@extends('layouts.unified-automation')

@section('title', 'Secuencias Programadas | Automatizacion WhatsApp')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/bundled/lenis.min.js"></script>
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

        .title-font {
            font-family: var(--font-title);
            letter-spacing: -0.5px;
        }

        /* Gradientes personalizados */
        .gradient-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }

        .gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .gradient-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .gradient-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Animaciones */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes pulse-subtle {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
        }

        .animate-slide-in {
            animation: slideInUp 0.6s ease-out forwards;
        }

        .animate-fab {
            animation: slideInUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* Hover effects */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
        }

        .btn-hover {
            transition: all 0.2s ease;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-hover:active {
            transform: translateY(0);
        }

        /* Badge animations */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            animation: fadeIn 0.3s ease-out;
        }

        .status-active {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-inactive {
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(107, 114, 128, 0.05));
            color: #475569;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state-icon {
            font-size: 64px;
            opacity: 0.3;
            margin-bottom: 20px;
            animation: pulse-subtle 2s infinite;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen text-slate-900">
    <main class="max-w-7xl mx-auto px-6 py-12" x-data="sequenceDashboard()" x-init="init()" data-sequence-dashboard data-live-status-endpoint="{{ route('automation.api.sequences-live-status') }}">
        <x-ui.section-heading title="Secuencias Programadas" subtitle="Diseño unificado con foco en legibilidad, orden visual y navegación rápida.">
            <x-slot:actions>
                <x-ui.button as="a" :href="route('automation.missing-punch.preview')" variant="secondary">Vista no fichados</x-ui.button>
                <x-ui.button as="a" :href="route('automation.audit.index')" variant="ghost">Auditoria</x-ui.button>
                <x-ui.button as="a" :href="route('automation.sequences.create')">Nueva Secuencia</x-ui.button>
            </x-slot:actions>
        </x-ui.section-heading>

        <!-- Mensaje de éxito/error -->
        @if (session('success'))
            <div class="mb-6 p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-sm font-medium animate-slide-in flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-700 text-sm font-medium animate-slide-in flex items-center gap-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <section class="mb-4 gradient-card rounded-2xl p-4 card-hover animate-slide-in">
            <form method="GET" action="{{ route('automation.sequences.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div class="md:col-span-2">
                    <label for="filter-q" class="block text-xs font-semibold text-slate-600 uppercase mb-1">Buscar</label>
                    <input id="filter-q" type="text" name="q" value="{{ $query ?? '' }}" placeholder="Nombre o descripcion..." class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm">
                </div>
                <div>
                    <label for="filter-status" class="block text-xs font-semibold text-slate-600 uppercase mb-1">Estado</label>
                    <select id="filter-status" name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm">
                        <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                        <option value="active" {{ ($statusFilter ?? 'all') === 'active' ? 'selected' : '' }}>Activas</option>
                        <option value="paused" {{ ($statusFilter ?? 'all') === 'paused' ? 'selected' : '' }}>Pausadas</option>
                        <option value="inactive" {{ ($statusFilter ?? 'all') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                    </select>
                </div>
                <div>
                    <label for="filter-health" class="block text-xs font-semibold text-slate-600 uppercase mb-1">Salud</label>
                    <select id="filter-health" name="health" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm">
                        <option value="all" {{ ($healthFilter ?? 'all') === 'all' ? 'selected' : '' }}>Todas</option>
                        <option value="urgent" {{ ($healthFilter ?? 'all') === 'urgent' ? 'selected' : '' }}>Con riesgo</option>
                        <option value="soon" {{ ($healthFilter ?? 'all') === 'soon' ? 'selected' : '' }}>Proxima &lt; 1h</option>
                        <option value="with_attachments" {{ ($healthFilter ?? 'all') === 'with_attachments' ? 'selected' : '' }}>Con adjuntos</option>
                        <option value="no_schedule" {{ ($healthFilter ?? 'all') === 'no_schedule' ? 'selected' : '' }}>Sin programacion</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">Aplicar filtros</button>
                    <a href="{{ route('automation.sequences.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200 transition">Limpiar</a>
                </div>
            </form>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                    {{ $sequences->count() }} resultado(s)
                </span>

                @if(!empty($query))
                    <a href="{{ route('automation.sequences.index', ['status' => $statusFilter ?? 'all', 'health' => $healthFilter ?? 'all']) }}"
                       class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-blue-100 text-blue-700 text-xs font-semibold hover:bg-blue-200 transition"
                       title="Quitar búsqueda">
                        Buscar: {{ $query }} <span aria-hidden="true">×</span>
                    </a>
                @endif

                @if(($statusFilter ?? 'all') !== 'all')
                    <a href="{{ route('automation.sequences.index', ['q' => $query ?? '', 'health' => $healthFilter ?? 'all', 'status' => 'all']) }}"
                       class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-semibold hover:bg-emerald-200 transition"
                       title="Quitar filtro de estado">
                        Estado: {{ $statusFilter }} <span aria-hidden="true">×</span>
                    </a>
                @endif

                @if(($healthFilter ?? 'all') !== 'all')
                    <a href="{{ route('automation.sequences.index', ['q' => $query ?? '', 'status' => $statusFilter ?? 'all', 'health' => 'all']) }}"
                       class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-semibold hover:bg-amber-200 transition"
                       title="Quitar filtro de salud">
                        Salud: {{ $healthFilter }} <span aria-hidden="true">×</span>
                    </a>
                @endif
            </div>
        </section>

        <section class="mb-6 gradient-card rounded-2xl p-5 card-hover animate-slide-in">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="title-font text-lg font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10A8 8 0 11.001 10 8 8 0 0118 10zM9 9V5a1 1 0 012 0v4a1 1 0 01-1 1H7a1 1 0 110-2h2z" clip-rule="evenodd"/></svg>
                    Monitor de Ejecución
                </h2>
                <button type="button" onclick="window.location.reload()" class="px-3 py-1 rounded-lg bg-violet-500/10 hover:bg-violet-500/20 text-violet-700 text-xs font-semibold transition-all duration-200 btn-hover">
                    Actualizar
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4 text-sm">
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <p class="text-slate-500 text-xs font-semibold uppercase">Hora servidor</p>
                    <p class="font-semibold text-slate-900" id="server-time">{{ $diagnostics['server_now']->format('d/m/Y H:i:s') }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 px-3 py-2">
                    <p class="text-slate-500 text-xs font-semibold uppercase">Timezone</p>
                    <p class="font-semibold text-slate-900">{{ $diagnostics['app_timezone'] }}</p>
                </div>
                <div class="rounded-lg px-3 py-2 {{ $diagnostics['queue_worker_active'] ? 'bg-emerald-50' : 'bg-amber-50' }}">
                    <p class="text-slate-500 text-xs font-semibold uppercase">Worker cola</p>
                    <p class="font-semibold {{ $diagnostics['queue_worker_active'] ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $diagnostics['queue_worker_active'] ? 'Activo' : 'Sin heartbeat reciente' }}
                        @if(!is_null($diagnostics['queue_worker_heartbeat_age']))
                            ({{ $diagnostics['queue_worker_heartbeat_age'] }}s)
                        @endif
                    </p>
                </div>
            </div>
        </section>

        @if(($templates ?? collect())->isNotEmpty())
            <section class="mb-6 gradient-card rounded-2xl p-5 card-hover animate-slide-in">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <h2 class="title-font text-lg font-bold text-slate-900">Plantillas de secuencia</h2>
                    <span class="text-xs font-semibold text-slate-500">1 clic para crear nuevas secuencias</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                    @foreach($templates as $template)
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <p class="font-semibold text-slate-900">{{ $template->name }}</p>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $template->description ?: 'Sin descripcion' }}</p>
                            <form method="POST" action="{{ route('automation.sequences.createFromTemplate', $template) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="w-full px-3 py-2 rounded bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition">Crear secuencia desde plantilla</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mb-4 gradient-card rounded-2xl p-4 card-hover animate-slide-in">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="title-font text-base font-bold text-slate-900">Vista de secuencias</h2>
                    <p class="text-xs text-slate-500">Controla el nivel de detalle para navegar con menos scroll.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="collapseAll()" class="px-3 py-2 rounded-lg bg-slate-500/10 hover:bg-slate-500/20 text-slate-700 text-xs font-semibold border border-slate-300 transition">Replegar todas</button>
                    <button type="button" @click="expandAll()" class="px-3 py-2 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-700 text-xs font-semibold border border-blue-300 transition">Desplegar todas</button>
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-violet-300 bg-violet-50 text-violet-800 text-xs font-semibold cursor-pointer select-none">
                        <input type="checkbox" id="default-compact-mode" class="rounded border-violet-300 text-violet-600 focus:ring-violet-500" x-model="compactDefault" @change="persistCompactDefault()">
                        Compacto por defecto
                    </label>
                </div>
            </div>
        </section>

        <!-- Grid de Secuencias -->
        @forelse($sequences as $sequence)
            @include('automation.sequences.partials.sequence-card', ['sequence' => $sequence, 'trafficLights' => $trafficLights])
        @empty
            <div class="gradient-card rounded-2xl p-12 text-center card-hover">
                <div class="empty-state">
                    <div class="empty-state-icon">⚡</div>
                    <h3 class="title-font text-2xl font-bold text-slate-900 mb-2">Sin secuencias aún</h3>
                    <p class="text-slate-600 mb-6 max-w-md">Crea tu primera secuencia automática de WhatsApp para comenzar con automatizaciones programadas.</p>
                    <a href="{{ route('automation.sequences.create') }}" class="px-6 py-3 gradient-primary rounded-lg text-white font-semibold btn-hover shadow-lg inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Crear Primera Secuencia
                    </a>
                </div>
            </div>
        @endforelse
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/10 mt-12 py-6 text-center text-white/60 text-sm">
        <p>© 2026 Sistema de Automatización WhatsApp • Todas las secuencias se ejecutan automáticamente según su programación</p>
    </footer>
    </div>
@endsection

