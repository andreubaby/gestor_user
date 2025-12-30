<?php

namespace App\Http\Controllers;

use App\Models\UserFichaje;
use App\Models\TrabajadorDia;
use App\Models\UserCronos;
use App\Models\UserSemillas;
use App\Models\UserStore;
use App\Models\UserZeus;
use App\Models\Usuario;
use App\Models\TrabajadorPolifonia;
use App\Models\UserPluton;
use App\Models\UsuarioVinculado;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\UserBuscador;
use App\Models\WorkerBuscador;
use App\Models\UserTrabajador;
use App\Models\Fichar;
use App\Exports\TrabajadoresPolifoniaExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $activo = $request->input('activo');           // '' | '1' | '0'
        $sort   = $request->input('sort', 'nombre');   // nombre|email|activo|vinculado
        $dir    = $request->input('dir', 'asc');       // asc|desc

        // ✅ Año para ausencias
        $year = (int) $request->input('vacation_year', date('Y'));

        // 1. Cargar vinculaciones (incluye activo en trabajador)
        $vinculos = UsuarioVinculado::with([
            'usuario:id,nombre,email',
            'trabajador:id,nombre,email,activo',
            'pluton:id,nombre,email,imei'
        ])->get()
            ->unique(fn($v) => $v->trabajador_id ?: $v->uuid)
            ->values();

        // 2. Representante por vínculo
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

        // 3. NO vinculados (solo trabajadores Polifonía)
        $trabajadores = TrabajadorPolifonia::whereNotIn('id', $vinculos->pluck('trabajador_id')->filter())
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->get()
            ->each(fn($t) => $t->tipo = 'trabajador');

        // 4. Unificar SOLO trabajadores (vinculados + no vinculados)
        $todos = $vinculados
            ->filter(fn($r) => ($r->tipo ?? null) === 'trabajador')
            ->concat($trabajadores);

        // --- Filtro activo
        if ($activo !== null && $activo !== '') {
            $todos = $todos->filter(fn($r) => (int)($r->activo ?? 0) === (int)$activo);
        }

        // --- Búsqueda (mb_* para tildes)
        if ($search) {
            $s = mb_strtolower($search);
            $todos = $todos->filter(fn($r) => str_contains(mb_strtolower($r->nombre ?? ''), $s));
        }

        // --- Emails duplicados
        $emails = $todos
            ->map(fn($r) => mb_strtolower(trim($r->email ?? '')))
            ->filter(fn($e) => $e !== '');

        $dupEmails = $emails->countBy()->filter(fn($c) => $c > 1)->keys()->all();

        // --- Orden
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

        // --- Stats
        $stats = [
            'total'      => $todos->count(),
            'vinculados' => $todos->filter(fn($r) => !empty($r->uuid))->count(),
            'activos'    => $todos->filter(fn($r) => (int)($r->activo ?? 0) === 1)->count(),
            'inactivos'  => $todos->filter(fn($r) => (int)($r->activo ?? 0) === 0)->count(),
        ];

        // --- Paginación manual
        $perPage = 50;
        $page = (int) $request->input('page', 1);

        $items = $todos->forPage($page, $perPage)->values();

        /**
         * ✅ Bienestar: 4 últimas SEMANAS (media semanal de bienestar 1..4 por email)
         * Resultado: $r->bienestar_ultimos = [int,int,int,int] (máx 4) => semana0..semana3
         */
        $emailsPage = $items
            ->map(fn($r) => mb_strtolower(trim($r->email ?? '')))
            ->filter()
            ->unique()
            ->values();

        $bienestarByEmail = collect();

        if ($emailsPage->isNotEmpty()) {

            // 1) users remotos por email
            $usersRemotos = UserTrabajador::query()
                ->select(['id', 'email'])
                ->whereIn('email', $emailsPage->all())
                ->get();

            $userIds = $usersRemotos->pluck('id')->all();

            // 2) Definir semanas (últimas 4): lunes..domingo
            // Ajusta startOfWeek() si tu semana empieza en domingo, pero en ES normalmente lunes.
            $startOldestWeek = now()->startOfWeek(Carbon::MONDAY)->subWeeks(3)->startOfDay();
            $endThisWeek     = now()->endOfWeek(Carbon::MONDAY)->endOfDay();

            // Vamos a generar las 4 “keys” en orden: más reciente primero (0) -> más antigua (3)
            $weekKeys = collect(range(0, 3))->map(function ($i) {
                $monday = now()->startOfWeek(Carbon::MONDAY)->subWeeks($i)->startOfDay();
                // clave estable para agrupar (lunes de esa semana)
                return $monday->format('Y-m-d');
            });

            // 3) Traer fichajes dentro del rango total (4 semanas)
            $fichajes = Fichar::query()
                ->select(['user_id', 'bienestar', 'created_at'])
                ->whereIn('user_id', $userIds)
                ->whereBetween('created_at', [$startOldestWeek, $endThisWeek])
                ->orderBy('created_at', 'desc')
                ->get();

            // 4) Agrupar por user y por semana (lunes)
            $avgByUserWeek = $fichajes
                ->groupBy(function ($row) {
                    $monday = Carbon::parse($row->created_at)->startOfWeek(Carbon::MONDAY)->startOfDay();
                    return $row->user_id . '|' . $monday->format('Y-m-d');
                })
                ->map(function ($group) {
                    // media de bienestar (1..4)
                    return $group->avg('bienestar');
                });

            // 5) Construir array de 4 semanas por email: [semana0..semana3] en nivel 1..4 (o null)
            $bienestarByEmail = $usersRemotos->mapWithKeys(function ($u) use ($avgByUserWeek, $weekKeys) {
                $emailKey = mb_strtolower(trim($u->email ?? ''));

                $vals = $weekKeys->map(function ($weekMonday) use ($avgByUserWeek, $u) {
                    $key = $u->id . '|' . $weekMonday;
                    $avg = $avgByUserWeek->get($key, null);
                    if ($avg === null) return null;

                    // Convertir media a 1..4
                    // Opción A (recomendada): redondeo al entero más cercano
                    $lvl = (int) round($avg);

                    // asegurar límites
                    if ($lvl < 1) $lvl = 1;
                    if ($lvl > 4) $lvl = 4;

                    return $lvl;
                })->values()->all();

                return [$emailKey => $vals];
            });
        }

        // 6) pegar al listado
        $items = $items->map(function ($r) use ($bienestarByEmail) {
            $emailKey = mb_strtolower(trim($r->email ?? ''));
            $r->bienestar_ultimos = $bienestarByEmail->get($emailKey, [null, null, null, null]);
            return $r;
        });

        // ✅ Ausencias SOLO a los items de la página
        $items = $items->map(function ($r) use ($year) {
            $r->ausencias = $this->buildAusenciasPayload((int)$r->id, $year);
            return $r;
        });

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $todos->count(),
            $perPage,
            $page,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('usuarios.index', [
            'agrupados' => $paginator,
            'search'    => $search,
            'activo'    => $activo,
            'sort'      => $sort,
            'dir'       => $dir,
            'stats'     => $stats,
            'dupEmails' => $dupEmails,
            'year'      => $year,
        ]);
    }

    public function edit($id)
    {
        $usuario = Usuario::findOrFail($id);
        $vinculo = UsuarioVinculado::where('usuario_id', $id)->first();

        if ($vinculo) {
            return redirect()->route('usuarios.edit.uuid', ['uuid' => $vinculo->uuid]);
        }

        return view('usuarios.edit', compact('usuario'));
    }

    public function editUnificado($identificador)
    {
        $usuario = $trabajador = $usuarioPluton = null;
        $usuarioBuscador = $trabajadorBuscador = null;
        $usuarioCronos = $usuarioSemillas = null;
        $usuarioStore  = $usuarioZeus = null;

        if (Str::isUuid($identificador)) {
            $vinculo = UsuarioVinculado::where('uuid', $identificador)->firstOrFail();

            $usuario       = Usuario::find($vinculo->usuario_id);
            $trabajador    = TrabajadorPolifonia::on('mysql_polifonia')->find($vinculo->trabajador_id);
            $usuarioPluton = UserPluton::on('mysql_pluton')->find($vinculo->pluton_id);

            // No hay UUID en buscador, buscamos por email
            $email = $usuario?->email ?? $trabajador?->email ?? $usuarioPluton?->email;

        } else {
            $email = strtolower($identificador);

            $usuario       = Usuario::whereRaw('LOWER(email) = ?', [$email])->first();
            $trabajador    = TrabajadorPolifonia::whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioPluton = UserPluton::whereRaw('LOWER(email) = ?', [$email])->first();
        }

        // Buscar usuarios del nuevo sistema buscador
        if (!empty($email)) {
            $usuarioBuscador     = \App\Models\UserBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();
            $trabajadorBuscador  = \App\Models\WorkerBuscador::on('mysql_buscador')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioCronos       = \App\Models\UserCronos::on('mysql_cronos')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioSemillas     = \App\Models\UserSemillas::on('mysql_semillas')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioStore        = \App\Models\UserStore::on('mysql_store')->whereRaw('LOWER(email) = ?', [$email])->first();
            $usuarioZeus         = \App\Models\UserZeus::on('mysql_zeus')->whereRaw('LOWER(email) = ?', [$email])->first();
        }

        // Validación básica
        if (!$usuario && !$trabajador && !$usuarioPluton && !$usuarioBuscador && !$trabajadorBuscador && !$usuarioCronos && !$usuarioSemillas && !$usuarioStore && !$usuarioZeus) {
            abort(404, 'No se encontró ningún registro.');
        }

        $userFichaje = null;

        if (!empty($email)) {
            $emailKey = mb_strtolower(trim((string)$email));

            if ($emailKey !== '') {
                $userFichaje = UserFichaje::whereRaw('LOWER(email) = ?', [$emailKey])->first();
            }
        }

        // Buscar vínculo si es que existe
        $vinculo = UsuarioVinculado::where(function ($q) use ($usuario, $trabajador, $usuarioPluton) {
            if ($usuario)       $q->orWhere('usuario_id', $usuario->id);
            if ($trabajador)    $q->orWhere('trabajador_id', $trabajador->id);
            if ($usuarioPluton) $q->orWhere('pluton_id', $usuarioPluton->id);
        })->first();

        if (!$vinculo) {
            return redirect()->route('usuarios.vincular')
                ->with('usuario_preseleccionado', $usuario?->id)
                ->with('trabajador_preseleccionado', $trabajador?->id)
                ->with('pluton_preseleccionado', $usuarioPluton?->id)
                ->with('email_preseleccionado', $email);
        }

        return view('usuarios.edit_unificado', compact(
            'usuario',
            'trabajador',
            'usuarioPluton',
            'usuarioBuscador',
            'trabajadorBuscador',
            'usuarioCronos',
            'usuarioSemillas',
            'usuarioStore',
            'usuarioZeus',
            'vinculo',
            'userFichaje'
        ));
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
        // 🧹 Limpiar sesión de preselecciones antiguas
        session()->forget([
            'usuario_preseleccionado',
            'trabajador_preseleccionado',
            'pluton_preseleccionado',
            'user_buscador_preseleccionado',
            'worker_buscador_preseleccionado',
            'user_cronos_preseleccionado',
            'user_semillas_preseleccionado',
            'user_store_preseleccionado',
            'user_zeus_preseleccionado',
            'email_preseleccionado',
        ]);

        // ✅ Catálogos
        $usuarios             = Usuario::orderBy('nombre')->get();
        $trabajadores         = TrabajadorPolifonia::on('mysql_polifonia')->orderBy('nombre')->get();
        $usuariosPluton       = UserPluton::on('mysql_pluton')->orderBy('nombre')->get();
        $usuariosBuscador     = UserBuscador::on('mysql_buscador')->orderBy('name')->get();
        $trabajadoresBuscador = WorkerBuscador::on('mysql_buscador')->orderBy('name')->get();
        $userCronos           = UserCronos::on('mysql_cronos')->orderBy('name')->get();
        $userSemillas         = UserSemillas::on('mysql_semillas')->orderBy('name')->get();
        $userStore            = UserStore::on('mysql_store')->orderBy('name')->get();
        $userZeus             = UserZeus::on('mysql_zeus')->orderBy('name')->get();

        // sin edición
        $vinculo = null;
        $uuid = null;
        $emailSugerido = null;

        return view('usuarios.vincular', compact(
            'usuarios','trabajadores','usuariosPluton',
            'usuariosBuscador','trabajadoresBuscador',
            'userCronos','userSemillas','userStore','userZeus',
            'uuid','emailSugerido','vinculo'
        ));
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
        ]);

        // ✅ Validaciones externas (solo si viene ID)
        if (!empty($data['trabajador_id']) &&
            !DB::connection('mysql_polifonia')->table('trabajadores')->where('id', $data['trabajador_id'])->exists()) {
            return back()->withErrors(['trabajador_id' => 'El trabajador no existe en Polifonía'])->withInput();
        }

        if (!empty($data['pluton_id']) &&
            !DB::connection('mysql_pluton')->table('users')->where('id', $data['pluton_id'])->exists()) {
            return back()->withErrors(['pluton_id' => 'El usuario no existe en Plutón'])->withInput();
        }

        if (!empty($data['user_buscador_id']) &&
            !DB::connection('mysql_buscador')->table('users')->where('id', $data['user_buscador_id'])->exists()) {
            return back()->withErrors(['user_buscador_id' => 'El usuario no existe en Buscador'])->withInput();
        }

        if (!empty($data['worker_buscador_id']) &&
            !DB::connection('mysql_buscador')->table('workers')->where('id', $data['worker_buscador_id'])->exists()) {
            return back()->withErrors(['worker_buscador_id' => 'El trabajador no existe en Buscador'])->withInput();
        }

        if (!empty($data['user_cronos_id']) &&
            !DB::connection('mysql_cronos')->table('users')->where('id', $data['user_cronos_id'])->exists()) {
            return back()->withErrors(['user_cronos_id' => 'El usuario no existe en Cronos'])->withInput();
        }

        if (!empty($data['user_semillas_id']) &&
            !DB::connection('mysql_semillas')->table('users')->where('id', $data['user_semillas_id'])->exists()) {
            return back()->withErrors(['user_semillas_id' => 'El usuario no existe en Semillas'])->withInput();
        }

        if (!empty($data['user_store_id']) &&
            !DB::connection('mysql_store')->table('users')->where('id', $data['user_store_id'])->exists()) {
            return back()->withErrors(['user_store_id' => 'El usuario no existe en Store'])->withInput();
        }

        if (!empty($data['user_zeus_id']) &&
            !DB::connection('mysql_zeus')->table('users')->where('id', $data['user_zeus_id'])->exists()) {
            return back()->withErrors(['user_zeus_id' => 'El usuario no existe en Zeus'])->withInput();
        }

        UsuarioVinculado::updateOrCreate(
            ['uuid' => $data['uuid']],
            [
                'usuario_id'         => $data['usuario_id'] ?? null,
                'trabajador_id'      => $data['trabajador_id'] ?? null,
                'pluton_id'          => $data['pluton_id'] ?? null,
                'user_buscador_id'   => $data['user_buscador_id'] ?? null,
                'worker_buscador_id' => $data['worker_buscador_id'] ?? null,
                'user_cronos_id'     => $data['user_cronos_id'] ?? null,
                'user_semillas_id'   => $data['user_semillas_id'] ?? null,
                'user_store_id'      => $data['user_store_id'] ?? null,
                'user_zeus_id'       => $data['user_zeus_id'] ?? null,
            ]
        );

        return redirect()->route('usuarios.index')->with('success', 'Vinculación guardada correctamente.');
    }

    public function vincularEdit(UsuarioVinculado $vinculo)
    {
        // Catálogos
        $usuarios             = Usuario::orderBy('nombre')->get();
        $trabajadores         = TrabajadorPolifonia::on('mysql_polifonia')->orderBy('nombre')->get();
        $usuariosPluton       = UserPluton::on('mysql_pluton')->orderBy('nombre')->get();
        $usuariosBuscador     = UserBuscador::on('mysql_buscador')->orderBy('name')->get();
        $trabajadoresBuscador = WorkerBuscador::on('mysql_buscador')->orderBy('name')->get();
        $userCronos           = UserCronos::on('mysql_cronos')->orderBy('name')->get();
        $userSemillas         = UserSemillas::on('mysql_semillas')->orderBy('name')->get();
        $userStore            = UserStore::on('mysql_store')->orderBy('name')->get();
        $userZeus             = UserZeus::on('mysql_zeus')->orderBy('name')->get();

        // Para sugerencia email (si quieres)
        $emailSugerido =
            Usuario::find($vinculo->usuario_id)?->email
            ?? TrabajadorPolifonia::on('mysql_polifonia')->find($vinculo->trabajador_id)?->email
            ?? UserPluton::on('mysql_pluton')->find($vinculo->pluton_id)?->email;

        $uuid = $vinculo->uuid;

        return view('usuarios.vincular', compact(
            'vinculo','uuid','emailSugerido',
            'usuarios','trabajadores','usuariosPluton',
            'usuariosBuscador','trabajadoresBuscador',
            'userCronos','userSemillas','userStore','userZeus'
        ));
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
        ]);

        // (Puedes reutilizar exactamente las mismas validaciones externas del store)
        // Para no duplicar, extrae a private function validateExternalIds($data)

        $vinculo->update([
            'uuid'              => $data['uuid'],
            'usuario_id'        => $data['usuario_id'] ?? null,
            'trabajador_id'     => $data['trabajador_id'] ?? null,
            'pluton_id'         => $data['pluton_id'] ?? null,
            'user_buscador_id'  => $data['user_buscador_id'] ?? null,
            'worker_buscador_id'=> $data['worker_buscador_id'] ?? null,
            'user_cronos_id'    => $data['user_cronos_id'] ?? null,
            'user_semillas_id'  => $data['user_semillas_id'] ?? null,
            'user_store_id'     => $data['user_store_id'] ?? null,
            'user_zeus_id'      => $data['user_zeus_id'] ?? null,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Vinculación actualizada correctamente.');
    }

    public function editByUuid($uuid)
    {
        $vinculo = UsuarioVinculado::where('uuid', $uuid)->firstOrFail();

        $usuario            = Usuario::find($vinculo->usuario_id);
        $trabajador         = TrabajadorPolifonia::on('mysql_polifonia')->find($vinculo->trabajador_id);
        $usuarioPluton      = UserPluton::on('mysql_pluton')->find($vinculo->pluton_id);
        $usuarioBuscador    = UserBuscador::on('mysql_buscador')->find($vinculo->user_buscador_id);
        $trabajadorBuscador = WorkerBuscador::on('mysql_buscador')->find($vinculo->worker_buscador_id);
        $userCronos         = UserCronos::on('mysql_cronos')->find($vinculo->user_cronos_id);
        $userSemillas       = UserSemillas::on('mysql_semillas')->find($vinculo->user_semillas_id);
        $userStore          = UserStore::on('mysql_store')->find($vinculo->user_store_id);
        $userZeus           = UserZeus::on('mysql_zeus')->find($vinculo->user_zeus_id);

        // ✅ NUEVO: Usuario Fichajes (mysql_fichajes)
        // 1) si más adelante guardas el id en el vínculo -> úsalo
        $userFichaje = null;

        if (!empty($vinculo->user_fichaje_id ?? null)) {
            $userFichaje = UserFichaje::on('mysql_fichajes')->find($vinculo->user_fichaje_id);
        }

        // 2) fallback por email (recomendado mientras no exista el campo en vinculos)
        if (!$userFichaje) {
            $email = mb_strtolower(trim(
                $usuario?->email
                ?? $trabajador?->email
                ?? $usuarioPluton?->email
                ?? $usuarioBuscador?->email
                ?? $trabajadorBuscador?->email
                ?? $userCronos?->email
                ?? $userSemillas?->email
                ?? $userStore?->email
                ?? $userZeus?->email
                ?? ''
            ));

            if ($email !== '') {
                $userFichaje = UserFichaje::on('mysql_fichajes')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->first();
            }
        }

        return view('usuarios.edit_unificado', [
            'usuario'            => $usuario,
            'trabajador'         => $trabajador,
            'usuarioPluton'      => $usuarioPluton,
            'usuarioBuscador'    => $usuarioBuscador,
            'trabajadorBuscador' => $trabajadorBuscador,
            'userCronos'         => $userCronos,
            'userSemillas'       => $userSemillas,
            'userStore'          => $userStore,
            'userZeus'           => $userZeus,
            'userFichaje'        => $userFichaje,

            'vinculo'            => $vinculo,
        ]);
    }
    public function exportExcel(Request $request)
    {
        // reutiliza tus filtros actuales
        $search = $request->input('search');
        $activo = $request->input('activo');
        $sort   = $request->input('sort', 'nombre');
        $dir    = $request->input('dir', 'asc');

        // OJO: aquí lo ideal es reutilizar EXACTAMENTE la misma colección que ya generas en index
        // Para no duplicar lógica, puedes extraer tu build a un método privado.
        $todos = $this->buildTrabajadoresPolifoniaCollection($search, $activo, $sort, $dir);

        $filename = 'trabajadores_polifonia_' . now()->format('Y_m_d_His') . '.xlsx';

        return Excel::download(new TrabajadoresPolifoniaExport($todos), $filename);
    }

    private function buildTrabajadoresPolifoniaCollection($search, $activo, $sort, $dir)
    {
        $vinculos = UsuarioVinculado::with([
            'trabajador:id,nombre,email,activo'
        ])->get();

        $vinculados = $vinculos->map(function ($v) {
            $r = $v->trabajador;
            if (!$r) return null;
            $r->uuid = $v->uuid;
            $r->tipo = 'trabajador';
            return $r;
        })->filter();

        $trabajadores = TrabajadorPolifonia::whereNotIn('id', $vinculos->pluck('trabajador_id')->filter())
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
            ->get()
            ->each(fn($t) => $t->tipo = 'trabajador');

        $todos = $vinculados->concat($trabajadores);

        if ($activo !== null && $activo !== '') {
            $todos = $todos->filter(fn($r) => (int)($r->activo ?? 0) === (int)$activo);
        }

        if ($search) {
            $s = mb_strtolower($search);
            $todos = $todos->filter(fn($r) => str_contains(mb_strtolower($r->nombre ?? ''), $s));
        }

        // sort
        $keyFn = match ($sort) {
            'email' => fn($r) => mb_strtolower($r->email ?? ''),
            'activo' => fn($r) => (int)($r->activo ?? 0),
            'vinculado' => fn($r) => !empty($r->uuid) ? 1 : 0,
            default => fn($r) => mb_strtolower($r->nombre ?? ''),
        };

        $todos = $todos->sortBy($keyFn);
        if ($dir === 'desc') $todos = $todos->reverse()->values();

        return $todos->values();
    }

    public function showVinculacionManualConDatos($uuid)
    {
        $vinculo = UsuarioVinculado::where('uuid', $uuid)->firstOrFail();

        $usuario            = Usuario::find($vinculo->usuario_id);
        $trabajador         = TrabajadorPolifonia::on('mysql_polifonia')->find($vinculo->trabajador_id);
        $usuarioPluton      = UserPluton::on('mysql_pluton')->find($vinculo->pluton_id);
        $userBuscador       = UserBuscador::on('mysql_buscador')->find($vinculo->user_buscador_id);
        $workerBuscador     = WorkerBuscador::on('mysql_buscador')->find($vinculo->worker_buscador_id);
        $userCronos         = UserCronos::on('mysql_cronos')->find($vinculo->user_cronos_id);
        $userSemillas       = UserSemillas::on('mysql_semillas')->find($vinculo->user_semillas_id);
        $userStore          = UserStore::on('mysql_store')->find($vinculo->user_store_id);
        $userZeus           = UserZeus::on('mysql_zeus')->find($vinculo->user_zeus_id);

        // ✅ Guardar preseleccionados en sesión
        session([
            'usuario_preseleccionado'          => $usuario?->id,
            'trabajador_preseleccionado'       => $trabajador?->id,
            'pluton_preseleccionado'           => $usuarioPluton?->id,
            'user_buscador_preseleccionado'    => $userBuscador?->id,
            'worker_buscador_preseleccionado'  => $workerBuscador?->id,
            'user_cronos_preseleccionado'      => $userCronos?->id,
            'user_semillas_preseleccionado'    => $userSemillas?->id,
            'user_store_preseleccionado'       => $userStore?->id,
            'user_zeus_preseleccionado'       => $userZeus?->id,
            'email_preseleccionado'            => $usuario?->email ?? $trabajador?->email ?? $usuarioPluton?->email ?? null,
        ]);

        return view('usuarios.vincular', [
            'uuid'                  => $uuid,
            'usuarios'              => Usuario::orderBy('nombre')->get(),
            'trabajadores'          => TrabajadorPolifonia::on('mysql_polifonia')->orderBy('nombre')->get(),
            'usuariosPluton'        => UserPluton::on('mysql_pluton')->orderBy('nombre')->get(),
            'usuariosBuscador'      => UserBuscador::on('mysql_buscador')->orderBy('name')->get(),
            'trabajadoresBuscador'  => WorkerBuscador::on('mysql_buscador')->orderBy('name')->get(),
            'userCronos'            => UserCronos::on('mysql_cronos')->orderBy('name')->get(),
            'userSemillas'          => UserSemillas::on('mysql_semillas')->orderBy('name')->get(),
            'userStore'             => UserStore::on('mysql_store')->orderBy('name')->get(),
            'userZeus'              => UserZeus::on('mysql_zeus')->orderBy('name')->get(),
            'usuario'               => $usuario,
            'trabajador'            => $trabajador,
            'usuarioPluton'         => $usuarioPluton,
            'userBuscador'          => $userBuscador,
            'workerBuscador'        => $workerBuscador,
            'emailSugerido'         => $usuario?->email ?? $trabajador?->email ?? $usuarioPluton?->email ?? null,
        ]);
    }

    public function getDays(Request $r, int $trabajador)
    {
        $calendarYear = (int) ($r->query('calendar_year')
            ?? $r->query('vacation_year')   // compat con front viejo
            ?? date('Y'));

        return response()->json([
            'ok' => true,
            'data' => $this->buildAusenciasPayloadByCalendarYear($trabajador, $calendarYear),
        ]);
    }

    private function buildAusenciasPayloadByCalendarYear(int $trabajadorId, int $calendarYear): array
    {
        $rows = DB::connection('mysql_polifonia')->table('trabajadores_dias')
            ->select('fecha', 'tipo', 'vacation_year')
            ->where('trabajador_id', $trabajadorId)
            ->whereYear('fecha', $calendarYear)   // ✅ CLAVE: por año de fecha
            ->get();

        $out = [
            'vacaciones' => ['count'=>0,'items'=>[]],
            'permiso'    => ['count'=>0,'items'=>[]],
            'baja'       => ['count'=>0,'items'=>[]],
        ];

        foreach ($rows as $r) {
            $key = $r->tipo === 'V' ? 'vacaciones' : ($r->tipo === 'P' ? 'permiso' : 'baja');
            $out[$key]['items'][] = [
                'fecha' => (string)$r->fecha,
                'bucket_year' => (int)$r->vacation_year, // por si quieres mostrarlo
            ];
            $out[$key]['count']++;
        }

        return $out;
    }

    public function storeDays(Request $r, int $trabajador)
    {
        $data = $r->validate([
            'calendar_year' => 'required|integer',
            'tipo' => 'required|in:V,P,B',
            'from' => 'required|date',
            'to' => 'required|date',
            'mode' => 'required|in:add,remove',
            'bucket_year' => 'nullable|integer', // ✅
        ]);

        $bucketYear = $data['tipo'] === 'V'
            ? (int)($data['bucket_year'] ?? $data['calendar_year'])
            : (int)$data['calendar_year']; // para P/B puedes dejarlo igual o ignorarlo

        $period = CarbonPeriod::create($data['from'], $data['to']);
        $dates = collect($period)->map(fn($d) => $d->format('Y-m-d'))->values();

        DB::connection('mysql_polifonia')->transaction(function () use ($trabajador, $data, $dates, $bucketYear) {

            if ($data['mode'] === 'remove') {
                DB::connection('mysql_polifonia')->table('trabajadores_dias')
                    ->where('trabajador_id', $trabajador)
                    ->where('tipo', $data['tipo'])
                    ->where('vacation_year', $bucketYear)
                    ->whereIn('fecha', $dates)
                    ->delete();
                return;
            }

            // add: NO pisar ninguna ausencia existente (por FECHA, da igual el bucket)
            $occupied = DB::connection('mysql_polifonia')->table('trabajadores_dias')
                ->where('trabajador_id', $trabajador)
                ->whereIn('fecha', $dates)
                ->pluck('fecha')
                ->map(fn($d) => is_string($d) ? $d : $d->format('Y-m-d'))
                ->all();

            $occupiedSet = array_flip($occupied);

            $toInsert = [];
            foreach ($dates as $date) {
                if (isset($occupiedSet[$date])) continue;

                $toInsert[] = [
                    'trabajador_id' => $trabajador,
                    'fecha' => $date,
                    'vacation_year' => $bucketYear, // ✅ imputación
                    'tipo' => $data['tipo'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($toInsert)) {
                DB::connection('mysql_polifonia')->table('trabajadores_dias')->insertOrIgnore($toInsert);
            }
        });

        return response()->json([
            'ok' => true,
            'data' => $this->buildAusenciasPayloadByCalendarYear($trabajador, (int)$data['calendar_year']),
        ]);
    }

    private function buildAusenciasPayload(int $trabajadorId, int $year): array
    {
        $rows = DB::connection('mysql_polifonia')->table('trabajadores_dias')
            ->select('fecha', 'tipo')
            ->where('trabajador_id', $trabajadorId)
            ->where('vacation_year', $year)
            ->get();

        $out = [
            'vacaciones' => ['count'=>0,'items'=>[]],
            'permiso'    => ['count'=>0,'items'=>[]],
            'baja'       => ['count'=>0,'items'=>[]],
        ];

        foreach ($rows as $r) {
            $key = $r->tipo === 'V' ? 'vacaciones' : ($r->tipo === 'P' ? 'permiso' : 'baja');
            $out[$key]['items'][] = ['fecha' => (string)$r->fecha];
            $out[$key]['count']++;
        }

        return $out;
    }

    public function vacaciones(Request $request, $trabajadorId)
    {
        $year = (int) $request->query('vacation_year', now()->year);
        $tipo = strtoupper($request->query('tipo', 'V')); // V | P | B

        // 1) Datos trabajador
        $t = TrabajadorPolifonia::query()->findOrFail($trabajadorId);

        $empresa = $t->empresa;
        $nombre  = $t->nombre;
        $nif     = $t->nif;

        // 2) Días base del año seleccionado (por vacation_year)
        $diasBase = TrabajadorDia::query()
            ->where('trabajador_id', $trabajadorId)
            ->where('vacation_year', $year)
            ->where('tipo', $tipo)
            ->orderBy('fecha')
            ->pluck('fecha');

        if ($diasBase->isEmpty()) {
            abort(404, 'No hay ausencias registradas');
        }

        // 2.1) ✅ Arrastrar días consecutivos posteriores (aunque sean del año siguiente y/o otro vacation_year)
        $last = Carbon::parse($diasBase->last())->startOfDay();

        // Límite razonable (por ejemplo 31 días) para no traerte medio año si alguien mete mal datos
        $spill = TrabajadorDia::query()
            ->where('trabajador_id', $trabajadorId)
            ->where('tipo', $tipo)
            ->where('fecha', '>', $last->format('Y-m-d'))
            ->where('fecha', '<=', $last->copy()->addDays(31)->format('Y-m-d'))
            ->orderBy('fecha')
            ->pluck('fecha');

        // Añadir solo mientras sean consecutivos día a día
        $dias = $diasBase->map(fn($f) => Carbon::parse($f)->startOfDay());

        $cursor = $last->copy();
        foreach ($spill as $f) {
            $d = Carbon::parse($f)->startOfDay();
            if ($d->equalTo($cursor->copy()->addDay())) {
                $dias->push($d);
                $cursor = $d->copy();
            } else {
                break;
            }
        }

        // 3) Convertir a rangos consecutivos
        $rangos = [];
        $inicio = null;
        $fin = null;

        foreach ($dias as $d) {
            if ($inicio === null) {
                $inicio = $d->copy();
                $fin    = $d->copy();
                continue;
            }

            if ($d->equalTo($fin->copy()->addDay())) {
                $fin = $d->copy();
            } else {
                $rangos[] = ['inicio' => $inicio->format('d/m/Y'), 'fin' => $fin->format('d/m/Y')];
                $inicio = $d->copy();
                $fin    = $d->copy();
            }
        }

        if ($inicio !== null) {
            $rangos[] = ['inicio' => $inicio->format('d/m/Y'), 'fin' => $fin->format('d/m/Y')];
        }

        // 4) Texto humano
        $tipoTexto = match ($tipo) {
            'V' => 'vacaciones',
            'P' => 'permisos',
            'B' => 'bajas',
            default => 'ausencias',
        };

        // 5) Datos vista (✅ el año del documento sigue siendo $year)
        $data = [
            'empresa'    => $empresa,
            'trabajador' => $nombre,
            'dni'        => $nif,
            'tipo'       => $tipoTexto,
            'anyo'       => $year,
            'rangos'     => $rangos,
            'fecha'      => now()->format('d/m/Y'),
        ];

        $pdf = Pdf::loadView('pdfs.pdp_vacaciones', $data);
        return $pdf->stream("{$tipoTexto}_{$trabajadorId}_{$year}.pdf");
    }

}
