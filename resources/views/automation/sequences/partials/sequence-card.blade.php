@php
    $light = $trafficLights[$sequence->id] ?? ['level' => 'amber', 'label' => 'Sin datos', 'reason' => ''];
    $lightClasses = match ($light['level']) {
        'green' => 'bg-emerald-100 text-emerald-700 border-emerald-300',
        'red' => 'bg-red-100 text-red-700 border-red-300',
        default => 'bg-amber-100 text-amber-700 border-amber-300',
    };
    $dotClass = match ($light['level']) {
        'green' => 'bg-emerald-500',
        'red' => 'bg-red-500',
        default => 'bg-amber-500',
    };
    $sortedSchedules = $sequence->scheduledAutomations->sortBy('scheduled_time')->values();
    $lastExecuted = $sequence->scheduledAutomations->max('last_executed_at');
    $nextExecution = $sequence->scheduledAutomations
        ->where('status', 'active')
        ->map(fn($s) => $s->effective_next_execution)
        ->filter()
        ->sort()
        ->first();
@endphp

<div class="mb-5 gradient-card rounded-2xl p-6 card-hover group animate-slide-in sequence-card" data-sequence-id="{{ $sequence->id }}" style="animation-delay: {{ $loop->index * 0.1 }}s">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2 flex-wrap">
                <h2 class="title-font text-xl font-bold text-slate-900">{{ $sequence->name }}</h2>
                <span class="status-badge {{ $sequence->isActive() ? 'status-active' : 'status-inactive' }}">
                    <span class="w-2 h-2 rounded-full {{ $sequence->isActive() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    {{ ucfirst($sequence->status) }}
                </span>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-xs font-semibold border {{ $lightClasses }}" title="{{ $light['reason'] }}">
                    <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                    {{ $light['label'] }}
                </span>
                <button
                    type="button"
                    class="ml-auto inline-flex items-center gap-1 px-3 py-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                    @click="toggleSequence({{ $sequence->id }})"
                    :aria-expanded="isCollapsed({{ $sequence->id }}) ? 'false' : 'true'"
                >
                    <span aria-hidden="true" x-text="isCollapsed({{ $sequence->id }}) ? '▸' : '▾'"></span>
                    <span x-text="isCollapsed({{ $sequence->id }}) ? 'Desplegar' : 'Replegar'"></span>
                </button>
            </div>

            <div class="mb-3 text-xs text-slate-600 flex flex-wrap items-center gap-2">
                <span class="px-2 py-1 rounded bg-slate-100">{{ count($sequence->actions) }} pasos</span>
                <span class="px-2 py-1 rounded bg-slate-100">{{ $sequence->scheduledAutomations->count() }} programaciones</span>
            </div>

            <div
                id="sequence-details-{{ $sequence->id }}"
                x-show="!isCollapsed({{ $sequence->id }})"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                x-cloak
            >
                @if($sequence->description)
                    <p class="text-sm text-slate-600 mb-4 line-clamp-2">{{ $sequence->description }}</p>
                @endif

                @if(!empty($light['reason']))
                    <p class="text-xs text-slate-500 mb-3">{{ $light['reason'] }}</p>
                @endif

                <div class="flex items-center gap-6 text-sm">
                    <div class="flex items-center gap-2 text-slate-600">
                        <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z"/></svg>
                        <span>{{ count($sequence->actions) }} pasos</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600">
                        <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5.5 12a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"/></svg>
                        <span>{{ $sequence->scheduledAutomations->count() }} programaciones</span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-3">
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Horas programadas</p>
                        <p class="text-sm text-slate-800 mt-1">
                            @if($sortedSchedules->isEmpty())
                                Sin programaciones
                            @else
                                {{ $sortedSchedules->map(fn($s) => \Carbon\Carbon::parse($s->scheduled_time)->format('H:i'))->implode(' · ') }}
                            @endif
                        </p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Ultima ejecucion</p>
                        <p class="text-sm text-slate-800 mt-1">{{ $lastExecuted ? $lastExecuted->format('d/m H:i') : 'Aun no ejecutada' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <p class="text-[11px] font-semibold uppercase text-slate-500">Proxima ejecucion</p>
                        <p class="text-sm text-slate-800 mt-1">{{ $nextExecution ? $nextExecution->format('d/m H:i') : 'Sin proxima ejecucion' }}</p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-[11px]" data-live-status-container>
                    <span class="px-2 py-1 rounded bg-blue-100 text-blue-700">queued: <strong data-live-status="queued">0</strong></span>
                    <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700">executed: <strong data-live-status="executed">0</strong></span>
                    <span class="px-2 py-1 rounded bg-red-100 text-red-700">failed: <strong data-live-status="failed">0</strong></span>
                    <span class="px-2 py-1 rounded bg-amber-100 text-amber-700">duplicate_blocked: <strong data-live-status="duplicate_blocked">0</strong></span>
                    <span class="px-2 py-1 rounded bg-slate-100 text-slate-600">24h</span>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col gap-2"
            x-show="!isCollapsed({{ $sequence->id }})"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-2"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-2"
            x-cloak
        >
            <form method="POST" action="{{ route('automation.sequences.execute', $sequence) }}" onsubmit="return confirm('¿Ejecutar esta secuencia ahora?');">
                @csrf
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 text-sm font-medium btn-hover border border-emerald-500/30 flex items-center justify-center gap-2 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                    Ejecutar
                </button>
            </form>
            <form method="POST" action="{{ route('automation.sequences.toggleStatus', $sequence) }}">
                @csrf
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 text-sm font-medium btn-hover border border-amber-500/30 flex items-center justify-center gap-2 whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6"/></svg>
                    {{ $sequence->status === 'active' ? 'Pausar' : 'Reactivar' }}
                </button>
            </form>
            <a href="{{ route('automation.sequences.show', $sequence) }}" class="px-4 py-2 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-700 text-sm font-medium btn-hover border border-blue-500/30 flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Ver
            </a>
            <a href="{{ route('automation.sequences.edit', $sequence) }}" class="px-4 py-2 rounded-lg bg-slate-500/10 hover:bg-slate-500/20 text-slate-700 text-sm font-medium btn-hover border border-slate-500/30 flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar
            </a>
            <form method="POST" action="{{ route('automation.sequences.duplicate', $sequence) }}">
                @csrf
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-700 text-sm font-medium btn-hover border border-indigo-500/30">Duplicar</button>
            </form>
            <form method="POST" action="{{ route('automation.sequences.saveTemplate', $sequence) }}">
                @csrf
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-violet-500/10 hover:bg-violet-500/20 text-violet-700 text-sm font-medium btn-hover border border-violet-500/30">Guardar como plantilla</button>
            </form>
        </div>
    </div>
</div>

