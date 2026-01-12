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

class UsuarioController extends Controller
{
    public function __construct(
        private readonly TrabajadoresIndexService $indexService,
        private readonly UsuarioLookupService $lookup,
        private readonly VinculacionService $vinculacion,
        private readonly CatalogosService $catalogos,
        private readonly AusenciasService $ausencias,
        private readonly FichajesService $fichajesService,
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

            $trabajadorBuscador = !empty($vinculo->worker_buscador_id)
                ? WorkerBuscador::on('mysql_buscador')->find($vinculo->worker_buscador_id)
                : null;

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
                $usuarioBuscador    = UserBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();
                $trabajadorBuscador = WorkerBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();

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
        ]);

        $this->vinculacion->validateExternalIds($data);
        $this->vinculacion->save($data);

        return redirect()->route('usuarios.index')->with('success', 'Vinculación guardada correctamente.');
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
        ]);

        $this->vinculacion->validateExternalIds($data);

        // IMPORTANTE: aquí tú estabas forzando uuid del vínculo (bien).
        // Solo guardamos lo demás + user_fichaje_id.
        $this->vinculacion->save(array_merge($data, ['uuid' => $vinculo->uuid]));

        return redirect()->route('usuarios.index')->with('success', 'Vinculación actualizada correctamente.');
    }

    public function exportExcel(Request $request)
    {
        // Reutiliza EXACTAMENTE los mismos filtros que el index (sin duplicar lógica)
        $search = $request->input('search');
        $activo = $request->input('activo');
        $sort   = $request->input('sort', 'nombre');
        $dir    = $request->input('dir', 'asc');

        // Si quieres que sea idéntico a index: en lugar de rebuild, podrías extraer otro método en el service.
        $todos = $this->indexService->buildCollection($search);

        // Aplicar filtros/sort igual que en index:
        // (para no duplicar, lo ideal es exponer en el service métodos públicos apply..., pero aquí lo dejo simple)
        if ($activo !== null && $activo !== '') {
            $todos = $todos->filter(fn($r) => (int)($r->activo ?? 0) === (int)$activo)->values();
        }
        if ($search) {
            $s = mb_strtolower($search);
            $todos = $todos->filter(fn($r) => str_contains(mb_strtolower($r->nombre ?? ''), $s))->values();
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
            'tipo' => 'required|in:V,P,B',
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

        return $this->ausencias->streamPdfVacaciones((int)$trabajadorId, $year, $tipo);
    }
}
