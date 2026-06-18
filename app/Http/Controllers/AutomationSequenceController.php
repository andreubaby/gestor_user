<?php

namespace App\Http\Controllers;

use App\Models\AutomationSequence;
use App\Models\AutomationSequenceExecutionLog;
use App\Models\ScheduledAutomation;
use App\Models\WhatsappGroup;
use App\Models\TrabajadorPolifonia;
use App\Services\OpenWA\OpenWAClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AutomationSequenceController extends Controller
{
    /**
     * Display a listing of automation sequences
     */
    public function index(Request $request): View
    {
        $allSequences = AutomationSequence::with('scheduledAutomations')->latest()->get();
        $templates = $allSequences->where('is_template', true)->values();
        $sequences = $allSequences->where('is_template', false)->values();

        $queueWorkerActive = false;
        $queueHeartbeatAge = null;
        try {
            $heartbeatPath = storage_path('framework/queue-worker-heartbeat');
            if (is_file($heartbeatPath)) {
                $timestamp = (int) trim((string) file_get_contents($heartbeatPath));
                if ($timestamp > 0) {
                    $queueHeartbeatAge = max(0, now()->timestamp - $timestamp);
                    $queueWorkerActive = $queueHeartbeatAge <= 20;
                }
            }
        } catch (\Throwable $e) {
            // Mantener diagnóstico en fallback si no se puede leer heartbeat.
        }

        $diagnostics = [
            'server_now' => now(),
            'app_timezone' => (string) config('app.timezone', 'UTC'),
            'queue_worker_active' => $queueWorkerActive,
            'queue_worker_heartbeat_age' => $queueHeartbeatAge,
        ];

        $trafficLights = [];
        $now = now();

        $nextExecutions = [];

        foreach ($sequences as $sequence) {
            $activeSchedules = $sequence->scheduledAutomations->where('status', 'active');

            $nextExecution = $activeSchedules
                ->map(fn ($s) => $s->effective_next_execution)
                ->filter()
                ->sort()
                ->first();

            $isDelayed = false;
            foreach ($activeSchedules as $schedule) {
                $days = $schedule->days_of_week ?? [];
                if (!in_array((string) $now->dayOfWeek, $days, true)) {
                    continue;
                }

                $scheduledToday = $now->copy()->setTimeFromTimeString($schedule->scheduled_time);

                // Si ya pasó la hora + tolerancia y no hay evidencia de ejecución hoy, está retrasada.
                if ($now->greaterThan($scheduledToday->copy()->addMinutes(2))) {
                    $lastExecutedAt = $schedule->last_executed_at;
                    if (!$lastExecutedAt || $lastExecutedAt->lessThan($scheduledToday)) {
                        $isDelayed = true;
                        break;
                    }
                }
            }

            $light = [
                'level' => 'amber',
                'label' => 'Sin próximas activas',
                'reason' => 'No hay ejecuciones pendientes en esta secuencia.',
            ];

            if (!$queueWorkerActive || $isDelayed) {
                $light = [
                    'level' => 'red',
                    'label' => !$queueWorkerActive ? 'Worker inactivo' : 'Ejecución retrasada',
                    'reason' => !$queueWorkerActive
                        ? 'El worker de cola no reporta latido reciente.'
                        : 'Había una ejecución prevista y no consta ejecución reciente.',
                ];
            } elseif ($nextExecution) {
                $minutesToNext = $now->diffInMinutes($nextExecution, false);
                if ($minutesToNext >= 0 && $minutesToNext <= 5) {
                    $light = [
                        'level' => 'green',
                        'label' => 'Próxima < 5 min',
                        'reason' => 'La próxima ejecución está dentro de la ventana inmediata.',
                    ];
                } else {
                    $light = [
                        'level' => 'amber',
                        'label' => 'Programada',
                        'reason' => 'Hay próxima ejecución activa, pero no es inminente.',
                    ];
                }
            }

            $trafficLights[$sequence->id] = $light;
            $nextExecutions[$sequence->id] = $nextExecution;
        }

        if ($request->boolean('reset_filters')) {
            $request->session()->forget('automation.sequences.filters');
        }

        $filterSessionKey = 'automation.sequences.filters';
        $hasFilterInput = $request->hasAny(['q', 'status', 'health']);
        $storedFilters = (array) $request->session()->get($filterSessionKey, []);

        $query = trim((string) ($hasFilterInput ? $request->input('q', '') : ($storedFilters['q'] ?? '')));
        $statusFilter = (string) ($hasFilterInput ? $request->input('status', 'all') : ($storedFilters['status'] ?? 'all'));
        $healthFilter = (string) ($hasFilterInput ? $request->input('health', 'all') : ($storedFilters['health'] ?? 'all'));

        $request->session()->put($filterSessionKey, [
            'q' => $query,
            'status' => $statusFilter,
            'health' => $healthFilter,
        ]);

        if ($query !== '') {
            $sequences = $sequences->filter(function (AutomationSequence $sequence) use ($query) {
                $haystack = mb_strtolower($sequence->name . ' ' . ($sequence->description ?? ''));
                return str_contains($haystack, mb_strtolower($query));
            })->values();
        }

        if (in_array($statusFilter, ['active', 'inactive', 'paused'], true)) {
            $sequences = $sequences->where('status', $statusFilter)->values();
        }

        if ($healthFilter !== 'all') {
            $sequences = $sequences->filter(function (AutomationSequence $sequence) use ($healthFilter, $trafficLights, $nextExecutions, $now) {
                $light = $trafficLights[$sequence->id]['level'] ?? 'amber';
                $next = $nextExecutions[$sequence->id] ?? null;
                $hasSchedules = $sequence->scheduledAutomations->count() > 0;
                $hasAttachments = collect($sequence->actions ?? [])->contains(function ($action) {
                    return !empty($action['attachment_url']) || !empty($action['attachment_urls']);
                });

                return match ($healthFilter) {
                    'urgent' => $light === 'red',
                    'soon' => $next && $now->diffInMinutes($next, false) >= 0 && $now->diffInMinutes($next, false) <= 60,
                    'with_attachments' => $hasAttachments,
                    'no_schedule' => !$hasSchedules,
                    default => true,
                };
            })->values();
        }

        $priority = ['red' => 0, 'amber' => 1, 'green' => 2];
        $sequences = $sequences->sort(function (AutomationSequence $a, AutomationSequence $b) use ($trafficLights, $nextExecutions, $priority) {
            $pa = $priority[$trafficLights[$a->id]['level'] ?? 'amber'] ?? 1;
            $pb = $priority[$trafficLights[$b->id]['level'] ?? 'amber'] ?? 1;

            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            $na = $nextExecutions[$a->id] ?? null;
            $nb = $nextExecutions[$b->id] ?? null;

            if ($na && $nb) {
                return $na <=> $nb;
            }

            if ($na && !$nb) {
                return -1;
            }

            if (!$na && $nb) {
                return 1;
            }

            return strcmp((string) $a->name, (string) $b->name);
        })->values();

        return view('automation.sequences.index', compact('sequences', 'templates', 'diagnostics', 'trafficLights', 'query', 'statusFilter', 'healthFilter'));
    }

    /**
     * Acción rápida para pausar/reactivar secuencia.
     */
    public function toggleStatus(AutomationSequence $sequence): RedirectResponse
    {
        $newStatus = $sequence->status === 'active' ? 'paused' : 'active';
        $sequence->update(['status' => $newStatus]);

        return redirect()->route('automation.sequences.index', request()->query())
            ->with('success', "Secuencia '{$sequence->name}' actualizada a estado {$newStatus}.");
    }

    /**
     * Show the form for creating a new automation sequence
     */
    public function create(): View
    {
        $groups = WhatsappGroup::query()->latest()->get();
        $openwaSessionGroups = $this->getOpenwaGroups();
        $templates = AutomationSequence::query()
            ->where('is_template', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'actions']);

        return view('automation.sequences.create', compact('groups', 'openwaSessionGroups', 'templates'));
    }

    /**
     * Store a newly created automation sequence
     */
    public function store(Request $request): RedirectResponse
    {
        $this->normalizeActionsFromRequest($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:automation_sequences',
            'description' => 'nullable|string',
            'actions' => 'required|array|min:1',
            'status' => 'required|in:active,inactive,paused',
        ]);

        AutomationSequence::create($validated);

        return redirect()->route('automation.sequences.index')
            ->with('success', 'Secuencia de automatización creada correctamente');
    }

    /**
     * Display the specified automation sequence
     */
    public function show(AutomationSequence $sequence): View
    {
        $sequence->load(['scheduledAutomations', 'executionLogs']);

        $lastExecutedAt = $sequence->scheduledAutomations->max('last_executed_at');
        $nextExecutionAt = $sequence->scheduledAutomations
            ->where('status', 'active')
            ->map(fn ($s) => $s->effective_next_execution)
            ->filter()
            ->sort()
            ->first();

        $executionLogs = $sequence->executionLogs()
            ->latest('happened_at')
            ->limit(20)
            ->get();

        $recentActivity = $executionLogs->take(8);

        $queueWorkerActive = false;
        $queueHeartbeatAge = null;
        try {
            $heartbeatPath = storage_path('framework/queue-worker-heartbeat');
            if (is_file($heartbeatPath)) {
                $timestamp = (int) trim((string) file_get_contents($heartbeatPath));
                if ($timestamp > 0) {
                    $queueHeartbeatAge = max(0, now()->timestamp - $timestamp);
                    $queueWorkerActive = $queueHeartbeatAge <= 20;
                }
            }
        } catch (\Throwable $e) {
            // Si falla diagnóstico, dejamos valores por defecto.
        }

        $diagnostics = [
            'server_now' => now(),
            'app_timezone' => (string) config('app.timezone', 'UTC'),
            'active_schedules_count' => $sequence->scheduledAutomations->where('status', 'active')->count(),
            'queue_worker_active' => $queueWorkerActive,
            'queue_worker_heartbeat_age' => $queueHeartbeatAge,
        ];

        return view('automation.sequences.show', compact(
            'sequence',
            'lastExecutedAt',
            'nextExecutionAt',
            'diagnostics',
            'executionLogs',
            'recentActivity'
        ));
    }

    /**
     * Show the form for editing the specified automation sequence
     */
    public function edit(AutomationSequence $sequence): View
    {
        $groups = WhatsappGroup::query()->latest()->get();
        $openwaSessionGroups = $this->getOpenwaGroups();
        $templates = AutomationSequence::query()
            ->where('is_template', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'actions']);

        return view('automation.sequences.edit', compact('sequence', 'groups', 'openwaSessionGroups', 'templates'));
    }

    public function duplicate(AutomationSequence $sequence): RedirectResponse
    {
        $newSequence = AutomationSequence::create([
            'name' => $this->nextAvailableSequenceName($sequence->name . ' (copia)'),
            'description' => $sequence->description,
            'actions' => $sequence->actions,
            'status' => 'inactive',
            'is_template' => false,
            'template_source_id' => $sequence->is_template ? $sequence->id : $sequence->template_source_id,
        ]);

        return redirect()->route('automation.sequences.edit', $newSequence)
            ->with('success', 'Secuencia duplicada. Revisa y guarda los cambios.');
    }

    public function saveAsTemplate(AutomationSequence $sequence): RedirectResponse
    {
        AutomationSequence::create([
            'name' => $this->nextAvailableSequenceName('Plantilla - ' . $sequence->name),
            'description' => $sequence->description,
            'actions' => $sequence->actions,
            'status' => 'inactive',
            'is_template' => true,
            'template_source_id' => $sequence->id,
        ]);

        return redirect()->route('automation.sequences.show', $sequence)
            ->with('success', 'Plantilla guardada correctamente.');
    }

    public function createFromTemplate(AutomationSequence $sequence): RedirectResponse
    {
        if (!$sequence->is_template) {
            return redirect()->route('automation.sequences.index')->with('error', 'La secuencia indicada no es una plantilla.');
        }

        $newSequence = AutomationSequence::create([
            'name' => $this->nextAvailableSequenceName('Nueva - ' . $sequence->name),
            'description' => $sequence->description,
            'actions' => $sequence->actions,
            'status' => 'inactive',
            'is_template' => false,
            'template_source_id' => $sequence->id,
        ]);

        return redirect()->route('automation.sequences.edit', $newSequence)
            ->with('success', 'Secuencia creada desde plantilla.');
    }

    /**
     * Update the specified automation sequence
     */
    public function update(Request $request, AutomationSequence $sequence): RedirectResponse
    {
        $this->normalizeActionsFromRequest($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:automation_sequences,name,' . $sequence->id,
            'description' => 'nullable|string',
            'actions' => 'required|array|min:1',
            'status' => 'required|in:active,inactive,paused',
        ]);

        $sequence->update($validated);

        return redirect()->route('automation.sequences.show', $sequence)
            ->with('success', 'Secuencia de automatización actualizada correctamente');
    }

    /**
     * Delete the specified automation sequence
     */
    public function destroy(AutomationSequence $sequence): RedirectResponse
    {
        $sequence->delete();

        return redirect()->route('automation.sequences.index')
            ->with('success', 'Secuencia de automatización eliminada correctamente');
    }

    /**
     * Create a scheduled automation
     */
    public function createSchedule(AutomationSequence $sequence): View
    {
        return view('automation.schedules.create', compact('sequence'));
    }

    /**
     * Store a scheduled automation
     */
    public function storeSchedule(Request $request, AutomationSequence $sequence): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_time' => 'required|date_format:H:i',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'in:2,3,4,5',
        ]);

        $daysOfWeek = collect($validated['days_of_week'])
            ->map(fn ($day) => (string) $day)
            ->unique()
            ->values()
            ->all();

        ScheduledAutomation::create([
            'automation_sequence_id' => $sequence->id,
            'scheduled_time' => $validated['scheduled_time'],
            'days_of_week' => $daysOfWeek,
            'status' => 'active',
            'next_execution_at' => $this->calculateNextExecution(
                $validated['scheduled_time'],
                $daysOfWeek
            ),
        ]);

        return redirect()->route('automation.sequences.show', $sequence)
            ->with('success', 'Programación de automatización creada correctamente');
    }

    /**
     * Edit a scheduled automation
     */
    public function editSchedule(AutomationSequence $sequence, ScheduledAutomation $schedule): View
    {
        return view('automation.schedules.edit', compact('sequence', 'schedule'));
    }

    /**
     * Update a scheduled automation
     */
    public function updateSchedule(Request $request, AutomationSequence $sequence, ScheduledAutomation $schedule): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_time' => 'required|date_format:H:i',
            'days_of_week' => 'required|array|min:1',
            'days_of_week.*' => 'in:2,3,4,5',
            'status' => 'required|in:active,inactive',
        ]);

        $daysOfWeek = collect($validated['days_of_week'])
            ->map(fn ($day) => (string) $day)
            ->unique()
            ->values()
            ->all();

        $schedule->update([
            'scheduled_time' => $validated['scheduled_time'],
            'days_of_week' => $daysOfWeek,
            'status' => $validated['status'],
            'next_execution_at' => $this->calculateNextExecution(
                $validated['scheduled_time'],
                $daysOfWeek
            ),
        ]);

        return redirect()->route('automation.sequences.show', $sequence)
            ->with('success', 'Programación de automatización actualizada correctamente');
    }

    /**
     * Delete a scheduled automation
     */
    public function destroySchedule(AutomationSequence $sequence, ScheduledAutomation $schedule): RedirectResponse
    {
        $schedule->delete();

        return redirect()->route('automation.sequences.show', $sequence)
            ->with('success', 'Programación de automatización eliminada correctamente');
    }

    /**
     * Execute an automation sequence manually
     */
    public function execute(AutomationSequence $sequence): RedirectResponse
    {
        if ($sequence->execute(auth()->id())) {
            return redirect()->back()->with('success', 'Secuencia encolada correctamente. Los mensajes se enviarán por el worker de cola.');
        } else {
            return redirect()->back()->with('error', 'Error al ejecutar la secuencia de automatización');
        }
    }

    /**
     * Ejecuta acciones masivas sobre secuencias seleccionadas.
     */
    public function bulkActions(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => 'required|in:pause,activate,execute,duplicate,save_template',
            'sequence_ids' => 'required|array|min:1',
            'sequence_ids.*' => 'integer|exists:automation_sequences,id',
        ]);

        $ids = collect($data['sequence_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->route('automation.sequences.index')
                ->with('error', 'No se recibieron secuencias para acción masiva.');
        }

        $sequences = AutomationSequence::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $result = [
            'action' => $data['action'],
            'total' => $ids->count(),
            'ok' => [],
            'skipped' => [],
            'failed' => [],
        ];

        foreach ($ids as $id) {
            /** @var AutomationSequence|null $sequence */
            $sequence = $sequences->get($id);
            if (!$sequence) {
                $result['failed'][] = [
                    'id' => $id,
                    'name' => 'N/D',
                    'reason' => 'Secuencia no encontrada',
                ];
                continue;
            }

            try {
                if ($data['action'] === 'pause') {
                    if ($sequence->status === 'paused') {
                        $result['skipped'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'Ya estaba pausada'];
                    } else {
                        $sequence->update(['status' => 'paused']);
                        $result['ok'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'Pausada'];
                    }
                    continue;
                }

                if ($data['action'] === 'activate') {
                    if ($sequence->status === 'active') {
                        $result['skipped'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'Ya estaba activa'];
                    } else {
                        $sequence->update(['status' => 'active']);
                        $result['ok'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'Reactivada'];
                    }
                    continue;
                }

                if ($data['action'] === 'duplicate') {
                    AutomationSequence::create([
                        'name' => $this->nextAvailableSequenceName($sequence->name . ' (copia)'),
                        'description' => $sequence->description,
                        'actions' => $sequence->actions,
                        'status' => 'inactive',
                        'is_template' => false,
                        'template_source_id' => $sequence->is_template ? $sequence->id : $sequence->template_source_id,
                    ]);

                    $result['ok'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'Duplicada'];
                    continue;
                }

                if ($data['action'] === 'save_template') {
                    AutomationSequence::create([
                        'name' => $this->nextAvailableSequenceName('Plantilla - ' . $sequence->name),
                        'description' => $sequence->description,
                        'actions' => $sequence->actions,
                        'status' => 'inactive',
                        'is_template' => true,
                        'template_source_id' => $sequence->id,
                    ]);

                    $result['ok'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'Plantilla creada'];
                    continue;
                }

                if ($sequence->execute(auth()->id())) {
                    $result['ok'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'Encolada'];
                } else {
                    $result['failed'][] = ['id' => $id, 'name' => $sequence->name, 'reason' => 'No se pudo encolar'];
                }
            } catch (\Throwable $e) {
                $result['failed'][] = [
                    'id' => $id,
                    'name' => $sequence->name,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        $okCount = count($result['ok']);
        $failedCount = count($result['failed']);
        $skippedCount = count($result['skipped']);

        $actionLabel = match ($data['action']) {
            'pause' => 'pausar',
            'activate' => 'reactivar',
            'execute' => 'ejecutar',
            'duplicate' => 'duplicar',
            'save_template' => 'crear plantillas de',
            default => 'procesar',
        };

        $message = "Acción masiva '{$actionLabel}': OK {$okCount}";
        if ($skippedCount > 0) {
            $message .= ", omitidas {$skippedCount}";
        }
        if ($failedCount > 0) {
            $message .= ", fallidas {$failedCount}";
        }
        $message .= '.';

        return redirect()->route('automation.sequences.index')
            ->with($failedCount > 0 ? 'error' : 'success', $message)
            ->with('bulk_result', $result);
    }

    public function exportBulkActionsCsv(Request $request): RedirectResponse|StreamedResponse
    {
        $bulk = $request->session()->get('bulk_result');

        if (!is_array($bulk)) {
            return redirect()->route('automation.sequences.index')
                ->with('error', 'No hay resultado masivo disponible para exportar.');
        }

        $action = (string) ($bulk['action'] ?? 'unknown');
        $filename = 'automation_bulk_result_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($bulk, $action) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['status', 'id', 'nombre', 'motivo', 'accion']);

            $statusMap = [
                'ok' => 'ok',
                'skipped' => 'skipped',
                'failed' => 'failed',
            ];

            foreach ($statusMap as $key => $statusLabel) {
                foreach (($bulk[$key] ?? []) as $row) {
                    fputcsv($handle, [
                        $statusLabel,
                        (int) ($row['id'] ?? 0),
                        (string) ($row['name'] ?? 'N/D'),
                        preg_replace('/\s+/', ' ', (string) ($row['reason'] ?? '')),
                        $action,
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function liveStatus(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $counts = AutomationSequenceExecutionLog::query()
            ->select('automation_sequence_id', 'status', DB::raw('COUNT(*) as total'))
            ->whereIn('automation_sequence_id', $ids)
            ->whereIn('status', ['queued', 'executed', 'failed', 'duplicate_blocked'])
            ->where('happened_at', '>=', now()->subHours(24))
            ->groupBy('automation_sequence_id', 'status')
            ->get();

        $latest = AutomationSequenceExecutionLog::query()
            ->select('automation_sequence_id', DB::raw('MAX(happened_at) as last_event_at'))
            ->whereIn('automation_sequence_id', $ids)
            ->groupBy('automation_sequence_id')
            ->get()
            ->keyBy('automation_sequence_id');

        $payload = [];
        foreach ($ids as $id) {
            $payload[$id] = [
                'queued' => 0,
                'executed' => 0,
                'failed' => 0,
                'duplicate_blocked' => 0,
                'last_event_at' => optional($latest->get($id))->last_event_at,
            ];
        }

        foreach ($counts as $row) {
            $sequenceId = (int) $row->automation_sequence_id;
            $status = (string) $row->status;
            $payload[$sequenceId][$status] = (int) $row->total;
        }

        return response()->json([
            'ok' => true,
            'data' => $payload,
            'window_hours' => 24,
        ]);
    }

    public function audit(Request $request): View
    {
        $query = AutomationSequenceExecutionLog::query()
            ->with('automationSequence:id,name')
            ->latest('happened_at');

        $this->applyAuditFilters($query, $request);

        $events = $query->paginate(50)->withQueryString();
        $sequences = AutomationSequence::query()->orderBy('name')->get(['id', 'name']);

        return view('automation.audit.index', [
            'events' => $events,
            'sequences' => $sequences,
            'filters' => [
                'from' => (string) $request->query('from', ''),
                'to' => (string) $request->query('to', ''),
                'status' => (string) $request->query('status', 'all'),
                'sequence_id' => (string) $request->query('sequence_id', 'all'),
                'destination' => (string) $request->query('destination', ''),
            ],
        ]);
    }

    public function exportAuditCsv(Request $request): StreamedResponse
    {
        $query = AutomationSequenceExecutionLog::query()
            ->with('automationSequence:id,name')
            ->latest('happened_at');

        $this->applyAuditFilters($query, $request);
        $filename = 'automation_audit_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['fecha_hora', 'secuencia', 'paso', 'estado', 'destino', 'tipo_destino', 'mensaje', 'execution_key']);

            foreach ($query->cursor() as $log) {
                fputcsv($handle, $this->buildCsvRow($log));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function applyAuditFilters($query, Request $request): void
    {
        $status = (string) $request->query('status', 'all');
        if (in_array($status, ['queued', 'executed', 'failed', 'duplicate_blocked'], true)) {
            $query->where('status', $status);
        }

        $sequenceId = (int) $request->query('sequence_id', 0);
        if ($sequenceId > 0) {
            $query->where('automation_sequence_id', $sequenceId);
        }

        $destination = trim((string) $request->query('destination', ''));
        if ($destination !== '') {
            $query->where('target_label', 'like', '%' . $destination . '%');
        }

        $from = trim((string) $request->query('from', ''));
        if ($from !== '') {
            $query->where('happened_at', '>=', Carbon::parse($from)->startOfDay());
        }

        $to = trim((string) $request->query('to', ''));
        if ($to !== '') {
            $query->where('happened_at', '<=', Carbon::parse($to)->endOfDay());
        }
    }

    private function buildCsvRow(AutomationSequenceExecutionLog $log): array
    {
        return [
            optional($log->happened_at)->format('Y-m-d H:i:s'),
            $log->automationSequence?->name ?? 'N/D',
            $log->step_number,
            $log->status,
            $log->target_label,
            $log->target_type,
            preg_replace('/\s+/', ' ', (string) $log->message),
            $log->execution_key,
        ];
    }

    private function nextAvailableSequenceName(string $baseName): string
    {
        $name = trim($baseName) !== '' ? trim($baseName) : 'Secuencia';
        $candidate = $name;
        $suffix = 2;

        while (AutomationSequence::query()->where('name', $candidate)->exists()) {
            $candidate = $name . ' #' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Calculate the next execution time
     */
    private function calculateNextExecution(string $time, array $daysOfWeek): Carbon
    {
        $now = Carbon::now();
        $nextExecution = $now->copy()->setTimeFromTimeString($time);

        // If the time has already passed today, start from tomorrow
        if ($nextExecution <= $now) {
            $nextExecution->addDay();
        }

        // Find the next scheduled day
        while (!in_array((string) $nextExecution->dayOfWeek, $daysOfWeek)) {
            $nextExecution->addDay();
        }

        return $nextExecution;
    }

    /**
     * Get OpenWA session groups
     */
    private function getOpenwaGroups(): array
    {
        try {
            /** @var OpenWAClient $client */
            $client = app(OpenWAClient::class);

            $groups = collect($client->getSessionGroups())
                ->map(function ($group) {
                    $chatId = (string) ($group['chat_id'] ?? $group['chatId'] ?? $group['id'] ?? '');
                    $name = (string) ($group['name'] ?? $group['title'] ?? $group['formattedTitle'] ?? 'Grupo sin nombre');

                    return [
                        'chat_id' => $chatId,
                        'name' => $name,
                        'member_count' => isset($group['size']) ? (int) $group['size'] : null,
                    ];
                })
                ->filter(fn ($group) => $group['chat_id'] !== '')
                ->values()
                ->all();

            return !empty($groups) ? $groups : $this->getMockOpenwaGroups();
        } catch (\Exception $e) {
            \Log::warning('Error fetching OpenWA groups: ' . $e->getMessage());
            return $this->getMockOpenwaGroups();
        }
    }

    /**
     * Get mock OpenWA groups for development
     */
    private function getMockOpenwaGroups(): array
    {
        return [
            [
                'chat_id' => '120363123456789@g.us',
                'name' => '📱 Equipo de Ventas',
                'participants' => 8,
            ],
            [
                'chat_id' => '120363987654321@g.us',
                'name' => '💼 Directivos',
                'participants' => 5,
            ],
            [
                'chat_id' => '120363555666777@g.us',
                'name' => '👥 Todos',
                'participants' => 25,
            ],
        ];
    }

    /**
     * Search for trabajadores
     */
    public function searchTrabajadores(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            $trabajadores = TrabajadorPolifonia::query()
                ->where('tfno', 'like', "%{$query}%")
                ->orWhere('nombre', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->limit(10)
                ->get(['id', 'nombre', 'tfno', 'email'])
                ->map(fn($t) => [
                    'id' => $t->id,
                    'label' => "{$t->nombre} ({$t->tfno})",
                    'tfno' => $t->tfno,
                    'nombre' => $t->nombre,
                    'email' => $t->email,
                ]);

            return response()->json($trabajadores);
        } catch (\Exception $e) {
            \Log::warning('Error searching trabajadores: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Normaliza el campo actions cuando llega como JSON string desde el form.
     */
    private function normalizeActionsFromRequest(Request $request): void
    {
        $actions = $request->input('actions');

        if (is_string($actions)) {
            $decoded = json_decode($actions, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['actions' => $decoded]);
                return;
            }
        }

        if (is_array($actions)) {
            return;
        }

        $steps = $request->input('steps');
        if (is_array($steps)) {
            $request->merge(['actions' => array_values($steps)]);
        }
    }

    /**
     * Subir adjunto para automatizaciones y devolver URL pública.
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,mp4,mov,avi,mkv,webm,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,txt,zip'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:51200', 'mimes:pdf,mp4,mov,avi,mkv,webm,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx,txt,zip'],
        ]);

        $files = [];

        if ($request->hasFile('files')) {
            $files = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $files = [$request->file('file')];
        }

        if (empty($files)) {
            return response()->json([
                'ok' => false,
                'message' => 'No se recibió ningún archivo válido.',
            ], 422);
        }

        $uploaded = [];

        foreach ($files as $file) {
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $safeName = Str::uuid()->toString() . '.' . $extension;
            $file->storeAs('automation_attachments', $safeName);

            $uploaded[] = [
                'url' => route('automation.attachments.show', ['filename' => $safeName]),
                'filename' => $file->getClientOriginalName(),
                'stored_name' => $safeName,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        return response()->json([
            'ok' => true,
            'files' => $uploaded,
            // Compatibilidad con frontend anterior (single file)
            'url' => $uploaded[0]['url'] ?? null,
            'filename' => $uploaded[0]['filename'] ?? null,
        ]);
    }

    /**
     * Servir adjunto almacenado para uso de OpenWA.
     */
    public function serveAttachment(string $filename)
    {
        // Evitar path traversal
        if (!preg_match('/^[A-Za-z0-9\-]+\.[A-Za-z0-9]+$/', $filename)) {
            abort(404);
        }

        $path = 'automation_attachments/' . $filename;

        if (!Storage::exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::path($path);
        $mime = Storage::mimeType($path) ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}


