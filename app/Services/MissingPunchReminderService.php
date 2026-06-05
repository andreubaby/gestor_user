<?php

namespace App\Services;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use App\Services\WhatsApp\WhatsappNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MissingPunchReminderService
{
    /** @var WhatsappNotificationService */
    protected $whatsappNotificationService;

    /** @var FichajesDiariosService */
    protected $fichajesDiariosService;

    public function __construct(
        WhatsappNotificationService $whatsappNotificationService,
        FichajesDiariosService $fichajesDiariosService
    ) {
        $this->whatsappNotificationService = $whatsappNotificationService;
        $this->fichajesDiariosService = $fichajesDiariosService;
    }

    /**
     * @return array{date:string,status:string,reason?:string,total_candidates?:int,sent?:int,skipped_no_phone?:int,skipped_duplicate?:int}
     */
    public function sendForDate(?Carbon $targetDate = null): array
    {
        $date = ($targetDate ?: now()->subDay())->copy()->startOfDay();
        $result = [
            'date' => $date->toDateString(),
            'status' => 'ok',
            'total_candidates' => 0,
            'sent' => 0,
            'skipped_no_phone' => 0,
            'skipped_duplicate' => 0,
        ];

        if (!config('fichajes.missing_punch.enabled', true)) {
            $result['status'] = 'skipped';
            $result['reason'] = 'disabled';
            $this->logSendRun($result);
            return $result;
        }

        if ($this->isNonWorkingDay($date)) {
            $result['status'] = 'skipped';
            $result['reason'] = 'non_working_day';
            $this->logSendRun($result);
            return $result;
        }

        $evaluation = $this->evaluateDate($date);
        $candidates = $evaluation['candidates'];

        if ($candidates->isNotEmpty()) {

            $messageTemplate = (string) config(
                'fichajes.missing_punch.message_template',
                'Hola {nombre}, ayer ({fecha}) no aparece ningun fichaje tuyo. Si corresponde, revisalo en la app.'
            );

            $sent = 0;
            $skippedNoPhone = 0;
            $skippedDuplicate = 0;

            foreach ($candidates as $worker) {
                $phone = trim((string) ($worker['tfno'] ?? ''));
                if ($phone === '') {
                    $skippedNoPhone++;
                    continue;
                }

                $lockKey = sprintf(
                    'missing-punch-reminder:%s:%d',
                    $date->toDateString(),
                    (int) $worker['trabajador_id']
                );

                // Evita reenvio si el comando se lanza varias veces el mismo dia.
                if (!Cache::add($lockKey, now()->toDateTimeString(), now()->addDays(2))) {
                    $skippedDuplicate++;
                    continue;
                }

                $message = $this->buildMessage($messageTemplate, [
                    'nombre' => (string) ($worker['nombre'] ?? ''),
                    'fecha' => $date->format('d/m/Y'),
                ]);

                $this->whatsappNotificationService->sendToPhone(
                    $phone,
                    $message,
                    isset($worker['usuario_id']) ? (int) $worker['usuario_id'] : null,
                    true
                );

                $sent++;
            }

            $result['total_candidates'] = $candidates->count();
            $result['sent'] = $sent;
            $result['skipped_no_phone'] = $skippedNoPhone;
            $result['skipped_duplicate'] = $skippedDuplicate;
        }

        $this->logSendRun($result);

        return $result;
    }

    /**
     * @param array{date:string,status:string,reason?:string,total_candidates?:int,sent?:int,skipped_no_phone?:int,skipped_duplicate?:int} $result
     */
    protected function logSendRun(array $result): void
    {
        Log::channel('openwa')->info('Missing punch reminders processed', [
            'date' => (string) ($result['date'] ?? ''),
            'status' => (string) ($result['status'] ?? 'unknown'),
            'reason' => isset($result['reason']) ? (string) $result['reason'] : null,
            'total_candidates' => (int) ($result['total_candidates'] ?? 0),
            'sent' => (int) ($result['sent'] ?? 0),
            'skipped_no_phone' => (int) ($result['skipped_no_phone'] ?? 0),
            'skipped_duplicate' => (int) ($result['skipped_duplicate'] ?? 0),
        ]);
    }

