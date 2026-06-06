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
     *
     * ⚡ OPTIMIZADO: AVG/GROUP BY se calculan en SQL (1-2 queries) en vez de
     * cargar todos los registros en PHP y agrupar en memoria.
     */
    public function moodPorWorkerId(Collection $workerIds, int $weeks = 4): Collection
    {
        if ($workerIds->isEmpty()) return collect();

        $startOldestWeek = now()->startOfWeek(Carbon::MONDAY)->subWeeks($weeks - 1)->startOfDay();
        $endThisWeek     = now()->endOfWeek(Carbon::MONDAY)->endOfDay();

        // Claves de semana (lunes de cada semana, más reciente primero)
        $weekKeys = collect(range(0, $weeks - 1))->map(
            fn($i) => now()->startOfWeek(Carbon::MONDAY)->subWeeks($i)->startOfDay()->format('Y-m-d')
        );

        // ─────────────────────────────────────────────────────────────────────
        // Vinculos trabajador_id → user_fichaje_id
        // ─────────────────────────────────────────────────────────────────────
        $vinculos = UsuarioVinculado::whereIn('trabajador_id', $workerIds->all())
            ->whereNotNull('user_fichaje_id')
            ->pluck('user_fichaje_id', 'trabajador_id')
            ->map(fn($v) => (int)$v);

        $fichajeUserIds = $vinculos->values()->filter()->unique()->values()->all();

        // ─────────────────────────────────────────────────────────────────────
        // A) NUEVO SISTEMA: Punch — AVG en SQL ⚡
        // ─────────────────────────────────────────────────────────────────────
        $punchAvgByUserWeek = collect();

        if (!empty($fichajeUserIds)) {
            $punchRows = Punch::query()
                ->selectRaw("
                    user_id,
                    DATE_SUB(DATE(happened_at), INTERVAL WEEKDAY(happened_at) DAY) AS week_start,
                    ROUND(AVG(mood), 0) AS avg_mood
                ")
                ->whereIn('user_id', $fichajeUserIds)
                ->whereBetween('happened_at', [$startOldestWeek, $endThisWeek])
                ->whereNotNull('mood')
                ->groupByRaw("user_id, DATE_SUB(DATE(happened_at), INTERVAL WEEKDAY(happened_at) DAY)")
                ->get();

            // key: "user_id|YYYY-MM-DD" → avg_mood
            $punchAvgByUserWeek = $punchRows->mapWithKeys(
                fn($row) => [$row->user_id . '|' . $row->week_start => (float)$row->avg_mood]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // B) FALLBACK: Fichar — AVG en SQL ⚡
        // ─────────────────────────────────────────────────────────────────────
        $ficharAvgByWorkerWeek = collect();

        $ficharRows = Fichar::query()
            ->selectRaw("
                user_id,
                DATE_SUB(DATE(fecha_hora), INTERVAL WEEKDAY(fecha_hora) DAY) AS week_start,
                ROUND(AVG(bienestar), 0) AS avg_mood
            ")
            ->whereIn('user_id', $workerIds->all())
            ->whereBetween('fecha_hora', [$startOldestWeek, $endThisWeek])
            ->whereNotNull('bienestar')
            ->groupByRaw("user_id, DATE_SUB(DATE(fecha_hora), INTERVAL WEEKDAY(fecha_hora) DAY)")
            ->get();

        if ($ficharRows->isNotEmpty()) {
            $ficharAvgByWorkerWeek = $ficharRows->mapWithKeys(
                fn($row) => [$row->user_id . '|' . $row->week_start => (float)$row->avg_mood]
            );
        }

        // ─────────────────────────────────────────────────────────────────────
        // C) Resultado final por trabajador_id
        // ─────────────────────────────────────────────────────────────────────
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
                // 1) Punch.mood (nuevo sistema)
                if ($fichajeUserId) {
                    $avg = $punchAvgByUserWeek->get($fichajeUserId . '|' . $weekMonday);
                    if ($avg !== null) {
                        return max(1, min(4, (int) round($avg)));
                    }
                }

                // 2) Fallback Fichar.bienestar
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
