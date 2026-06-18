<?php

namespace App\Services;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrabajadoresIndexService
{
    public function __construct(
        private readonly BienestarService $bienestar,
        private readonly AusenciasService $ausencias,
    ) {}

    public function handle(Request $request): array
    {
        if ($request->boolean('reset_filters')) {
            $request->session()->forget('usuarios.index.filters');
        }

        $filterSessionKey = 'usuarios.index.filters';
        $hasFilterInput = $request->hasAny(['search', 'activo', 'sort', 'dir', 'vacation_year', 'grupo']);
        $storedFilters = (array) $request->session()->get($filterSessionKey, []);

        $search = $hasFilterInput ? $request->input('search') : ($storedFilters['search'] ?? null);
        $activo = $hasFilterInput ? $request->input('activo') : ($storedFilters['activo'] ?? null); // '' | '1' | '0'
        $sort   = (string) ($hasFilterInput ? $request->input('sort', 'nombre') : ($storedFilters['sort'] ?? 'nombre')); // nombre|email|activo|vinculado
        $dir    = (string) ($hasFilterInput ? $request->input('dir', 'asc') : ($storedFilters['dir'] ?? 'asc')); // asc|desc
        $year   = (int) ($hasFilterInput ? $request->input('vacation_year', date('Y')) : ($storedFilters['vacation_year'] ?? date('Y')));
        $grupo  = $hasFilterInput ? $request->input('grupo') : ($storedFilters['grupo'] ?? null);

        $request->session()->put($filterSessionKey, [
            'search' => $search,
            'activo' => $activo,
            'sort' => $sort,
            'dir' => $dir,
            'vacation_year' => $year,
            'grupo' => $grupo,
        ]);

        // ⚡ Se pasan $search y $activo a buildCollection para empujar filtros a SQL
        $todos = $this->buildCollection($search, $activo);
        $todos = $this->applyGroupFilter($todos, $grupo);

        $dupEmails = $this->getDuplicatedEmails($todos);
        $todos     = $this->applySort($todos, $sort, $dir);
        $stats     = $this->buildStats($todos);

        [$items, $paginator] = $this->paginateAndDecorate($todos, $request, $year);

        return [
            'paginator' => $paginator,
            'search'    => $search,
            'activo'    => $activo,
            'sort'      => $sort,
            'dir'       => strtolower($dir) === 'desc' ? 'desc' : 'asc',
            'stats'     => $stats,
            'dupEmails' => $dupEmails,
            'year'      => $year,
            'grupo'     => $grupo,
        ];
    }

    /**
     * ⚡ Construye la colección unificada de trabajadores aplicando filtros en SQL
     * para la parte no-vinculada y en PHP para la parte vinculada (subconjunto pequeño).
     *
     * Se eliminaron applyActivoFilter() y applySearchFilter() del flujo principal
     * ya que los filtros ahora se aplican aquí directamente.
     */
    public function buildCollection(?string $search = null, $activo = null): Collection
    {
        // 1) Vinculos + relaciones eager-loaded
        $vinculos = UsuarioVinculado::with([
            'usuario:id,nombre,email',
            'trabajador:id,nombre,email,activo',
            'pluton:id,nombre,email,imei'
        ])->get()
            ->unique(fn($v) => $v->trabajador_id ?: $v->uuid)
            ->values();

        $vinculados = $vinculos->map(function ($v) {
            $registro = $v->trabajador ?? $v->usuario ?? $v->pluton;

            if ($registro) {
                $registro->uuid = $v->uuid;
                $registro->tipo = match (true) {
                    $registro instanceof \App\Models\TrabajadorPolifonia => 'trabajador',
                    $registro instanceof \App\Models\Usuario             => 'usuario',
                    default                                              => 'pluton'
                };
                return $registro;
            }
            return null;
        })
        ->filter()
        ->filter(fn($r) => ($r->tipo ?? null) === 'trabajador');

        // Aplicar filtros en PHP sobre el subconjunto vinculado (normalmente pequeño)
        if ($activo !== null && $activo !== '') {
            $vinculados = $vinculados->filter(fn($r) => (int)($r->activo ?? 0) === (int)$activo);
        }
        if ($search) {
            $s = mb_strtolower($search);
            $vinculados = $vinculados->filter(
                fn($r) => str_contains(mb_strtolower($r->nombre ?? ''), $s)
            );
        }

        // 2) No-vinculados: filtros activo + búsqueda directo en SQL ⚡
        $trabajadores = TrabajadorPolifonia::whereNotIn(
                'id',
                $vinculos->pluck('trabajador_id')->filter()->values()->all()
            )
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->when(
                $activo !== null && $activo !== '',
                fn($q) => $q->where('activo', (int)$activo)
            )
            ->get()
            ->each(fn($t) => $t->tipo = 'trabajador');

        // 3) Unificar
        return $vinculados->values()
            ->concat($trabajadores)
            ->values();
    }

    private function applyGroupFilter(Collection $todos, $grupo): Collection
    {
        if ($grupo === null || $grupo === '') {
            return $todos->values();
        }

        $grupoId = (int) $grupo;
        if ($grupoId <= 0) {
            return $todos->values();
        }

        $ids = DB::connection('mysql')
            ->table('group_trabajador')
            ->where('group_id', $grupoId)
            ->pluck('trabajador_id')
            ->map(fn($v) => (int)$v)
            ->values()
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return $todos
            ->filter(fn($r) => in_array((int)($r->id ?? 0), $ids, true))
            ->values();
    }

    private function getDuplicatedEmails(Collection $todos): array
    {
        $emails = $todos
            ->map(fn($r) => mb_strtolower(trim($r->email ?? '')))
            ->filter(fn($e) => $e !== '');

        return $emails->countBy()->filter(fn($c) => $c > 1)->keys()->all();
    }

    private function applySort(Collection $todos, string $sort, string $dir): Collection
    {
        $dir  = strtolower($dir) === 'desc' ? 'desc' : 'asc';
        $sort = in_array($sort, ['nombre', 'email', 'activo', 'vinculado'], true) ? $sort : 'nombre';

        return $todos->sortBy(function ($r) use ($sort) {
            return match ($sort) {
                'email'     => mb_strtolower(trim($r->email ?? '')),
                'activo'    => (int)($r->activo ?? 0),
                'vinculado' => !empty($r->uuid) ? 1 : 0,
                default     => mb_strtolower($r->nombre ?? ''),
            };
        }, SORT_REGULAR, $dir === 'desc')->values();
    }

    private function buildStats(Collection $todos): array
    {
        return [
            'total'      => $todos->count(),
            'vinculados' => $todos->filter(fn($r) => !empty($r->uuid))->count(),
            'activos'    => $todos->filter(fn($r) => (int)($r->activo ?? 0) === 1)->count(),
            'inactivos'  => $todos->filter(fn($r) => (int)($r->activo ?? 0) === 0)->count(),
        ];
    }

    private function paginateAndDecorate(Collection $todos, Request $request, int $year): array
    {
        $perPage = 50;
        $page    = (int) $request->input('page', 1);

        $items = $todos->forPage($page, $perPage)->values();

        // Bienestar y ausencias solo para la página actual (no toda la colección)
        $items = $this->bienestar->attachMoodUltimasSemanas($items, 4);
        $items = $this->ausencias->attachAusenciasPayload($items, $year);

        $paginator = new LengthAwarePaginator(
            $items,
            $todos->count(),
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        return [$items, $paginator];
    }
}
