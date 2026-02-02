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
        $search = $request->input('search');
        $activo = $request->input('activo');         // '' | '1' | '0'
        $sort   = $request->input('sort', 'nombre'); // nombre|email|activo|vinculado
        $dir    = $request->input('dir', 'asc');     // asc|desc
        $year   = (int) $request->input('vacation_year', date('Y'));
        $grupo = $request->input('grupo'); // '' | null | 'ID'
        $todos = $this->buildCollection($search);

        $todos = $this->applyActivoFilter($todos, $activo);
        $todos = $this->applySearchFilter($todos, $search);
        $todos = $this->applyGroupFilter($todos, $grupo);

        $dupEmails = $this->getDuplicatedEmails($todos);

        $todos = $this->applySort($todos, $sort, $dir);

        $stats = $this->buildStats($todos);

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
            'grupo' => $grupo,
        ];
    }

    public function buildCollection(?string $search = null): Collection
    {
        // 1) Vinculos + representante
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
                    $registro instanceof \App\Models\Usuario            => 'usuario',
                    default                                             => 'pluton'
                };

                return $registro;
            }

            return null;
        })->filter();

        // 2) No vinculados (solo trabajadores polifonia)
        $trabajadores = TrabajadorPolifonia::whereNotIn('id', $vinculos->pluck('trabajador_id')->filter())
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->get()
            ->each(fn($t) => $t->tipo = 'trabajador');

        // 3) Unificar SOLO trabajadores
        return $vinculados
            ->filter(fn($r) => ($r->tipo ?? null) === 'trabajador')
            ->concat($trabajadores)
            ->values();
    }

    private function applyActivoFilter(Collection $todos, $activo): Collection
    {
        if ($activo !== null && $activo !== '') {
            $todos = $todos->filter(fn($r) => (int)($r->activo ?? 0) === (int)$activo);
        }
        return $todos->values();
    }

    private function applySearchFilter(Collection $todos, ?string $search): Collection
    {
        if ($search) {
            $s = mb_strtolower($search);
            $todos = $todos->filter(fn($r) => str_contains(mb_strtolower($r->nombre ?? ''), $s));
        }
        return $todos->values();
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

        // Pivot en DB principal (mysql)
        $ids = DB::connection('mysql')
            ->table('group_trabajador')
            ->where('group_id', $grupoId)
            ->pluck('trabajador_id')
            ->map(fn($v) => (int)$v)
            ->values()
            ->all();

        // Si el grupo no tiene nadie -> lista vacía (sin romper nada)
        if (empty($ids)) {
            return collect();
        }

        // En tu colección todos son trabajadores (tipo trabajador) y tienen id
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

        $sorted = $todos->sortBy(function ($r) use ($sort) {
            return match ($sort) {
                'email'     => mb_strtolower(trim($r->email ?? '')),
                'activo'    => (int)($r->activo ?? 0),
                'vinculado' => !empty($r->uuid) ? 1 : 0,
                default     => mb_strtolower($r->nombre ?? ''),
            };
        }, SORT_REGULAR, $dir === 'desc');

        return $sorted->values();
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
        $page = (int) $request->input('page', 1);

        $items = $todos->forPage($page, $perPage)->values();

        // Bienestar (4 semanas) SOLO items de la página
        $items = $this->bienestar->attachMoodUltimasSemanas($items, 4);

        // Ausencias SOLO items de la página
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
