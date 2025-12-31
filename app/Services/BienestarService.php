<?php

namespace App\Services;

use App\Models\Fichar;
use App\Models\UserTrabajador;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BienestarService
{
    public function attachBienestarUltimasSemanas(Collection $items, int $weeks = 4): Collection
    {
        $emails = $items
            ->map(fn($r) => mb_strtolower(trim($r->email ?? '')))
            ->filter()
            ->unique()
            ->values();

        $bienestarByEmail = $this->bienestarPorEmail($emails, $weeks);

        return $items->map(function ($r) use ($bienestarByEmail, $weeks) {
            $emailKey = mb_strtolower(trim($r->email ?? ''));
            $r->bienestar_ultimos = $bienestarByEmail->get($emailKey, array_fill(0, $weeks, null));
            return $r;
        });
    }

    public function bienestarPorEmail(Collection $emails, int $weeks = 4): Collection
    {
        if ($emails->isEmpty()) return collect();

        $usersRemotos = UserTrabajador::query()
            ->select(['id', 'email'])
            ->whereIn('email', $emails->all())
            ->get();

        $userIds = $usersRemotos->pluck('id')->all();

        $startOldestWeek = now()->startOfWeek(Carbon::MONDAY)->subWeeks($weeks - 1)->startOfDay();
        $endThisWeek     = now()->endOfWeek(Carbon::MONDAY)->endOfDay();

        $weekKeys = collect(range(0, $weeks - 1))->map(function ($i) {
            return now()->startOfWeek(Carbon::MONDAY)->subWeeks($i)->startOfDay()->format('Y-m-d');
        });

        $fichajes = Fichar::query()
            ->select(['user_id', 'bienestar', 'created_at'])
            ->whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$startOldestWeek, $endThisWeek])
            ->get();

        $avgByUserWeek = $fichajes
            ->groupBy(function ($row) {
                $monday = Carbon::parse($row->created_at)->startOfWeek(Carbon::MONDAY)->startOfDay();
                return $row->user_id . '|' . $monday->format('Y-m-d');
            })
            ->map(fn($g) => $g->avg('bienestar'));

        return $usersRemotos->mapWithKeys(function ($u) use ($avgByUserWeek, $weekKeys) {
            $emailKey = mb_strtolower(trim($u->email ?? ''));

            $vals = $weekKeys->map(function ($weekMonday) use ($avgByUserWeek, $u) {
                $avg = $avgByUserWeek->get($u->id . '|' . $weekMonday);
                if ($avg === null) return null;
                $lvl = (int) round($avg);
                return max(1, min(4, $lvl));
            })->values()->all();

            return [$emailKey => $vals];
        });
    }
}
