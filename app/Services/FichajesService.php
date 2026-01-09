<?php

namespace App\Services;

use App\Models\UserFichaje;
use Carbon\Carbon;

class FichajesService
{
    public function findUserByEmail(?string $email): ?UserFichaje
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email === '') return null;

        return UserFichaje::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
    }

    public function getDailySummaries(UserFichaje $user, ?string $from = null, ?string $to = null)
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth()->startOfDay();
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()     : now()->endOfMonth()->endOfDay();

        return $user->dailySummaries()
            ->whereBetween('work_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderBy('work_date', 'desc')
            ->get();
    }
}
