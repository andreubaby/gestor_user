<?php

namespace App\Services;

use App\Models\Fichar;         // fallback antiguo
use App\Models\Punch;          // nuevo sistema (punches)
use App\Models\UserFichaje;    // users en mysql_fichajes
use App\Models\UserTrabajador; // users en sistema antiguo
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BienestarService
{
    /**
     * Adjunta $weeks valores en $r->bienestar_ultimos (para no tocar la Blade):
     * - Preferencia: Punch.mood (mysql_fichajes)
     * - Fallback: Fichar.bienestar
     *
     * Si prefieres cambiar el nombre a mood_ultimos en la Blade, dime y lo adapto.
     */
    public function attachMoodUltimasSemanas(Collection $items, int $weeks = 4): Collection
    {
        $emails = $items
            ->map(fn($r) => mb_strtolower(trim($r->email ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return $items->map(function ($r) use ($weeks) {
                $r->bienestar_ultimos = array_fill(0, $weeks, null);
                return $r;
            });
        }

        $moodByEmail = $this->moodUnificadoPorEmail($emails, $weeks);

        return $items->map(function ($r) use ($moodByEmail, $weeks) {
            $emailKey = mb_strtolower(trim($r->email ?? ''));
            $r->bienestar_ultimos = $moodByEmail->get($emailKey, array_fill(0, $weeks, null));
            return $r;
        });
    }

    /**
     * Devuelve: [ email => [semana0, semana1, semana2, semana3] ]
     * semana0 = semana actual (lunes-domingo)
     */
    public function moodUnificadoPorEmail(Collection $emails, int $weeks = 4): Collection
    {
        if ($emails->isEmpty()) return collect();

        $startOldestWeek = now()->startOfWeek(Carbon::MONDAY)->subWeeks($weeks - 1)->startOfDay();
        $endThisWeek     = now()->endOfWeek(Carbon::MONDAY)->endOfDay();

        $weekKeys = collect(range(0, $weeks - 1))->map(function ($i) {
            return now()->startOfWeek(Carbon::MONDAY)->subWeeks($i)->startOfDay()->format('Y-m-d');
        });

        // =========================
        // A) NUEVO SISTEMA: Punch (mood, happened_at)
        // =========================
        $usersNew = UserFichaje::query()
            ->select(['id', 'email'])
            ->whereIn('email', $emails->all())
            ->get();

        $newEmailToId = $usersNew->mapWithKeys(fn($u) => [
            mb_strtolower(trim($u->email ?? '')) => (int) $u->id
        ]);

        $newUserIds = $usersNew->pluck('id')->map(fn($v) => (int)$v)->values()->all();

        $punchAvgByUserWeek = collect();

        if (!empty($newUserIds)) {
            $punches = Punch::query()
                ->select(['user_id', 'mood', 'happened_at'])
                ->whereIn('user_id', $newUserIds)
                ->whereBetween('happened_at', [$startOldestWeek, $endThisWeek])
                ->whereNotNull('mood')
                ->get();

            $punchAvgByUserWeek = $punches
                ->groupBy(function ($row) {
                    $monday = Carbon::parse($row->happened_at)->startOfWeek(Carbon::MONDAY)->startOfDay();
                    return (int)$row->user_id . '|' . $monday->format('Y-m-d');
                })
                ->map(fn($g) => $g->avg('mood'));
        }

        // =========================
        // B) FALLBACK: Fichar (bienestar, created_at)
        // =========================
        $usersOld = UserTrabajador::query()
            ->select(['id', 'email'])
            ->whereIn('email', $emails->all())
            ->get();

        $oldEmailToId = $usersOld->mapWithKeys(fn($u) => [
            mb_strtolower(trim($u->email ?? '')) => (int) $u->id
        ]);

        $oldUserIds = $usersOld->pluck('id')->map(fn($v) => (int)$v)->values()->all();

        $ficharAvgByUserWeek = collect();

        if (!empty($oldUserIds)) {
            $fichajes = Fichar::query()
                ->select(['user_id', 'bienestar', 'created_at'])
                ->whereIn('user_id', $oldUserIds)
                ->whereBetween('created_at', [$startOldestWeek, $endThisWeek])
                ->whereNotNull('bienestar')
                ->get();

            $ficharAvgByUserWeek = $fichajes
                ->groupBy(function ($row) {
                    $monday = Carbon::parse($row->created_at)->startOfWeek(Carbon::MONDAY)->startOfDay();
                    return (int)$row->user_id . '|' . $monday->format('Y-m-d');
                })
                ->map(fn($g) => $g->avg('bienestar')); // bienestar como mood equivalente
        }

        // =========================
        // C) Resultado final por email (Punch primero, si no -> Fichar)
        // =========================
        return $emails->mapWithKeys(function ($email) use (
            $weekKeys,
            $newEmailToId,
            $punchAvgByUserWeek,
            $oldEmailToId,
            $ficharAvgByUserWeek
        ) {
            $emailKey = mb_strtolower(trim($email));

            $newId = $newEmailToId->get($emailKey);
            $oldId = $oldEmailToId->get($emailKey);

            $vals = $weekKeys->map(function ($weekMonday) use (
                $newId,
                $punchAvgByUserWeek,
                $oldId,
                $ficharAvgByUserWeek
            ) {
                // 1) Punch.mood
                if ($newId) {
                    $avg = $punchAvgByUserWeek->get($newId . '|' . $weekMonday);
                    if ($avg !== null) {
                        $lvl = (int) round($avg);
                        return max(1, min(4, $lvl));
                    }
                }

                // 2) Fallback Fichar.bienestar
                if ($oldId) {
                    $avg = $ficharAvgByUserWeek->get($oldId . '|' . $weekMonday);
                    if ($avg !== null) {
                        $lvl = (int) round($avg);
                        return max(1, min(4, $lvl));
                    }
                }

                return null;
            })->values()->all();

            return [$emailKey => $vals];
        });
    }
}
