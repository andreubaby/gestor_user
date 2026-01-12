<?php

namespace App\Services;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FichajesDiariosService
{
    public function handle(Request $request): array
    {
        $date = $request->input('date') ?: now()->format('Y-m-d'); // YYYY-mm-dd
        $groupId = $request->input('grupo'); // opcional

        // 1) trabajadores (polifonía) - si hay grupo, filtramos por pivot en mysql
        $trabajadoresQuery = TrabajadorPolifonia::query()
            ->select(['id', 'nombre', 'email', 'activo'])
            ->orderBy('nombre');

        if ($groupId !== null && $groupId !== '') {
            $ids = DB::connection('mysql')
                ->table('group_trabajador')
                ->where('group_id', (int)$groupId)
                ->pluck('trabajador_id')
                ->map(fn($v) => (int)$v)
                ->all();

            // si grupo vacío -> lista vacía
            if (empty($ids)) {
                return [
                    'date'    => $date,
                    'groupId' => $groupId,
                    'rows'    => collect(),
                    'stats'   => ['total'=>0,'con_fichaje'=>0,'sin_fichaje'=>0,'solo_entrada'=>0,'solo_salida'=>0],
                ];
            }

            $trabajadoresQuery->whereIn('id', $ids);
        }

        $trabajadores = $trabajadoresQuery->get();

        // 2) vinculos trabajador_id -> user_fichaje_id
        $vinculos = UsuarioVinculado::query()
            ->whereIn('trabajador_id', $trabajadores->pluck('id'))
            ->get(['trabajador_id', 'user_fichaje_id']);

        $mapTrabajadorToFichajes = $vinculos
            ->filter(fn($v) => !empty($v->user_fichaje_id))
            ->mapWithKeys(fn($v) => [(int)$v->trabajador_id => (int)$v->user_fichaje_id]);

        $fichajesUserIds = $mapTrabajadorToFichajes->values()->unique()->values();

        // 3) punches de ese día en mysql_fichajes
        // happened_at es datetime: filtramos por rango [00:00, 23:59:59]
        $start = Carbon::parse($date)->startOfDay()->format('Y-m-d H:i:s');
        $end   = Carbon::parse($date)->endOfDay()->format('Y-m-d H:i:s');

        $punches = collect();

        if ($fichajesUserIds->isNotEmpty()) {
            $punches = DB::connection('mysql_fichajes')
                ->table('punches')
                ->select(['id','user_id','type','mood','happened_at','is_manual','note'])
                ->whereIn('user_id', $fichajesUserIds->all())
                ->whereBetween('happened_at', [$start, $end])
                ->orderBy('happened_at')
                ->get();
        }

        // Agrupar punches por user_id
        $punchesByUser = $punches->groupBy('user_id');

        // 4) construir filas por trabajador
        $rows = $trabajadores->map(function ($t) use ($mapTrabajadorToFichajes, $punchesByUser, $date) {
            $trabajadorId = (int)$t->id;
            $fu = $mapTrabajadorToFichajes->get($trabajadorId); // user_id en fichajes

            $userPunches = $fu ? ($punchesByUser->get($fu) ?? collect()) : collect();

            $entradas = $userPunches->filter(fn($p) => strtolower((string)$p->type) === 'in');
            $salidas  = $userPunches->filter(fn($p) => strtolower((string)$p->type) === 'out');

            $firstIn  = $entradas->first()?->happened_at;
            $lastOut  = $salidas->last()?->happened_at;

            // “ha fichado” si tiene al menos 1 punch
            $haFichado = $userPunches->count() > 0;

            return (object)[
                'date'         => $date,
                'trabajador_id'=> $trabajadorId,
                'nombre'       => $t->nombre,
                'email'        => $t->email,
                'activo'       => (int)($t->activo ?? 0),

                'vinculado_fichajes' => (bool)$fu,
                'user_fichajes_id'   => $fu,

                'count'        => $userPunches->count(),
                'first_in'     => $firstIn ? Carbon::parse($firstIn)->format('H:i') : null,
                'last_out'     => $lastOut ? Carbon::parse($lastOut)->format('H:i') : null,

                'solo_entrada' => $entradas->count() > 0 && $salidas->count() === 0,
                'solo_salida'  => $salidas->count() > 0 && $entradas->count() === 0,

                // para un “detalle rápido” si luego quieres desplegable:
                'punches' => $userPunches->map(function ($p) {
                    return [
                        'type' => strtolower((string)$p->type) === 'in' ? 'entrada' : 'salida',
                        'hora' => $p->happened_at ? \Illuminate\Support\Carbon::parse($p->happened_at)->format('H:i') : '—',
                        'mood' => is_null($p->mood) ? null : (int)$p->mood,
                        'is_manual' => (int)($p->is_manual ?? 0),
                        'note' => $p->note,
                    ];
                })->values()->all(),
            ];
        });

        // 5) stats
        $conFichaje = $rows->filter(fn($r) => $r->count > 0)->count();
        $soloEntrada = $rows->filter(fn($r) => $r->solo_entrada)->count();
        $soloSalida  = $rows->filter(fn($r) => $r->solo_salida)->count();

        $stats = [
            'total'       => $rows->count(),
            'con_fichaje' => $conFichaje,
            'sin_fichaje' => $rows->count() - $conFichaje,
            'solo_entrada'=> $soloEntrada,
            'solo_salida' => $soloSalida,
        ];

        return [
            'date'    => $date,
            'groupId' => $groupId,
            'rows'    => $rows,
            'stats'   => $stats,
        ];
    }
}
