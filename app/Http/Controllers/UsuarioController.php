<?php

namespace App\Http\Controllers;

use App\Exports\TrabajadoresPolifoniaExport;
use App\Models\Group;
use App\Models\TrabajadorPolifonia;
use App\Models\UserBuscador;
use App\Models\UserCronos;
use App\Models\UserFichaje;
use App\Models\UserPluton;
use App\Models\UserSemillas;
use App\Models\UserStore;
use App\Models\UserZeus;
use App\Models\Usuario;
use App\Models\UsuarioVinculado;
use App\Models\WorkerBuscador;
use App\Services\AusenciasService;
use App\Services\BulkUsuarioActionService;
use App\Services\CatalogosService;
use App\Services\FichajesService;
use App\Services\TrabajadoresIndexService;
use App\Services\UsuarioLookupService;
use App\Services\VinculacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly TrabajadoresIndexService $indexService,
        private readonly UsuarioLookupService $lookup,
        private readonly VinculacionService $vinculacion,
        private readonly CatalogosService $catalogos,
        private readonly AusenciasService $ausencias,
        private readonly FichajesService $fichajesService,
        private readonly BulkUsuarioActionService $bulkActions,
    ) {}

    public function index(Request $request)
    {
        $data = $this->indexService->handle($request);

        // ✅ Para pintar el select (DB principal)
        $groups = Group::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('usuarios.index', [
            'agrupados' => $data['paginator'],
            'search'    => $data['search'],
            'activo'    => $data['activo'],
            'sort'      => $data['sort'],
            'dir'       => $data['dir'],
            'stats'     => $data['stats'],
            'dupEmails' => $data['dupEmails'],
            'year'      => $data['year'],

            // ✅ NUEVO
            'groups'    => $groups,
        ]);
    }

    public function edit($id)
    {
        $usuario = Usuario::findOrFail($id);
        $vinculo = UsuarioVinculado::where('usuario_id', $id)->first();

        if ($vinculo) {
            return redirect()->route('usuarios.edit.uuid', ['uuid' => $vinculo->uuid]);
        }

        return view('usuarios.edit_unificado', compact('usuario'));
    }

    public function editUnificado($identificador)
    {
        $usuario = $trabajador = $usuarioPluton = null;

        $usuarioBuscador = $trabajadorBuscador = null;
        $userCronos = $userSemillas = $userStore = $userZeus = null;

        $vinculo = null;
        $email = null;

        // Helper para normalizar email
        $normEmail = static function ($value) {
            $v = mb_strtolower(trim((string) $value));
            return $v !== '' ? $v : null;
        };

        if (Str::isUuid($identificador)) {
            $vinculo = UsuarioVinculado::where('uuid', $identificador)->firstOrFail();

            // Principales por ID
            $usuario       = $vinculo->usuario_id ? Usuario::find($vinculo->usuario_id) : null;
            $trabajador    = $vinculo->trabajador_id ? TrabajadorPolifonia::on('mysql_polifonia')->find($vinculo->trabajador_id) : null;
            $usuarioPluton = $vinculo->pluton_id ? UserPluton::on('mysql_pluton')->find($vinculo->pluton_id) : null;

            // ✅ Satélites por IDs DEL VÍNCULO (no por email)
            $usuarioBuscador = !empty($vinculo->user_buscador_id)
                ? UserBuscador::on('mysql_buscador')->find($vinculo->user_buscador_id)
                : null;

            $trabajadorBuscador = null;
            if (!empty($vinculo->worker_buscador_id)) {
                try {
                    $trabajadorBuscador = WorkerBuscador::on('mysql_buscador')->find($vinculo->worker_buscador_id);
                } catch (\Throwable $e) {
                    Log::warning('[WorkerBuscador] Tabla no disponible (find by id): ' . $e->getMessage());
                }
            }

            $userCronos = !empty($vinculo->user_cronos_id)
                ? UserCronos::on('mysql_cronos')->find($vinculo->user_cronos_id)
                : null;

            $userSemillas = !empty($vinculo->user_semillas_id)
                ? UserSemillas::on('mysql_semillas')->find($vinculo->user_semillas_id)
                : null;

            $userStore = !empty($vinculo->user_store_id)
                ? UserStore::on('mysql_store')->find($vinculo->user_store_id)
                : null;

            $userZeus = !empty($vinculo->user_zeus_id)
                ? UserZeus::on('mysql_zeus')->find($vinculo->user_zeus_id)
                : null;

            // Email solo como sugerencia/fallback para búsquedas (y para Fichajes si no hay ID)
            $email = $normEmail($usuario?->email ?? $trabajador?->email ?? $usuarioPluton?->email);

        } else {
            // Lookup por email (cuando NO hay UUID / vínculo directo)
            $email = $normEmail($identificador);

            $usuario       = $email ? Usuario::whereRaw('LOWER(email) = ?', [$email])->first() : null;
            $trabajador    = $email ? TrabajadorPolifonia::whereRaw('LOWER(email) = ?', [$email])->first() : null;
            $usuarioPluton = $email ? UserPluton::whereRaw('LOWER(email) = ?', [$email])->first() : null;

            if ($email) {
                $usuarioBuscador = UserBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();

                try {
                    $trabajadorBuscador = WorkerBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();
                } catch (\Throwable $e) {
                    Log::warning('[WorkerBuscador] Tabla no disponible (find by email): ' . $e->getMessage());
                }

                $userCronos   = UserCronos::on('mysql_cronos')->whereRaw('LOWER(email) = ?', [$email])->first();
                $userSemillas = UserSemillas::on('mysql_semillas')->whereRaw('LOWER(email) = ?', [$email])->first();
                $userStore    = UserStore::on('mysql_store')->whereRaw('LOWER(email) = ?', [$email])->first();
                $userZeus     = UserZeus::on('mysql_zeus')->whereRaw('LOWER(email) = ?', [$email])->first();
            }
        }

        // Si no vino por UUID, intenta localizar vínculo por IDs conocidos (como ya hacías)
        if (!$vinculo) {
            $vinculo = UsuarioVinculado::where(function ($q) use ($usuario, $trabajador, $usuarioPluton) {
                if ($usuario)       $q->orWhere('usuario_id', $usuario->id);
                if ($trabajador)    $q->orWhere('trabajador_id', $trabajador->id);
                if ($usuarioPluton) $q->orWhere('pluton_id', $usuarioPluton->id);
            })->first();
        }

        // Si no hay nada, 404
        if (
            !$usuario && !$trabajador && !$usuarioPluton &&
            !$usuarioBuscador && !$trabajadorBuscador &&
            !$userCronos && !$userSemillas && !$userStore && !$userZeus
        ) {
            abort(404, 'No se encontró ningún registro.');
        }

        // Si no hay vínculo, manda a vincular con preselección (igual que antes)
        if (!$vinculo) {
            return redirect()->route('usuarios.vincular')
                ->with('usuario_preseleccionado', $usuario?->id)
                ->with('trabajador_preseleccionado', $trabajador?->id)
                ->with('pluton_preseleccionado', $usuarioPluton?->id)
                ->with('email_preseleccionado', $email);
        }

        // ✅ Fichajes: preferir ID del vínculo si existe; si no, fallback por email
        $userFichaje = null;
        if (!empty($vinculo->user_fichaje_id)) {
            $userFichaje = UserFichaje::find($vinculo->user_fichaje_id);
        } elseif ($email) {
            $userFichaje = UserFichaje::whereRaw('LOWER(email) = ?', [$email])->first();
        }

        // ✅ NUEVO: traer daily_summaries para mostrar en el módulo fichajes
        $fichajesDaily = collect();
        if ($userFichaje) {
            // opcional: rango por querystring
            $from = request('from'); // YYYY-MM-DD
            $to   = request('to');   // YYYY-MM-DD
            $fichajesDaily = $this->fichajesService->getDailySummaries($userFichaje, $from, $to);
        }

        return view('usuarios.edit_unificado', compact(
            'usuario',
            'trabajador',
            'usuarioPluton',
            'usuarioBuscador',
            'trabajadorBuscador',
            'userCronos',
            'userSemillas',
            'userStore',
            'userZeus',
            'vinculo',
            'userFichaje',
            'fichajesDaily'
        ));
    }

    public function editByUuid($uuid)
    {
        // Si quieres, podrías apuntar esta ruta a editUnificado directamente.
        // Mantengo este método por compatibilidad.
        return $this->editUnificado((string) $uuid);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
            'nombre'   => 'nullable|string|max:255',
        ]);

        $usuario = Usuario::findOrFail($id);
        $usuario->email = $request->email;
        $usuario->nombre = $request->nombre;

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        $usuario->save();

        Log::info('Usuario actualizado', [
            'usuario_id' => $usuario->id,
            'email'      => $usuario->email,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function vincular()
    {
        $this->vinculacion->clearPreselectionSession();

        $catalogos = $this->catalogos->getCatalogos(); // debe incluir 'usuariosFichajes'

        $vinculo = null;
        $uuid = null;
        $emailSugerido = null;

        return view('usuarios.vincular', array_merge($catalogos, compact('uuid', 'emailSugerido', 'vinculo')));
    }

    public function vincularStore(Request $request)
    {
        $data = $request->validate([
            'uuid' => 'required|uuid',
            'usuario_id' => 'nullable|exists:usuarios,id',

            'trabajador_id' => 'nullable|integer',
            'pluton_id' => 'nullable|integer',

            'user_buscador_id' => 'nullable|integer',
            'worker_buscador_id' => 'nullable|integer',

            'user_cronos_id' => 'nullable|integer',
            'user_semillas_id' => 'nullable|integer',
            'user_store_id' => 'nullable|integer',
            'user_zeus_id' => 'nullable|integer',

            'user_fichaje_id' => 'nullable|integer',
            'continue_workflow' => 'nullable|boolean',
            'email_search' => 'nullable|email',
        ]);

        $this->vinculacion->validateExternalIds($data);
        $this->vinculacion->save($data);

        if (!empty($data['continue_workflow'])) {
            return $this->redirectToNextVinculacionCandidate($request, 'Vinculación guardada correctamente.');
        }

        return redirect()->route('usuarios.index')->with('success', 'Vinculación guardada correctamente.');
    }

    public function vincularSuggestions(Request $request)
    {
        $payload = $request->validate([
            'email' => 'nullable|email',
            'uuid' => 'nullable|uuid',
        ]);

        $email = isset($payload['email']) ? mb_strtolower(trim($payload['email'])) : null;
        $uuid = $payload['uuid'] ?? null;

        if (!$email && !$uuid) {
            return response()->json([
                'ok' => true,
                'suggestion' => null,
            ]);
        }

        $matches = [
            'usuario_id' => $this->lookupByEmailSafe(Usuario::query(), $email),
            'trabajador_id' => $this->lookupByEmailSafe(TrabajadorPolifonia::on('mysql_polifonia'), $email),
            'pluton_id' => $this->lookupByEmailSafe(UserPluton::on('mysql_pluton'), $email),
            'user_buscador_id' => $this->lookupByEmailSafe(UserBuscador::on('mysql_buscador'), $email),
            'worker_buscador_id' => $this->lookupByEmailSafe(WorkerBuscador::on('mysql_buscador'), $email),
            'user_cronos_id' => $this->lookupByEmailSafe(UserCronos::on('mysql_cronos'), $email),
            'user_semillas_id' => $this->lookupByEmailSafe(UserSemillas::on('mysql_semillas'), $email),
            'user_store_id' => $this->lookupByEmailSafe(UserStore::on('mysql_store'), $email),
            'user_zeus_id' => $this->lookupByEmailSafe(UserZeus::on('mysql_zeus'), $email),
            'user_fichaje_id' => $this->lookupByEmailSafe(UserFichaje::on('mysql_fichajes'), $email),
        ];

        $linked = null;
        if ($uuid) {
            $linked = UsuarioVinculado::where('uuid', $uuid)->first();
        }

        if (!$linked) {
            $linked = UsuarioVinculado::where(function ($query) use ($matches) {
                foreach ($matches as $key => $id) {
                    if (!empty($id)) {
                        $query->orWhere($key, $id);
                    }
                }
            })->first();
        }

        $resolvedUuid = $linked?->uuid ?? $uuid ?? (string) Str::uuid();
        if ($linked) {
            foreach (array_keys($matches) as $key) {
                if (empty($matches[$key]) && !empty($linked->{$key})) {
                    $matches[$key] = $linked->{$key};
                }
            }
        }

        $matchedCount = collect($matches)->filter()->count();
        $score = min(100, ($matchedCount * 10) + ($linked ? 35 : 0) + ($uuid ? 10 : 0));

        return response()->json([
            'ok' => true,
            'suggestion' => [
                'uuid' => $resolvedUuid,
                'email' => $email,
                'score' => $score,
                'matched_systems' => $matchedCount,
                'ids' => $matches,
                'linked_uuid_found' => (bool) $linked,
            ],
        ]);
    }

    public function vincularEdit(UsuarioVinculado $vinculo)
    {
        $catalogos = $this->catalogos->getCatalogos(); // debe incluir 'usuariosFichajes'

        $emailSugerido =
            \App\Models\Usuario::find($vinculo->usuario_id)?->email
            ?? \App\Models\TrabajadorPolifonia::on('mysql_polifonia')->find($vinculo->trabajador_id)?->email
            ?? \App\Models\UserPluton::on('mysql_pluton')->find($vinculo->pluton_id)?->email;

        $uuid = $vinculo->uuid;

        return view('usuarios.vincular', array_merge($catalogos, compact('vinculo', 'uuid', 'emailSugerido')));
    }

    public function vincularUpdate(Request $request, UsuarioVinculado $vinculo)
    {
        $data = $request->validate([
            'uuid' => 'required|uuid',
            'usuario_id' => 'nullable|exists:usuarios,id',
            'trabajador_id' => 'nullable|integer',
            'pluton_id' => 'nullable|integer',
            'user_buscador_id' => 'nullable|integer',
            'worker_buscador_id' => 'nullable|integer',
            'user_cronos_id' => 'nullable|integer',
            'user_semillas_id' => 'nullable|integer',
            'user_store_id' => 'nullable|integer',
            'user_zeus_id' => 'nullable|integer',

            'user_fichaje_id' => 'nullable|integer',
            'continue_workflow' => 'nullable|boolean',
            'email_search' => 'nullable|email',
        ]);

        $this->vinculacion->validateExternalIds($data);

        // IMPORTANTE: aquí tú estabas forzando uuid del vínculo (bien).
        // Solo guardamos lo demás + user_fichaje_id.
        $this->vinculacion->save(array_merge($data, ['uuid' => $vinculo->uuid]));

        if (!empty($data['continue_workflow'])) {
            return $this->redirectToNextVinculacionCandidate($request, 'Vinculación actualizada correctamente.');
        }

        return redirect()->route('usuarios.index')->with('success', 'Vinculación actualizada correctamente.');
    }

    private function lookupByEmailSafe($query, ?string $email): ?int
    {
        if (!$email) {
            return null;
        }

        try {
            return $query->whereRaw('LOWER(email) = ?', [$email])->value('id');
        } catch (\Throwable $e) {
            Log::warning('[vincularSuggestions] Catálogo no disponible: ' . $e->getMessage());
            return null;
        }
    }

    private function redirectToNextVinculacionCandidate(Request $request, string $message)
    {
        $currentEmail = mb_strtolower(trim((string) $request->input('email_search', '')));

        $nextCandidate = Usuario::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereRaw('LOWER(email) > ?', [$currentEmail !== '' ? $currentEmail : ''])
            ->whereNotIn('id', function ($query) {
                $query->select('usuario_id')
                    ->from('usuarios_vinculados')
                    ->whereNotNull('usuario_id');
            })
            ->orderByRaw('LOWER(email) asc')
            ->first(['id', 'email']);

        if ($nextCandidate) {
            return redirect()
                ->route('usuarios.vincular', ['email' => $nextCandidate->email])
                ->with('success', $message . ' Continuamos con el siguiente candidato.');
        }

        return redirect()
            ->route('usuarios.vincular')
            ->with('success', $message . ' No hay más candidatos pendientes por email.');
    }

    public function bulkActions(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:activate,deactivate,auto_link_email',
            'worker_ids' => 'required|array|min:1',
            'worker_ids.*' => 'integer|min:1',
        ]);

        $workerIds = $data['worker_ids'];

        if ($data['action'] === 'activate') {
            $result = $this->bulkActions->setActivoDetailed($workerIds, 1);
            $okCount = count($result['ok']);
            $skippedCount = count($result['skipped']);
            $failedCount = count($result['failed']);

            $message = "Activacion masiva completada. OK: {$okCount}";
            if ($skippedCount > 0) {
                $message .= ", omitidos: {$skippedCount}";
            }
            if ($failedCount > 0) {
                $message .= ", fallidos: {$failedCount}";
            }
            $message .= '.';

            return back()
                ->with($failedCount > 0 ? 'error' : 'success', $message)
                ->with('bulk_result', [
                    'action' => 'activate',
                    'total' => (int) ($result['processed'] ?? count($workerIds)),
                    'ok' => $result['ok'] ?? [],
                    'skipped' => $result['skipped'] ?? [],
                    'failed' => $result['failed'] ?? [],
                ]);
        }

        if ($data['action'] === 'deactivate') {
            $result = $this->bulkActions->setActivoDetailed($workerIds, 0);
            $okCount = count($result['ok']);
            $skippedCount = count($result['skipped']);
            $failedCount = count($result['failed']);

            $message = "Desactivacion masiva completada. OK: {$okCount}";
            if ($skippedCount > 0) {
                $message .= ", omitidos: {$skippedCount}";
            }
            if ($failedCount > 0) {
                $message .= ", fallidos: {$failedCount}";
            }
            $message .= '.';

            return back()
                ->with($failedCount > 0 ? 'error' : 'success', $message)
                ->with('bulk_result', [
                    'action' => 'deactivate',
                    'total' => (int) ($result['processed'] ?? count($workerIds)),
                    'ok' => $result['ok'] ?? [],
                    'skipped' => $result['skipped'] ?? [],
                    'failed' => $result['failed'] ?? [],
                ]);
        }

        $stats = $this->bulkActions->autoLinkByEmail($workerIds);

        $okCount = count($stats['ok'] ?? []);
        $skippedCount = count($stats['skipped'] ?? []);
        $failedCount = count($stats['failed'] ?? []);

        $message = sprintf(
            'Autovinculacion por email completada. Procesados: %d, creados: %d, actualizados: %d, sin email: %d, sin coincidencias: %d, con error: %d.',
            $stats['processed'] ?? 0,
            $stats['created'] ?? 0,
            $stats['updated'] ?? 0,
            $stats['no_email'] ?? 0,
            $stats['no_match'] ?? 0,
            $stats['errors'] ?? 0
        );

        return back()
            ->with($failedCount > 0 ? 'error' : 'success', $message)
            ->with('bulk_result', [
                'action' => 'auto_link_email',
                'total' => (int) ($stats['processed'] ?? count($workerIds)),
                'ok' => $stats['ok'] ?? [],
                'skipped' => $stats['skipped'] ?? [],
                'failed' => $stats['failed'] ?? [],
            ]);
    }

    public function exportExcel(Request $request)
    {
        $search = $request->input('search');
        $activo = $request->input('activo');
        $sort   = $request->input('sort', 'nombre');
        $dir    = $request->input('dir', 'asc');

        $todos = $this->indexService->buildCollection($search);

        if ($activo !== null && $activo !== '') {
            $todos = $todos->filter(fn($r) => (int)($r->activo ?? 0) === (int)$activo)->values();
        }

        if ($search) {
            $s = mb_strtolower($search);
            $todos = $todos->filter(fn($r) =>
            str_contains(mb_strtolower($r->nombre ?? ''), $s)
            )->values();
        }

        $dir  = strtolower($dir) === 'desc' ? 'desc' : 'asc';
        $sort = in_array($sort, ['nombre', 'email', 'activo', 'vinculado'], true) ? $sort : 'nombre';

        $todos = $todos->sortBy(function ($r) use ($sort) {
            return match ($sort) {
                'email'     => mb_strtolower(trim($r->email ?? '')),
                'activo'    => (int)($r->activo ?? 0),
                'vinculado' => !empty($r->uuid) ? 1 : 0,
                default     => mb_strtolower($r->nombre ?? ''),
            };
        }, SORT_REGULAR, $dir === 'desc')->values();

        // ===== ÚLTIMO FICHAJE (solo mysql_fichajes) =====

        $principalIds = $todos->map(fn($r) =>
            $r->usuario_id ?? $r->id ?? $r->user_id ?? null
        )->filter()->unique()->values();

        $uuids = $todos->pluck('uuid')->filter()->unique()->values();

        if ($principalIds->isNotEmpty() || $uuids->isNotEmpty()) {

            $vincRows = DB::table('usuarios_vinculados')
                ->select('usuario_id', 'uuid', 'user_fichaje_id')
                ->where(function ($q) use ($principalIds, $uuids) {
                    if ($principalIds->isNotEmpty()) {
                        $q->whereIn('usuario_id', $principalIds);
                    }
                    if ($uuids->isNotEmpty()) {
                        $q->orWhereIn('uuid', $uuids);
                    }
                })
                ->get();

            $byUsuarioId = $vincRows->keyBy('usuario_id');
            $byUuid      = $vincRows->keyBy('uuid');

            $fichajesIds = $vincRows->pluck('user_fichaje_id')
                ->filter()
                ->unique()
                ->values();

            $lastPunchByUser = $fichajesIds->isNotEmpty()
                ? DB::connection('mysql_fichajes')
                    ->table('punches')
                    ->whereIn('user_id', $fichajesIds)
                    ->select('user_id', DB::raw('MAX(happened_at) as last_dt'))
                    ->groupBy('user_id')
                    ->pluck('last_dt', 'user_id')
                : collect();

            $todos = $todos->map(function ($r) use ($byUsuarioId, $byUuid, $lastPunchByUser) {

                $principalId = $r->usuario_id ?? $r->id ?? $r->user_id ?? null;
                $uuid = $r->uuid ?? null;

                $vinc = null;
                if ($principalId !== null && isset($byUsuarioId[$principalId])) {
                    $vinc = $byUsuarioId[$principalId];
                } elseif ($uuid && isset($byUuid[$uuid])) {
                    $vinc = $byUuid[$uuid];
                }

                $fichajesUserId = $vinc->user_fichaje_id ?? null;
                $r->ultimo_fichaje = $fichajesUserId
                    ? ($lastPunchByUser[$fichajesUserId] ?? null)
                    : null;

                return $r;
            })->values();

        } else {
            $todos = $todos->map(function ($r) {
                $r->ultimo_fichaje = null;
                return $r;
            })->values();
        }

        // ===== FIN ÚLTIMO FICHAJE =====

        $filename = 'trabajadores_polifonia_' . now()->format('Y_m_d_His') . '.xlsx';
        return Excel::download(new TrabajadoresPolifoniaExport($todos), $filename);
    }


    public function showVinculacionManualConDatos($uuid)
    {
        $vinculo = UsuarioVinculado::where('uuid', $uuid)->firstOrFail();

        // Cargamos con el lookup para no repetir lógica
        $models = $this->lookup->resolveByIdentificador((string) $uuid);

        // Guardar preseleccionados en sesión
        $this->vinculacion->storePreseleccionados([
            'usuario_preseleccionado'          => $models['usuario']?->id,
            'trabajador_preseleccionado'       => $models['trabajador']?->id,
            'pluton_preseleccionado'           => $models['usuarioPluton']?->id,
            'user_buscador_preseleccionado'    => $models['usuarioBuscador']?->id,
            'worker_buscador_preseleccionado'  => $models['trabajadorBuscador']?->id,
            'user_cronos_preseleccionado'      => $models['usuarioCronos']?->id,
            'user_semillas_preseleccionado'    => $models['usuarioSemillas']?->id,
            'user_store_preseleccionado'       => $models['usuarioStore']?->id,
            'user_zeus_preseleccionado'        => $models['usuarioZeus']?->id,
            'email_preseleccionado'            => $models['email'] ?? null,
        ]);

        $catalogos = $this->catalogos->getCatalogos();

        return view('usuarios.vincular', array_merge($catalogos, [
            'uuid'         => $uuid,
            'vinculo'      => $vinculo,
            'emailSugerido'=> $models['email'] ?? null,

            // Si tu vista usa estas variables para pintar seleccionados:
            'usuario'      => $models['usuario'],
            'trabajador'   => $models['trabajador'],
            'usuarioPluton'=> $models['usuarioPluton'],
            'userBuscador' => $models['usuarioBuscador'] ?? null,
            'workerBuscador' => $models['trabajadorBuscador'] ?? null,
        ]));
    }

    public function getDays(Request $r, int $trabajador)
    {
        $calendarYear = (int) ($r->query('calendar_year')
            ?? $r->query('vacation_year')
            ?? date('Y'));

        return response()->json([
            'ok' => true,
            'data' => $this->ausencias->buildAusenciasPayloadByCalendarYear($trabajador, $calendarYear),
        ]);
    }

    public function storeDays(Request $r, int $trabajador)
    {
        $data = $r->validate([
            'calendar_year' => 'required|integer',
            'tipo' => 'required|in:V,P,B,L',
            'from' => 'required|date',
            'to' => 'required|date',
            'mode' => 'required|in:add,remove',
            'bucket_year' => 'nullable|integer',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->ausencias->storeDays($trabajador, $data),
        ]);
    }

    public function vacaciones(Request $request, $trabajadorId)
    {
        $year = (int) $request->query('vacation_year', now()->year);
        $tipo = strtoupper($request->query('tipo', 'V'));
        $fecha = $request->query('fecha');
        $fechaOffset = $request->query('fecha_offset');

        return $this->ausencias->streamPdfVacaciones(
            (int) $trabajadorId,
            $year,
            $tipo,
            is_string($fecha) && trim($fecha) !== '' ? $fecha : null,
            $fechaOffset !== null && $fechaOffset !== '' ? (int) $fechaOffset : null,
        );
    }
    public function permisos(Request $request, $trabajadorId)
    {
        $year = (int) $request->query('vacation_year', now()->year);
        $fecha = $request->query('fecha');
        $fechaOffset = $request->query('fecha_offset');

        Log::info('[PDF PERMISOS] request', [
            'trabajadorId' => $trabajadorId,
            'vacation_year' => $year,
            'tipo_forzado' => 'P',
            'full_url' => $request->fullUrl(),
            'query' => $request->query(),
        ]);

        return $this->ausencias->streamPdfVacaciones(
            (int) $trabajadorId,
            $year,
            'P',
            is_string($fecha) && trim($fecha) !== '' ? $fecha : null,
            $fechaOffset !== null && $fechaOffset !== '' ? (int) $fechaOffset : null,
        );
    }

    public function bajas(Request $request, $trabajadorId)
    {
        $year = (int) $request->query('vacation_year', now()->year);
        $fecha = $request->query('fecha');
        $fechaOffset = $request->query('fecha_offset');

        Log::info('[PDF BAJAS] request', [
            'trabajadorId' => $trabajadorId,
            'vacation_year' => $year,
            'tipo_forzado' => 'B',
            'full_url' => $request->fullUrl(),
            'query' => $request->query(),
        ]);

        return $this->ausencias->streamPdfVacaciones(
            (int) $trabajadorId,
            $year,
            'B',
            is_string($fecha) && trim($fecha) !== '' ? $fecha : null,
            $fechaOffset !== null && $fechaOffset !== '' ? (int) $fechaOffset : null,
        );
    }

}