    /**
     * @return array{date:string,status:string,reason?:string,total_linked:int,total_with_punch:int,total_absent:int,total_candidates:int,total_omitted:int,candidates:list<array<string,mixed>>,omitted_candidates:list<array<string,mixed>>}
     */
    public function previewForDate(?Carbon $targetDate = null): array
    {
        $date = ($targetDate ?: now()->subDay())->copy()->startOfDay();

        if ($this->isNonWorkingDay($date)) {
            return [
                'date' => $date->toDateString(),
                'status' => 'skipped',
                'reason' => 'non_working_day',
                'total_linked' => 0,
                'total_with_punch' => 0,
                'total_absent' => 0,
                'total_candidates' => 0,
                'total_omitted' => 0,
                'candidates' => [],
                'omitted_candidates' => [],
            ];
        }

        $evaluation = $this->evaluateDate($date);

        return [
            'date' => $date->toDateString(),
            'status' => 'ok',
            'total_linked' => (int) ($evaluation['stats']['total'] ?? $evaluation['workers']->count()),
            'total_with_punch' => (int) ($evaluation['stats']['con_fichaje'] ?? $evaluation['users_with_punches']->count()),
            'total_absent' => (int) ($evaluation['stats']['en_ausencia'] ?? $evaluation['absent_trabajador_ids']->count()),
            'total_candidates' => $evaluation['candidates']->count(),
            'total_omitted' => $evaluation['omitted_candidates']->count(),
            'candidates' => $evaluation['candidates']->map(fn (array $worker) => [
                'trabajador_id' => (int) ($worker['trabajador_id'] ?? 0),
                'nombre' => (string) ($worker['nombre'] ?? ''),
                'email' => (string) ($worker['email'] ?? ''),
                'tfno' => (string) ($worker['tfno'] ?? ''),
                'usuario_id' => isset($worker['usuario_id']) ? (int) $worker['usuario_id'] : null,
                'user_fichaje_id' => isset($worker['user_fichaje_id']) ? (int) $worker['user_fichaje_id'] : null,
            ])->values()->all(),
            'omitted_candidates' => $evaluation['omitted_candidates']->map(fn (array $worker) => [
                'trabajador_id' => (int) ($worker['trabajador_id'] ?? 0),
                'nombre' => (string) ($worker['nombre'] ?? ''),
                'email' => (string) ($worker['email'] ?? ''),
                'tfno' => (string) ($worker['tfno'] ?? ''),
            ])->values()->all(),
        ];
    }

