@extends('layouts.unified-automation')

@section('title', $sequence->name . ' | Detalles')

@push('head')
    <style>
        * {
            --font-title: 'Poppins', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        body {
            font-family: var(--font-body);
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0c4a6e 100%);
            min-height: 100vh;
        }

        .title-font { font-family: var(--font-title); letter-spacing: -0.5px; }
        .gradient-primary { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); }
        .gradient-card { background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }

        @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        .animate-slide-in { animation: slideInUp 0.6s ease-out forwards; }
        .animate-slide-in-left { animation: slideInLeft 0.6s ease-out forwards; }

        .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15); }

        .btn-hover { transition: all 0.2s ease; }
        .btn-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }

        .step-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(96, 165, 250, 0.05));
            border-left: 4px solid #3b82f6;
        }

        .schedule-item {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), rgba(52, 211, 153, 0.05));
            border-left: 4px solid #10b981;
        }

        .compact-scroll {
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            padding-right: 4px;
        }

        .step-summary {
            cursor: pointer;
            list-style: none;
        }

        .step-summary::-webkit-details-marker {
            display: none;
        }

        details[open] .step-chevron {
            transform: rotate(90deg);
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen text-slate-900">
    <!-- Contenido -->
    <main class="max-w-6xl mx-auto px-4 py-6">
        <x-ui.section-heading :title="$sequence->name" :subtitle="'Estado: ' . ucfirst($sequence->status)">
            <x-slot:actions>
                <x-ui.button as="a" :href="route('automation.sequences.edit', $sequence)" variant="secondary">Editar</x-ui.button>
                <form method="POST" action="{{ route('automation.sequences.duplicate', $sequence) }}" class="inline">@csrf <x-ui.button type="submit" variant="ghost">Duplicar</x-ui.button></form>
                <form method="POST" action="{{ route('automation.sequences.saveTemplate', $sequence) }}" class="inline">@csrf <x-ui.button type="submit" variant="ghost">Guardar plantilla</x-ui.button></form>
                <form method="POST" action="{{ route('automation.sequences.execute', $sequence) }}" class="inline" onsubmit="return confirm('¿Ejecutar esta secuencia ahora?');">@csrf <x-ui.button type="submit">Ejecutar</x-ui.button></form>
            </x-slot:actions>
        </x-ui.section-heading>

        <div class="grid grid-cols-12 gap-4">
            <!-- Pasos -->
            <div class="col-span-12 xl:col-span-8 space-y-4">
                <section class="gradient-card rounded-2xl p-5 card-hover animate-slide-in">
                    <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <h2 class="title-font text-xl font-bold">Pasos de WhatsApp</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="collapse-all-steps" class="px-3 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                Solo resumen
                            </button>
                            <button type="button" id="expand-all-steps" class="px-3 py-1 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-semibold transition">
                                Expandir todo
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2 compact-scroll">
                        @forelse($sequence->actions as $idx => $action)
                            @php
                                $stepType = $action['type'] ?? 'unknown';
                                $typeLabel = $stepType === 'person' ? '👤 Persona' : ($stepType === 'local_group' ? '👥 Grupo Local' : ($stepType === 'openwa_group' ? '🌐 Grupo OpenWA' : '📱 Mensaje'));
                                $destination = $stepType === 'person'
                                    ? ($action['person_mode'] === 'phone' ? 'Tel: ' . ($action['phone'] ?? 'N/D') : 'Trabajador: ' . ($action['trabajador_id'] ?? 'N/D'))
                                    : ($stepType === 'local_group' ? 'Grupo ID: ' . ($action['group_id'] ?? 'N/D') : ($stepType === 'openwa_group' ? ($action['chat_id'] ?? 'N/D') : 'N/D'));
                                $attachmentUrls = $action['attachment_urls'] ?? [];
                                $attachmentNames = $action['attachment_names'] ?? [];
                                if (empty($attachmentUrls) && !empty($action['attachment_url'])) {
                                    $attachmentUrls = [$action['attachment_url']];
                                    $attachmentNames = [($action['attachment_name'] ?? $action['attachment_url'])];
                                }
                            @endphp
                            <details class="step-card rounded-xl p-3 sequence-step-details" {{ $idx === 0 ? 'open' : '' }}>
                                <summary class="step-summary flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-7 h-7 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-xs flex-shrink-0">{{ $idx + 1 }}</div>
                                        <p class="font-semibold text-slate-900 text-sm truncate">{{ $typeLabel }} · {{ $destination }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if(!empty($attachmentUrls))
                                            <span class="text-[11px] px-2 py-0.5 rounded bg-blue-100 text-blue-700">📎 {{ count($attachmentUrls) }}</span>
                                        @endif
                                        <span class="text-[11px] px-2 py-0.5 rounded bg-slate-100 text-slate-700">⏱️ {{ (int) ($action['delay_minutes'] ?? 0) }}m</span>
                                        <svg class="step-chevron w-4 h-4 text-slate-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </summary>

                                <div class="mt-3 text-xs text-slate-600 space-y-2">
                                    <div class="bg-slate-100/50 rounded-lg p-2 border border-slate-200">
                                        <p class="font-medium text-slate-900 mb-1">Mensaje</p>
                                        <p class="text-slate-600">{{ $action['message'] ?? 'Sin mensaje' }}</p>
                                    </div>

                                    @if(!empty($attachmentUrls))
                                        <div>
                                            <p class="font-medium">Archivos adjuntos</p>
                                            <ul class="mt-1 list-disc pl-5 space-y-1">
                                                @foreach($attachmentUrls as $i => $url)
                                                    <li><a href="{{ $url }}" target="_blank" class="text-blue-600 underline break-all">{{ $attachmentNames[$i] ?? $url }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </details>
                        @empty
                            <div class="text-center py-12">
                                <p class="text-slate-600">No hay pasos definidos</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <!-- Sidebar -->
            <aside class="col-span-12 xl:col-span-4 space-y-3 xl:sticky xl:top-24 self-start">
                @php
                    $activeSchedules = $sequence->scheduledAutomations->where('status', 'active');
                    $scheduleTimes = $sequence->scheduledAutomations
                        ->sortBy('scheduled_time')
                        ->take(2)
                        ->map(fn($s) => $s->scheduled_time->format('H:i'))
                        ->implode(' · ');
                @endphp

                <section class="gradient-card rounded-2xl p-3 card-hover animate-slide-in-left" style="animation-delay: 0.1s">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg bg-slate-50 px-2 py-2">
                            <p class="text-slate-500 uppercase font-semibold">Estado</p>
                            <p class="font-semibold {{ $sequence->isActive() ? 'text-emerald-700' : 'text-slate-700' }}">{{ ucfirst($sequence->status) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-2 py-2">
                            <p class="text-slate-500 uppercase font-semibold">Pasos</p>
                            <p class="font-semibold text-slate-900">{{ count($sequence->actions) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-2 py-2">
                            <p class="text-slate-500 uppercase font-semibold">Última</p>
                            <p class="font-semibold text-slate-900">{{ $lastExecutedAt ? $lastExecutedAt->format('d/m H:i') : '—' }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-2 py-2">
                            <p class="text-slate-500 uppercase font-semibold">Próxima</p>
                            <p class="font-semibold text-slate-900">{{ $nextExecutionAt ? $nextExecutionAt->format('d/m H:i') : '—' }}</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2 truncate">Horarios: {{ $scheduleTimes ?: 'Sin horarios' }}</p>
                    <div class="mt-2 flex flex-wrap gap-1 text-[11px]" id="live-status-single" data-sequence-id="{{ $sequence->id }}">
                        <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700">queued: <strong data-live-status="queued">0</strong></span>
                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">executed: <strong data-live-status="executed">0</strong></span>
                        <span class="px-2 py-0.5 rounded bg-red-100 text-red-700">failed: <strong data-live-status="failed">0</strong></span>
                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700">duplicate_blocked: <strong data-live-status="duplicate_blocked">0</strong></span>
                    </div>
                </section>

                <details class="gradient-card rounded-2xl p-3 card-hover animate-slide-in-left" style="animation-delay: 0.15s">
                    <summary class="step-summary flex items-center justify-between">
                        <span class="title-font text-sm font-bold text-slate-900">Actividad ({{ $recentActivity->count() }})</span>
                        <svg class="step-chevron w-4 h-4 text-slate-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="mt-2 space-y-2 max-h-44 overflow-y-auto pr-1">
                        @forelse($recentActivity as $log)
                            @php
                                $status = $log->status;
                                $badge = match ($status) {
                                    'executed' => 'bg-emerald-100 text-emerald-700 border-emerald-300',
                                    'duplicate_blocked' => 'bg-amber-100 text-amber-700 border-amber-300',
                                    'failed' => 'bg-red-100 text-red-700 border-red-300',
                                    'queued' => 'bg-blue-100 text-blue-700 border-blue-300',
                                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                                };
                            @endphp
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $badge }}">{{ strtoupper(str_replace('_', ' ', $status)) }}</span>
                                    <span class="text-[11px] text-slate-500 whitespace-nowrap">{{ $log->happened_at?->format('H:i d/m') }}</span>
                                </div>
                                <p class="text-xs text-slate-600 mt-1 break-words">{{ $log->message ?: ($log->target_label ?? 'Evento de secuencia') }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-600">Sin actividad aún.</p>
                        @endforelse
                    </div>
                </details>

                <details class="gradient-card rounded-2xl p-3 card-hover animate-slide-in-left" style="animation-delay: 0.18s">
                    <summary class="step-summary flex items-center justify-between">
                        <span class="title-font text-sm font-bold text-slate-900">Programaciones ({{ $sequence->scheduledAutomations->count() }})</span>
                        <svg class="step-chevron w-4 h-4 text-slate-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="mt-2">
                        <a href="{{ route('automation.sequences.createSchedule', $sequence) }}" class="inline-flex px-2 py-1 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-700 text-xs font-semibold transition-all duration-200 btn-hover">+ Añadir</a>
                        <div class="space-y-2 max-h-44 overflow-y-auto pr-1 mt-2">
                            @forelse($sequence->scheduledAutomations as $schedule)
                                <div class="schedule-item rounded-lg p-2 group">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="font-semibold text-slate-900 text-sm">{{ $schedule->scheduled_time->format('H:i') }}</p>
                                        <span class="text-[11px] font-semibold {{ $schedule->status === 'active' ? 'text-emerald-600' : 'text-slate-500' }}">{{ ucfirst($schedule->status) }}</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 mb-2">
                                        @php
                                            $daysMap = ['0' => 'Dom', '1' => 'Lun', '2' => 'Mar', '3' => 'Mié', '4' => 'Jue', '5' => 'Vie', '6' => 'Sáb'];
                                            $days = $schedule->days_of_week ?? [];
                                            $labels = array_map(fn($d) => $daysMap[$d], $days);
                                        @endphp
                                        {{ implode(', ', $labels) }}
                                    </p>
                                    <div class="flex gap-1">
                                        <a href="{{ route('automation.sequences.editSchedule', [$sequence, $schedule]) }}" class="flex-1 px-2 py-1 rounded text-[11px] bg-blue-500/10 text-blue-700 hover:bg-blue-500/20 transition-all duration-200 text-center">Editar</a>
                                        <form method="POST" action="{{ route('automation.sequences.destroySchedule', [$sequence, $schedule]) }}" class="inline flex-1" onsubmit="return confirm('¿Eliminar?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full px-2 py-1 rounded text-[11px] bg-red-500/10 text-red-700 hover:bg-red-500/20 transition-all duration-200">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-600">Sin programaciones.</p>
                            @endforelse
                        </div>
                    </div>
                </details>

                <details class="gradient-card rounded-2xl p-3 card-hover animate-slide-in-left" style="animation-delay: 0.2s">
                    <summary class="step-summary flex items-center justify-between">
                        <span class="title-font text-sm font-bold text-slate-900">Diagnóstico</span>
                        <svg class="step-chevron w-4 h-4 text-slate-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="mt-2 space-y-2 text-xs">
                        <button type="button" onclick="window.location.reload()" class="px-2 py-1 rounded-lg bg-violet-500/10 hover:bg-violet-500/20 text-violet-700 font-semibold transition-all duration-200 btn-hover">Actualizar ahora</button>
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-2"><span class="text-slate-600">Hora servidor</span><span class="font-semibold text-slate-900">{{ $diagnostics['server_now']->format('d/m/Y H:i:s') }}</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-2"><span class="text-slate-600">Timezone</span><span class="font-semibold text-slate-900">{{ $diagnostics['app_timezone'] }}</span></div>
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-2 py-2"><span class="text-slate-600">Activas</span><span class="font-semibold text-slate-900">{{ $diagnostics['active_schedules_count'] }}</span></div>
                        <div class="flex items-center justify-between rounded-lg px-2 py-2 {{ $diagnostics['queue_worker_active'] ? 'bg-emerald-50' : 'bg-amber-50' }}">
                            <span class="text-slate-600">Worker cola</span>
                            <span class="font-semibold {{ $diagnostics['queue_worker_active'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $diagnostics['queue_worker_active'] ? 'Activo' : 'Sin heartbeat' }}@if(!is_null($diagnostics['queue_worker_heartbeat_age'])) ({{ $diagnostics['queue_worker_heartbeat_age'] }}s) @endif</span>
                        </div>
                    </div>
                </details>
            </aside>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const expandBtn = document.getElementById('expand-all-steps');
        const collapseBtn = document.getElementById('collapse-all-steps');

        function getStepDetails() {
            return Array.from(document.querySelectorAll('.sequence-step-details'));
        }

        expandBtn?.addEventListener('click', () => {
            getStepDetails().forEach((el) => {
                el.open = true;
            });
        });

        collapseBtn?.addEventListener('click', () => {
            getStepDetails().forEach((el) => {
                el.open = false;
            });
        });

        const liveContainer = document.getElementById('live-status-single');
        const endpoint = @json(route('automation.api.sequences-live-status'));

        async function refreshSingleLiveStatus() {
            if (!liveContainer) return;

            const id = liveContainer.dataset.sequenceId;
            if (!id) return;

            try {
                const response = await fetch(`${endpoint}?ids=${encodeURIComponent(id)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) return;

                const payload = await response.json();
                const row = payload?.data?.[id] || {};
                ['queued', 'executed', 'failed', 'duplicate_blocked'].forEach((status) => {
                    const target = liveContainer.querySelector(`[data-live-status="${status}"]`);
                    if (target) target.textContent = String(row[status] || 0);
                });
            } catch (_) {
                // No interrumpir la pagina si falla el poll.
            }
        }

        refreshSingleLiveStatus();
        setInterval(refreshSingleLiveStatus, 10000);
    });
</script>
@endsection

