<?php

namespace App\Services;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FichajesDiariosService
{
    public function handle(Request $request): array
    {
        $date    = $request->input('date') ?: now()->format('Y-m-d'); // YYYY-mm-dd
        $groupId = $request->input('grupo'); // opcional

        // ✅ estado '' | 'activo' | 'inactivo'
        $estado = (string)($request->input('estado', '') ?? '');
        if (!in_array($estado, ['', 'activo', 'inactivo'], true)) {
            $estado = '';
        }

        /**
         * =========================
         * 1) TRABAJADORES (polifonía) + filtros estado/grupo
         * =========================
         */
        $trabajadoresQuery = TrabajadorPolifonia::query()
            ->select(['id', 'nombre', 'email', 'activo'])
            ->orderBy('nombre');

        if ($estado === 'activo') {
            $trabajadoresQuery->where('activo', 1);
        } elseif ($estado === 'inactivo') {
            $trabajadoresQuery->where('activo', 0);
        }

        // Si hay grupo, filtramos por pivot (BD principal mysql)
        if ($groupId !== null && $groupId !== '') {
            $ids = DB::connection('mysql')
                ->table('group_trabajador')
                ->where('group_id', (int)$groupId)
                ->pluck('trabajador_id')
                ->map(fn ($v) => (int)$v)
                ->all();

            if (empty($ids)) {
                return [
                    'date'    => $date,
                    'groupId' => $groupId,
                    'estado'  => $estado,
                    'rows'    => collect(),
                    'stats'   => [
                        'total' => 0,
                        'con_fichaje' => 0,
                        'sin_fichaje' => 0,
                        'solo_entrada' => 0,
                        'solo_salida' => 0
                    ],
                ];
            }

            $trabajadoresQuery->whereIn('id', $ids);
        }

        $trabajadores = $trabajadoresQuery->get();

        /**
         * =========================
         * 2) VÍNCULOS trabajador_id -> user_fichaje_id (BD principal)
         * =========================
         */
        $vinculos = UsuarioVinculado::query()
            ->whereIn('trabajador_id', $trabajadores->pluck('id'))
            ->get(['trabajador_id', 'user_fichaje_id']);

        $mapTrabajadorToFichajes = $vinculos
            ->filter(fn ($v) => !empty($v->user_fichaje_id))
            ->mapWithKeys(fn ($v) => [(int)$v->trabajador_id => (int)$v->user_fichaje_id]);

        $fichajesUserIds = $mapTrabajadorToFichajes->values()->unique()->values();

        /**
         * =========================
         * 3) RANGO DEL DÍA
         * =========================
         */
        $start = Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
        $end   = Carbon::parse($date)->endOfDay()->format('Y-m-d H:i:s');

        /**
         * =========================
         * A) FICHAJES NUEVOS (mysql_fichajes.punches)
         * =========================
         */
        $punches = collect();

        if ($fichajesUserIds->isNotEmpty()) {
            $punches = DB::connection('mysql_fichajes')
                ->table('punches')
                ->select(['id', 'user_id', 'type', 'mood', 'happened_at', 'is_manual', 'note'])
                ->whereIn('user_id', $fichajesUserIds->all())
                ->whereBetween('happened_at', [$start, $end])
                ->orderBy('happened_at')
                ->get();
        }

        // Normalizar punches nuevos (por trabajador_id)
        $punchesFichajes = $punches
            ->map(function ($p) {
                $type = strtolower((string)($p->type ?? ''));

                return [
                    'origen'        => 'fichajes',
                    'trabajador_id' => null, // se asigna luego
                    'type'          => $type === 'in' ? 'in' : ($type === 'out' ? 'out' : $type),
                    'hora'          => $p->happened_at ? Carbon::parse($p->happened_at)->format('H:i') : '—',
                    'datetime'      => $p->happened_at,
                    'mood'          => is_null($p->mood) ? null : (int)$p->mood,
                    'is_manual'     => (int)($p->is_manual ?? 0),
                    'note'          => $p->note ?? null,
                    'raw_user_id'   => (int)$p->user_id,
                ];
            })
            ->values();

        // user_id fichajes -> trabajador_id
        $mapFichajesToTrabajador = $mapTrabajadorToFichajes->flip(); // [user_fichaje_id => trabajador_id]

        $punchesFichajes = $punchesFichajes
            ->map(function ($p) use ($mapFichajesToTrabajador) {
                $tid = $mapFichajesToTrabajador->get($p['raw_user_id']);
                if ($tid) {
                    $p['trabajador_id'] = (int)$tid;
                }
                return $p;
            })
            ->filter(fn ($p) => !empty($p['trabajador_id']))
            ->values();

        /**
         * =========================
         * B) FICHAJES ANTIGUOS (BD trabajadores / mysql_polifonia)
         * =========================
         *
         * Ajusta si tu tabla no se llama "fichar" o si los campos difieren.
         * Esperado:
         * - user_id (== trabajador_id)
         * - tipo: I/F (Entrada/Salida)
         * - fecha_hora (datetime)
         * - bienestar (opcional)
         */
        $fichajesTrabajadores = DB::connection('mysql_trabajadores')
            ->table('fichar') // ⚠️ ajusta si el nombre real difiere
            ->select(['user_id', 'tipo', 'fecha_hora', 'bienestar'])
            ->whereIn('user_id', $trabajadores->pluck('id'))
            ->whereBetween('fecha_hora', [$start, $end])
            ->orderBy('fecha_hora')
            ->get()
            ->map(function ($f) {
                $tipo = strtoupper((string)($f->tipo ?? ''));

                $type = match ($tipo) {
                    'I', 'IN', 'ENTRADA' => 'in',
                    'F', 'OUT', 'SALIDA' => 'out',
                    default              => 'fichaje',
                };

                return [
                    'origen'        => 'trabajadores',
                    'trabajador_id' => (int)$f->user_id,
                    'type'          => $type,
                    'hora'          => $f->fecha_hora
                        ? Carbon::parse($f->fecha_hora)->format('H:i')
                        : '—',
                    'datetime'      => $f->fecha_hora,
                    'mood'          => is_null($f->bienestar) ? null : (int)$f->bienestar,
                    'is_manual'     => null,
                    'note'          => null,
                    'raw_user_id'   => null,
                ];
            })
            ->values();

        /**
         * =========================
         * UNIFICAR AMBAS FUENTES
         * =========================
         */
        $allPunches = $punchesFichajes
            ->concat($fichajesTrabajadores)
            ->sortBy('datetime')
            ->values();

        $punchesByTrabajador = $allPunches->groupBy('trabajador_id');

        /**
         * =========================
         * 4) CONSTRUIR FILAS (por trabajador)
         * =========================
         */
        $rows = $trabajadores->map(function ($t) use ($mapTrabajadorToFichajes, $punchesByTrabajador, $date) {
            $trabajadorId = (int)$t->id;
            $fu = $mapTrabajadorToFichajes->get($trabajadorId); // user_id en fichajes (puede ser null)

            $userPunches = $punchesByTrabajador->get($trabajadorId) ?? collect();

            $entradas = $userPunches->filter(fn ($p) => ($p['type'] ?? '') === 'in');
            $salidas  = $userPunches->filter(fn ($p) => ($p['type'] ?? '') === 'out');

            $firstIn = $entradas->first()['hora'] ?? null;
            $lastOut = $salidas->last()['hora'] ?? null;

            return (object)[
                'date'          => $date,
                'trabajador_id' => $trabajadorId,
                'nombre'        => $t->nombre,
                'email'         => $t->email,
                'activo'        => (int)($t->activo ?? 0),

                // aunque no esté vinculado en "fichajes", puede fichar en la BD vieja
                'vinculado_fichajes' => (bool)$fu,
                'user_fichajes_id'   => $fu,

                'count'    => $userPunches->count(),
                'first_in' => $firstIn,
                'last_out' => $lastOut,

                'solo_entrada' => $entradas->count() > 0 && $salidas->count() === 0,
                'solo_salida'  => $salidas->count() > 0 && $entradas->count() === 0,

                'punches' => $userPunches->map(function ($p) {
                    $type = (string)($p['type'] ?? '');
                    return [
                        'type'      => $type === 'in' ? 'entrada' : ($type === 'out' ? 'salida' : $type),
                        'hora'      => $p['hora'] ?? '—',
                        'mood'      => $p['mood'] ?? null,
                        'is_manual' => $p['is_manual'] ?? null,
                        'note'      => $p['note'] ?? null,
                        'origen'    => $p['origen'] ?? null,
                    ];
                })->values()->all(),
            ];
        })->values();

        /**
         * =========================
         * 5) STATS
         * =========================
         */
        $conFichaje  = $rows->filter(fn ($r) => $r->count > 0)->count();
        $soloEntrada = $rows->filter(fn ($r) => (bool)$r->solo_entrada)->count();
        $soloSalida  = $rows->filter(fn ($r) => (bool)$r->solo_salida)->count();

        $stats = [
            'total'        => $rows->count(),
            'con_fichaje'  => $conFichaje,
            'sin_fichaje'  => $rows->count() - $conFichaje,
            'solo_entrada' => $soloEntrada,
            'solo_salida'  => $soloSalida,
        ];

        return [
            'date'    => $date,
            'groupId' => $groupId,
            'estado'  => $estado,
            'rows'    => $rows,
            'stats'   => $stats,
        ];
    }
}