    /**
     * @return array{workers:Collection<int,array<string,mixed>>,users_with_punches:Collection<int,int>,absent_trabajador_ids:Collection<int,int>,candidates:Collection<int,array<string,mixed>>,omitted_candidates:Collection<int,array<string,mixed>>,stats:array<string,int>}
     */
    protected function evaluateDate(Carbon $date): array
    {
        $dailyData = $this->fichajesDiariosService->handle(new Request([
            'date' => $date->toDateString(),
            'estado' => 'activo',
        ]));

        $workers = $this->buildWorkersFromDailyRows(collect($dailyData['rows'] ?? []));

        if ($workers->isEmpty()) {
            return [
                'workers' => collect(),
                'users_with_punches' => collect(),
                'absent_trabajador_ids' => collect(),
                'candidates' => collect(),
                'omitted_candidates' => collect(),
                'stats' => [
                    'total' => 0,
                    'con_fichaje' => 0,
                    'sin_fichaje' => 0,
                    'en_ausencia' => 0,
                ],
            ];
        }

        $stats = [
            'total' => (int) ($dailyData['stats']['total'] ?? $workers->count()),
            'con_fichaje' => (int) ($dailyData['stats']['con_fichaje'] ?? 0),
            'sin_fichaje' => (int) ($dailyData['stats']['sin_fichaje'] ?? 0),
            'en_ausencia' => (int) ($dailyData['stats']['en_ausencia'] ?? 0),
        ];

        $usersWithPunches = $workers
            ->filter(fn (array $w) => (int) ($w['count'] ?? 0) > 0)
            ->pluck('trabajador_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $absentTrabajadorIds = $workers
            ->filter(fn (array $w) => (int) ($w['count'] ?? 0) === 0 && !empty($w['absence_tipo']))
            ->pluck('trabajador_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $rawCandidates = $workers
            ->filter(fn (array $w) => (int) ($w['count'] ?? 0) === 0 && empty($w['absence_tipo']))
            ->values();

        $omittedByScheduleCandidates = $rawCandidates
            ->filter(fn (array $w) => !$this->isWorkerScheduledOnDate($w, $date))
            ->values();

        $rawCandidates = $rawCandidates
            ->reject(fn (array $w) => !$this->isWorkerScheduledOnDate($w, $date))
            ->values();

        $omittedEmails = $this->getOmittedEmails();
        $campaignUserIds = $this->getCampaignUserFichajeIds($workers);

        $omittedByCampaignCandidates = $rawCandidates
            ->filter(function (array $w) use ($campaignUserIds) {
                $userFichajeId = (int) ($w['user_fichaje_id'] ?? 0);

                return $userFichajeId > 0 && $campaignUserIds->contains($userFichajeId);
            })
            ->values();

        $omittedByEmailCandidates = $rawCandidates
            ->filter(function (array $w) use ($omittedEmails) {
                $email = strtolower(trim((string) ($w['email'] ?? '')));

                return $email !== '' && $omittedEmails->contains($email);
            })
            ->values();

        $omittedCandidates = $omittedByEmailCandidates
            ->concat($omittedByCampaignCandidates)
            ->concat($omittedByScheduleCandidates)
            ->unique(fn (array $w) => (int) ($w['trabajador_id'] ?? 0))
            ->values();

        $candidates = $rawCandidates
            ->reject(function (array $w) use ($omittedEmails, $campaignUserIds) {
                $email = strtolower(trim((string) ($w['email'] ?? '')));
                $userFichajeId = (int) ($w['user_fichaje_id'] ?? 0);

                $isOmittedByCampaignMode = $userFichajeId > 0 && $campaignUserIds->contains($userFichajeId);

                return ($email !== '' && $omittedEmails->contains($email)) || $isOmittedByCampaignMode;
            })
            ->values();

        return [
            'workers' => $workers,
            'users_with_punches' => $usersWithPunches,
            'absent_trabajador_ids' => $absentTrabajadorIds,
            'candidates' => $candidates,
            'omitted_candidates' => $omittedCandidates,
            'stats' => $stats,
        ];
    }

    protected function buildWorkersFromDailyRows(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        $trabajadorIds = $rows
            ->pluck('trabajador_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $vinculos = UsuarioVinculado::query()
            ->whereIn('trabajador_id', $trabajadorIds->all())
            ->get(['trabajador_id', 'usuario_id', 'user_fichaje_id'])
            ->keyBy(fn ($v) => (int) $v->trabajador_id);

        $phoneByTrabajadorId = TrabajadorPolifonia::query()
            ->whereIn('id', $trabajadorIds->all())
            ->get(['id', 'tfno'])
            ->mapWithKeys(function ($row) {
                $id = (int) ($row->id ?? 0);
                $tfno = trim((string) ($row->tfno ?? ''));

                return $id > 0 && $tfno !== '' ? [$id => $tfno] : [];
            });

        $emails = $rows
            ->map(fn ($row) => strtolower(trim((string) ($row->email ?? ''))))
            ->filter(fn ($email) => $email !== '')
            ->unique()
            ->values();

        $phoneByEmail = collect();

        if ($emails->isNotEmpty()) {
            try {
                $phoneByEmail = DB::connection('mysql_trabajadores')
                    ->table('users')
                    ->whereIn('email', $emails->all())
                    ->get(['email', 'tfno'])
                    ->mapWithKeys(function ($row) {
                        $email = strtolower(trim((string) ($row->email ?? '')));
                        $tfno = trim((string) ($row->tfno ?? ''));

                        return $email !== '' && $tfno !== '' ? [$email => $tfno] : [];
                    });
            } catch (\Throwable $e) {
                Log::warning('MissingPunchReminderService phone mapping by email failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $rows
            ->map(function ($row) use ($vinculos, $phoneByEmail, $phoneByTrabajadorId) {
                $trabajadorId = (int) ($row->trabajador_id ?? 0);
                $v = $vinculos->get($trabajadorId);
                $email = strtolower(trim((string) ($row->email ?? '')));
                $phoneByEmailValue = $email !== '' ? (string) ($phoneByEmail->get($email) ?? '') : '';
                $phoneByTrabajadorValue = (string) ($phoneByTrabajadorId->get($trabajadorId) ?? '');
                $phoneFromRow = trim((string) ($row->tfno ?? ''));
                $resolvedPhone = $phoneByEmailValue !== ''
                    ? $phoneByEmailValue
                    : ($phoneByTrabajadorValue !== '' ? $phoneByTrabajadorValue : $phoneFromRow);

                return [
                    'trabajador_id' => $trabajadorId,
                    'nombre' => (string) ($row->nombre ?? ''),
                    'email' => (string) ($row->email ?? ''),
                    'tfno' => $resolvedPhone,
                    'usuario_id' => $v?->usuario_id ? (int) $v->usuario_id : null,
                    'user_fichaje_id' => $v?->user_fichaje_id ? (int) $v->user_fichaje_id : null,
                    'vinculado_fichajes' => (bool) ($row->vinculado_fichajes ?? false),
                    'count' => (int) ($row->count ?? 0),
                    'absence_tipo' => $row->absence_tipo ?? null,
                ];
            })
            ->values();
    }

    protected function getOmittedEmails(): Collection
    {
        return collect((array) config('fichajes.missing_punch.omit_emails', []))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '')
            ->unique()
            ->values();
    }

    /**
     * @param Collection<int,array<string,mixed>> $workers
     * @return Collection<int,int>
     */
    protected function getCampaignUserFichajeIds(Collection $workers): Collection
    {
        $userFichajeIds = $workers
            ->pluck('user_fichaje_id')
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($userFichajeIds->isEmpty()) {
            return collect();
        }

        try {
            return DB::connection('mysql_fichajes')
                ->table('users')
                ->whereIn('id', $userFichajeIds->all())
                ->whereRaw('LOWER(COALESCE(work_mode, "")) = ?', ['campaign'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        } catch (\Throwable $e) {
            Log::warning('MissingPunchReminderService campaign work_mode check failed', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    protected function getLinkedWorkers(): Collection
    {
        $trabajadores = TrabajadorPolifonia::query()
            ->select(['id', 'nombre', 'email', 'tfno', 'activo'])
            ->where('activo', 1)
            ->get();

        if ($trabajadores->isEmpty()) {
            return collect();
        }

        $vinculos = UsuarioVinculado::query()
            ->whereIn('trabajador_id', $trabajadores->pluck('id')->all())
            ->get(['trabajador_id', 'usuario_id', 'user_fichaje_id'])
            ->keyBy(fn ($v) => (int) $v->trabajador_id);

        $emails = $trabajadores
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '')
            ->unique()
            ->values();

        $phoneByEmail = collect();

        if ($emails->isNotEmpty()) {
            try {
                $phoneByEmail = DB::connection('mysql_trabajadores')
                    ->table('users')
                    ->whereIn('email', $emails->all())
                    ->get(['email', 'tfno'])
                    ->mapWithKeys(function ($row) {
                        $email = strtolower(trim((string) ($row->email ?? '')));
                        $tfno = trim((string) ($row->tfno ?? ''));

                        return $email !== '' && $tfno !== '' ? [$email => $tfno] : [];
                    });
            } catch (\Throwable $e) {
                Log::warning('MissingPunchReminderService phone mapping by email failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $trabajadores
            ->map(function ($t) use ($vinculos, $phoneByEmail) {
                $v = $vinculos->get((int) $t->id);

                $email = strtolower(trim((string) ($t->email ?? '')));
                $phoneByEmailValue = $email !== '' ? (string) ($phoneByEmail->get($email) ?? '') : '';

                return [
                    'trabajador_id' => (int) $t->id,
                    'nombre' => (string) ($t->nombre ?? ''),
                    'email' => (string) ($t->email ?? ''),
                    'tfno' => $phoneByEmailValue !== '' ? $phoneByEmailValue : (string) ($t->tfno ?? ''),
                    'usuario_id' => $v?->usuario_id ? (int) $v->usuario_id : null,
                    'user_fichaje_id' => $v?->user_fichaje_id ? (int) $v->user_fichaje_id : null,
                ];
            })
            ->values();
    }

    protected function isNonWorkingDay(Carbon $date): bool
    {
        if ($this->isHolidayByConfiguredDates($date)) {
            return true;
        }

        return $this->isHolidayByTable($date);
    }

    protected function isHolidayByConfiguredDates(Carbon $date): bool
    {
        $raw = (string) config('fichajes.missing_punch.holidays', '');

        if ($raw === '') {
            return false;
        }

        $dates = collect(explode(',', $raw))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1)
            ->values();

        return $dates->contains($date->toDateString());
    }

    protected function isHolidayByTable(Carbon $date): bool
    {
        $connection = (string) config('fichajes.missing_punch.holiday_connection', 'mysql_polifonia');
        $tableCandidates = (array) config('fichajes.missing_punch.holiday_tables', ['festivos']);
        $columnCandidates = (array) config('fichajes.missing_punch.holiday_date_columns', ['fecha', 'date', 'dia']);

        foreach ($tableCandidates as $table) {
            if (!is_string($table) || trim($table) === '') {
                continue;
            }

            $table = trim($table);

            try {
                if (!Schema::connection($connection)->hasTable($table)) {
                    continue;
                }

                $columns = array_map('strtolower', Schema::connection($connection)->getColumnListing($table));
                $dateColumn = collect($columnCandidates)
                    ->first(fn ($candidate) => in_array(strtolower((string) $candidate), $columns, true));

                if (!$dateColumn) {
                    continue;
                }

                $dateColumn = (string) $dateColumn;

                $exists = DB::connection($connection)
                    ->table($table)
                    ->whereDate($dateColumn, $date->toDateString())
                    ->exists();

                if ($exists) {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning('MissingPunchReminderService holiday table check failed', [
                    'connection' => $connection,
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $replace
     */
    protected function buildMessage(string $template, array $replace): string
    {
        return strtr($template, [
            '{nombre}' => $replace['nombre'] ?? '',
            '{fecha}' => $replace['fecha'] ?? '',
        ]);
    }

    /**
     * @param array<string,mixed> $worker
     */
    protected function isWorkerScheduledOnDate(array $worker, Carbon $date): bool
    {
        $workdays = $this->resolveWorkerWorkdays($worker);

        if (empty($workdays)) {
            return false;
        }

        return in_array($date->dayOfWeekIso, $workdays, true);
    }

    /**
     * @param array<string,mixed> $worker
     * @return array<int,int>
     */
    protected function resolveWorkerWorkdays(array $worker): array
    {
        $defaultWorkdays = collect((array) config('fichajes.missing_punch.default_workdays', [1, 2, 3, 4, 5]))
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->values();

        $email = strtolower(trim((string) ($worker['email'] ?? '')));
        $workdaysByEmail = (array) config('fichajes.missing_punch.workdays_by_email', []);

        if ($email !== '' && isset($workdaysByEmail[$email]) && is_array($workdaysByEmail[$email])) {
            $override = collect($workdaysByEmail[$email])
                ->map(fn ($day) => (int) $day)
                ->filter(fn (int $day) => $day >= 1 && $day <= 7)
                ->unique()
                ->values();

            if ($override->isNotEmpty()) {
                return $override->all();
            }
        }

        return $defaultWorkdays->all();
    }
}








