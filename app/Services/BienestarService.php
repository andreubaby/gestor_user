<?php

namespace App\Services;

use App\Models\Fichar;
use App\Models\Punch;
use App\Models\UsuarioVinculado;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BienestarService
{
    /**
     * Adjunta $weeks valores de bienestar en $r->bienestar_ultimos.
     *
     * Resolución de IDs:
     *  - Punch (nuevo sistema): trabajador_id → user_fichaje_id via UsuarioVinculado
     *  - Fichar (fallback):     fichar.user_id = trabajador_id (Polifonía) directamente
     */
    public function attachMoodUltimasSemanas(Collection $items, int $weeks = 4): Collection
    {
        $workerIds = $items
            ->map(fn($r) => (int)($r->id ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($workerIds->isEmpty()) {
            return $items->map(function ($r) use ($weeks) {
                $r->bienestar_ultimos = array_fill(0, $weeks, null);
                return $r;
            });
        }

        $moodByWorkerId = $this->moodPorWorkerId($workerIds, $weeks);

        return $items->map(function ($r) use ($moodByWorkerId, $weeks) {
            $wid = (int)($r->id ?? 0);
            $r->bienestar_ultimos = $moodByWorkerId->get($wid, array_fill(0, $weeks, null));
            return $r;
        });
    }

    /**
     * Devuelve: [ trabajador_id => [semana0, semana1, semana2, semana3] ]
     * semana0 = semana actual, semana1 = hace 1 semana, etc.
     */
    public function moodPorWorkerId(Collection $workerIds, int $weeks = 4): Collection
    {
        if ($workerIds->isEmpty()) return collect();

        $startOldestWeek = now()->startOfWeek(Carbon::MONDAY)->subWeeks($weeks - 1)->startOfDay();
        $endThisWeek     = now()->endOfWeek(Carbon::MONDAY)->endOfDay();

        $weekKeys = collect(range(0, $weeks - 1))->map(function ($i) {
            return now()->startOfWeek(Carbon::MONDAY)->subWeeks($i)->startOfDay()->format('Y-m-d');
        });

        // =========================
        // A) NUEVO SISTEMA: Punch (mood, happened_at)
        //    trabajador_id → user_fichaje_id via UsuarioVinculado
        // =========================
        $vinculos = UsuarioVinculado::whereIn('trabajador_id', $workerIds->all())
            ->whereNotNull('user_fichaje_id')
            ->pluck('user_fichaje_id', 'trabajador_id')
            ->map(fn($v) => (int)$v);

        $fichajeUserIds = $vinculos->values()->filter()->unique()->values()->all();

        $punchAvgByUserWeek = collect();

        if (!empty($fichajeUserIds)) {
            $punches = Punch::query()
                ->select(['user_id', 'mood', 'happened_at'])
                ->whereIn('user_id', $fichajeUserIds)
                ->whereBetween('happened_at', [$startOldestWeek, $endThisWeek])
                ->whereNotNull('mood')
                ->get();

            $punchAvgByUserWeek = $punches
                ->groupBy(function ($row) {
                    $monday = Carbon::parse($row->happened_at)
                        ->startOfWeek(Carbon::MONDAY)
                        ->startOfDay();
                    return (int)$row->user_id . '|' . $monday->format('Y-m-d');
                })
                ->map(fn($g) => $g->avg('mood'));
        }

        // =========================
        // B) FALLBACK: Fichar (bienestar, fecha_hora)
        //    fichar.user_id = trabajador_id de Polifonía directamente
        // =========================
        $ficharAvgByWorkerWeek = collect();

        $fichajes = Fichar::query()
            ->select(['user_id', 'bienestar', 'fecha_hora'])
            ->whereIn('user_id', $workerIds->all())
            ->whereBetween('fecha_hora', [$startOldestWeek, $endThisWeek])
            ->whereNotNull('bienestar')
            ->get();

        if ($fichajes->isNotEmpty()) {
            $ficharAvgByWorkerWeek = $fichajes
                ->groupBy(function ($row) {
                    $monday = Carbon::parse($row->fecha_hora)
                        ->startOfWeek(Carbon::MONDAY)
                        ->startOfDay();
                    return (int)$row->user_id . '|' . $monday->format('Y-m-d');
                })
                ->map(fn($g) => $g->avg('bienestar'));
        }

        // =========================
        // C) Resultado final por trabajador_id (Punch primero, Fichar como fallback)
        // =========================
        return $workerIds->mapWithKeys(function ($workerId) use (
            $weekKeys,
            $vinculos,
            $punchAvgByUserWeek,
            $ficharAvgByWorkerWeek
        ) {
            $fichajeUserId = $vinculos->get($workerId);

            $vals = $weekKeys->map(function ($weekMonday) use (
                $workerId,
                $fichajeUserId,
                $punchAvgByUserWeek,
                $ficharAvgByWorkerWeek
            ) {
                // 1) Punch.mood (nuevo sistema, via UsuarioVinculado)
                if ($fichajeUserId) {
                    $avg = $punchAvgByUserWeek->get($fichajeUserId . '|' . $weekMonday);
                    if ($avg !== null) {
                        return max(1, min(4, (int) round($avg)));
                    }
                }

                // 2) Fallback Fichar.bienestar (sistema antiguo, user_id = trabajador_id)
                $avg = $ficharAvgByWorkerWeek->get($workerId . '|' . $weekMonday);
                if ($avg !== null) {
                    return max(1, min(4, (int) round($avg)));
                }

                return null;
            })->values()->all();

            return [(int)$workerId => $vals];
        });
    }
}
