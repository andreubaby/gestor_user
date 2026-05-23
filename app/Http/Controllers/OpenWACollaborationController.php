<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\OpenWA\OpenWAClient;
use App\Models\TrabajadorPolifonia;
use App\Models\WhatsappGroup;
use App\Services\WhatsApp\AutomaticMessageChainService;
use App\Services\WhatsApp\WhatsappNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class OpenWACollaborationController extends Controller
{
    private const ENQUEUE_ERROR_PREFIX = 'No se pudo encolar el envio: ';

    public function index(): View
    {
        $context = $this->loadOpenwaContext();

        $recentMessages = $this->getRecentMessages();

        return view('openwa.colaboraciones', array_merge($context, [
            'openwaBaseUrl' => config('openwa.base_url'),
            'recentMessages' => $recentMessages,
            'diagnostics' => $this->getDiagnostics(),
        ]));
    }

    public function automaticMessages(): View
    {
        $context = $this->loadOpenwaContext();

        return view('openwa.automatizaciones', array_merge($context, [
            'openwaBaseUrl' => config('openwa.base_url'),
            'diagnostics' => $this->getDiagnostics(),
        ]));
    }

    public function diagnostics(): JsonResponse
    {
        return response()->json($this->getDiagnostics());
    }

    public function recentMessagesPartial(): View
    {
        return view('openwa.partials.recent-messages-table', [
            'recentMessages' => $this->getRecentMessages(),
        ]);
    }

    /**
     * Buscar trabajadores por nombre/email/teléfono
     */
    public function searchTrabajadores(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

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
    }

    /**
     * Enviar a trabajador usando su ID
     */
    public function sendFromTrabajador(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'trabajador_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $trabajador = TrabajadorPolifonia::query()
            ->whereKey($data['trabajador_id'])
            ->first();

        if (!$trabajador || empty($trabajador->tfno)) {
            return back()->withInput()->with('error', 'Trabajador no encontrado o sin teléfono.');
        }

        try {
            /** @var WhatsappNotificationService $service */
            $service = app(WhatsappNotificationService::class);
            $service->sendToPhone($trabajador->tfno, $data['message'], null, true);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', self::ENQUEUE_ERROR_PREFIX . $e->getMessage());
        }

        return back()->with('success', "Mensaje encolado para {$trabajador->nombre}.");
    }

    public function sendFromPhone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        try {
            /** @var WhatsappNotificationService $service */
            $service = app(WhatsappNotificationService::class);
            $service->sendToPhone($data['phone'], $data['message'], null, true);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', self::ENQUEUE_ERROR_PREFIX . $e->getMessage());
        }

        return back()->with('success', 'Mensaje encolado para el telefono indicado.');
    }

    /**
     * Enviar a grupo de WhatsApp
     */
    public function sendToGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer', 'exists:whatsapp_groups,id'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $group = WhatsappGroup::findOrFail($data['group_id']);

        try {
            /** @var WhatsappNotificationService $service */
            $service = app(WhatsappNotificationService::class);
            $service->sendToGroup($group, $data['message'], true);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', self::ENQUEUE_ERROR_PREFIX . $e->getMessage());
        }

        return back()->with('success', "Mensaje encolado para {$group->member_count} miembros del grupo '{$group->name}'.");
    }

    /**
     * Enviar directamente a un chat_id de grupo real OpenWA
     */
    public function sendToOpenwaGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'chat_id' => ['required', 'string', 'max:120', 'regex:/@g\.us$/'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'chat_id.regex' => 'El chat_id debe corresponder a un grupo de WhatsApp (terminado en @g.us).',
        ]);

        try {
            /** @var WhatsappNotificationService $service */
            $service = app(WhatsappNotificationService::class);
            $service->sendToChatId($data['chat_id'], $data['message'], null, true);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo encolar el envio al grupo OpenWA: ' . $e->getMessage());
        }

        return back()->with('success', "Mensaje encolado al grupo OpenWA '{$data['chat_id']}'.");
    }

    public function sendAutomaticMessages(Request $request, AutomaticMessageChainService $service): RedirectResponse
    {
        $data = $request->validate([
            'automation_name' => ['nullable', 'string', 'max:255'],
            'steps' => ['required', 'array', 'min:1', 'max:20'],
            'steps.*.type' => ['required', 'in:person,local_group,openwa_group'],
            'steps.*.person_mode' => ['nullable', 'in:worker,phone'],
            'steps.*.trabajador_id' => ['nullable', 'integer'],
            'steps.*.phone' => ['nullable', 'string', 'max:30'],
            'steps.*.group_id' => ['nullable', 'integer'],
            'steps.*.chat_id' => ['nullable', 'string', 'max:120'],
            'steps.*.message' => ['required', 'string', 'max:2000'],
            'steps.*.delay_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ]);

        try {
            $summary = $service->dispatchChain(
                $data['steps'],
                auth()->id(),
                $data['automation_name'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo encolar la secuencia automática: ' . $e->getMessage());
        }

        $sequenceName = $data['automation_name'] ?: 'Secuencia automática';
        $stepCount = $summary['step_count'] ?? count($data['steps']);

        return back()->with('success', "{$sequenceName} encolada correctamente ({$stepCount} pasos). Puedes seguir ajustando la secuencia mientras se procesa en cola.");
    }

    /**
     * Crear grupo de WhatsApp
     */
    public function createGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'chat_id' => ['required', 'string', 'unique:whatsapp_groups,chat_id'],
        ]);

        $data['created_by'] = auth()->id();

        try {
            WhatsappGroup::create($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error al crear grupo: ' . $e->getMessage());
        }

        return back()->with('success', "Grupo '{$data['name']}' creado correctamente.");
    }

    protected function loadOpenwaContext(): array
    {
        $session = null;
        $sessionError = null;
        $sessionGroupsError = null;
        $openwaSessionGroups = collect();
        $groups = WhatsappGroup::query()->latest()->get();

        try {
            /** @var OpenWAClient $client */
            $client = app(OpenWAClient::class);
            $session = $client->getSession();

            try {
                $openwaSessionGroups = collect($client->getSessionGroups())
                    ->map(function ($group) {
                        return [
                            'chat_id' => (string) ($group['id'] ?? ''),
                            'name' => (string) ($group['name'] ?? 'Grupo sin nombre'),
                            'member_count' => isset($group['size']) ? (int) $group['size'] : null,
                        ];
                    })
                    ->filter(fn ($group) => $group['chat_id'] !== '');
            } catch (\Throwable $e) {
                $sessionGroupsError = $e->getMessage();
                Log::warning('OpenWA groups unavailable for collaboration dashboard', [
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            $sessionError = $e->getMessage();
            Log::warning('OpenWA collaboration dashboard unavailable', [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'session' => $session,
            'sessionError' => $sessionError,
            'sessionGroupsError' => $sessionGroupsError,
            'openwaSessionGroups' => $openwaSessionGroups,
            'groups' => $groups,
        ];
    }

    protected function getRecentMessages()
    {
        return WhatsappMessage::query()
            ->latest()
            ->limit(20)
            ->get();
    }

    protected function getDiagnostics(): array
    {
        $pending = 0;
        $failed = 0;
        $workerActive = false;
        $workerHeartbeatAge = null;

        try {
            $pending = (int) DB::table('jobs')->count();
        } catch (\Throwable $e) {
            // Si la tabla jobs no existe en algún entorno, mantenemos 0.
        }

        try {
            $failed = (int) DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            // Si la tabla failed_jobs no existe en algún entorno, mantenemos 0.
        }

        try {
            $heartbeatPath = storage_path('framework/queue-worker-heartbeat');

            if (is_file($heartbeatPath)) {
                $timestamp = (int) trim((string) file_get_contents($heartbeatPath));

                if ($timestamp > 0) {
                    $workerHeartbeatAge = max(0, now()->timestamp - $timestamp);
                    $workerActive = $workerHeartbeatAge <= 15;
                }
            }
        } catch (\Throwable $e) {
            // Si no se puede leer heartbeat, dejamos worker como inactivo.
        }

        return [
            'session_id' => (string) config('openwa.session_id', 'default'),
            'webhook_url' => route('api.webhooks.openwa'),
            'jobs_pending' => $pending,
            'jobs_failed' => $failed,
            'worker_active' => $workerActive,
            'worker_heartbeat_age' => $workerHeartbeatAge,
            'updated_at' => now()->format('H:i:s'),
        ];
    }
}
