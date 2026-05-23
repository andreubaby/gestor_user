@extends('layouts.unified-automation')

@section('title', 'OpenWA | Colaboraciones Pro')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
        * {
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            letter-spacing: -0.5px;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-16px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes shimmer {
            0%, 100% {
                background-position: -1000px 0;
            }
            50% {
                background-position: 1000px 0;
            }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        .animate-slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-accent {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .gradient-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .btn-glow {
            position: relative;
            overflow: hidden;
        }
        .btn-glow::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-glow:hover::after {
            width: 300px;
            height: 300px;
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            border-color: #667eea;
        }
        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .badge-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .divider-gradient {
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
        }
        @keyframes rowFlashSuccess {
            0% { background-color: rgba(34, 197, 94, 0.12); }
            50% { background-color: rgba(34, 197, 94, 0.22); }
            100% { background-color: transparent; }
        }
        @keyframes rowFlashFailed {
            0% { background-color: rgba(239, 68, 68, 0.12); }
            50% { background-color: rgba(239, 68, 68, 0.22); }
            100% { background-color: transparent; }
        }
        .row-flash-success {
            animation: rowFlashSuccess 2.2s ease-out;
            box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.35);
            scroll-margin-top: 120px;
        }
        .row-flash-failed {
            animation: rowFlashFailed 2.2s ease-out;
            box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.35);
            scroll-margin-top: 120px;
        }
        section {
            animation: fadeInUp 0.6s ease-out backwards;
        }
        section:nth-child(1) { animation-delay: 0.1s; }
        section:nth-child(2) { animation-delay: 0.2s; }
        section:nth-child(3) { animation-delay: 0.3s; }
        section:nth-child(4) { animation-delay: 0.4s; }
        section:nth-child(5) { animation-delay: 0.5s; }
    </style>
    <script>
        // Función para formatear teléfono con +34 y espacios cada 3 dígitos
        function phoneFormatter() {
            return {
                displayPhone: '',
                rawPhone: '',
                formatPhone() {
                    // Remover todo excepto dígitos
                    let cleaned = this.displayPhone.replace(/\D/g, '');

                    // Remover leading 0 si existe
                    if (cleaned.startsWith('0')) {
                        cleaned = cleaned.substring(1);
                    }

                    // Limitar a 9 dígitos (sin el código de país)
                    cleaned = cleaned.substring(0, 9);
                    this.rawPhone = cleaned;

                    // Formatear en grupos de 3: 622 435 165
                    if (cleaned.length > 0) {
                        let formatted = '';
                        for (let i = 0; i < cleaned.length; i += 3) {
                            if (i > 0) formatted += ' ';
                            formatted += cleaned.substring(i, i + 3);
                        }
                        this.displayPhone = formatted;
                    } else {
                        this.displayPhone = '';
                    }
                }
            }
        }

        function recentMessagesFeed(url) {
            return {
                isRefreshing: false,
                lastUpdated: null,
                timer: null,
                statusMap: {},
                initializeFeed() {
                    this.captureCurrentStatuses();
                    this.refresh();
                    this.timer = setInterval(() => this.refresh(), 5000);
                },
                captureCurrentStatuses() {
                    this.statusMap = {};

                    if (!this.$refs.feed) {
                        return;
                    }

                    this.$refs.feed.querySelectorAll('[data-message-id]').forEach((row) => {
                        this.statusMap[row.dataset.messageId] = row.dataset.status || 'unknown';
                    });
                },
                async refresh() {
                    this.isRefreshing = true;

                    try {
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('No se pudo refrescar la tabla de mensajes.');
                        }

                        const html = await response.text();
                        const parser = new DOMParser();
                        const documentHtml = parser.parseFromString(html, 'text/html');
                        const nextRows = Array.from(documentHtml.querySelectorAll('[data-message-id]'));
                        const changedRows = nextRows.filter((row) => {
                            const messageId = row.dataset.messageId;
                            const previousStatus = this.statusMap[messageId];
                            const nextStatus = row.dataset.status || 'unknown';

                            return previousStatus === 'pending' && (nextStatus === 'sent' || nextStatus === 'failed');
                        }).map((row) => ({
                            id: row.dataset.messageId,
                            status: row.dataset.status || 'unknown',
                        }));

                        if (this.$refs.feed) {
                            this.$refs.feed.innerHTML = html;
                        }

                        this.captureCurrentStatuses();
                        this.lastUpdated = new Date().toLocaleTimeString('es-ES');

                        if (changedRows.length > 0) {
                            requestAnimationFrame(() => {
                                changedRows.forEach(({ id, status }) => this.flashRow(id, status));
                            });
                        }
                    } catch (error) {
                        console.error(error);
                    } finally {
                        this.isRefreshing = false;
                    }
                },
                flashRow(messageId, status) {
                    if (!this.$refs.feed) {
                        return;
                    }

                    const row = this.$refs.feed.querySelector(`[data-message-id="${messageId}"]`);

                    if (!row) {
                        return;
                    }

                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    const highlightClass = status === 'failed' ? 'row-flash-failed' : 'row-flash-success';
                    row.classList.remove('row-flash-success', 'row-flash-failed');
                    row.classList.add(highlightClass);

                    window.setTimeout(() => {
                        row.classList.remove(highlightClass);
                    }, 2200);
                },
                stop() {
                    if (this.timer) {
                        clearInterval(this.timer);
                    }
                }
            }
        }

        function queueDiagnosticsFeed(url, initialState) {
            return {
                diagnostics: initialState,
                timer: null,
                initializeDiagnostics() {
                    this.timer = setInterval(() => this.refresh(), 6000);
                },
                async refresh() {
                    try {
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        this.diagnostics = await response.json();
                    } catch (error) {
                        console.error(error);
                    }
                },
                stop() {
                    if (this.timer) {
                        clearInterval(this.timer);
                    }
                }
            }
        }
    </script>
@endpush

@section('content')
<div class="text-slate-900">


<!-- Alert Messages Container -->
    <div class="mx-auto max-w-[1500px] px-4 py-4">
    @if (session('success'))
        <div class="mb-4 flex animate-fade-in-up items-start gap-3 rounded-xl border-l-4 border-green-500 bg-gradient-to-r from-green-50 to-emerald-50 p-4 text-green-900 shadow-sm">
            <svg class="h-6 w-6 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold">¡Éxito!</h3>
                <p class="text-sm text-opacity-90">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 flex animate-fade-in-up items-start gap-3 rounded-xl border-l-4 border-red-500 bg-gradient-to-r from-red-50 to-pink-50 p-4 text-red-900 shadow-sm">
            <svg class="h-6 w-6 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold">Error</h3>
                <p class="text-sm text-opacity-90">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <!-- Sidebar -->
        <div class="space-y-4 xl:col-span-3">
            <section class="card-hover group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all">
                <div class="absolute inset-0 opacity-0 transition-opacity group-hover:opacity-100 bg-gradient-to-br from-blue-50 to-transparent pointer-events-none"></div>
                <div class="relative space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Estado OpenWA</h2>
                        @if (!$sessionError)
                            <div class="h-3 w-3 rounded-full bg-gradient-success animate-pulse"></div>
                        @endif
                    </div>

                    <div class="divider-gradient h-px"></div>

                    @if ($sessionError || !$session)
                        <div class="rounded-lg bg-gradient-to-r from-red-50 to-pink-50 p-4 text-sm text-red-700 border border-red-200">
                            <p class="font-semibold mb-1">⚠️ Sesión no disponible</p>
                            <p class="text-opacity-90">{{ $sessionError ?: 'No se pudo cargar el estado de sesión.' }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            <div class="flex items-start justify-between rounded-lg bg-slate-50 p-3">
                                <span class="text-sm font-medium text-slate-600">Sesión</span>
                                <span class="font-semibold text-slate-900">{{ $session['name'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-start justify-between rounded-lg bg-slate-50 p-3">
                                <span class="text-sm font-medium text-slate-600">Teléfono</span>
                                <span class="font-mono font-semibold text-slate-900">{{ $session['phone'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-start justify-between rounded-lg bg-slate-50 p-3">
                                <span class="text-sm font-medium text-slate-600">Estado</span>
                                <span class="inline-block rounded-full bg-gradient-success px-3 py-1 text-xs font-semibold text-white">{{ strtoupper($session['status'] ?? 'N/A') }}</span>
                            </div>
                            <div class="flex items-start justify-between rounded-lg bg-slate-50 p-3">
                                <span class="text-sm font-medium text-slate-600">Conectado</span>
                                <span class="text-sm font-medium text-slate-900">{{ $session['connectedAt'] ? \Carbon\Carbon::parse($session['connectedAt'])->diffForHumans() : 'N/A' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="card-hover group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all" x-data="phoneFormatter()">
                <div class="absolute inset-0 -z-10 opacity-0 transition-opacity group-hover:opacity-100 bg-gradient-to-br from-blue-50 via-transparent to-transparent"></div>
                <div class="mb-4 flex items-center gap-3">
                    <div class="gradient-accent rounded-xl p-2.5 text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900">Enviar por Teléfono</h2>
                </div>

                <form method="POST" action="{{ route('openwa.collab.send.phone') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Número</label>
                        <div class="flex w-full items-center rounded-lg border-2 border-slate-200 bg-white transition-all focus-within:border-pink-500 focus-within:shadow-[0_0_0_3px_rgba(245,87,108,0.10)]">
                            <span class="shrink-0 px-4 py-2.5 font-mono text-sm font-semibold tracking-[0.08em] text-slate-700">+34</span>
                            <span class="h-6 w-px bg-slate-200"></span>
                            <input
                                type="tel"
                                x-model="displayPhone"
                                @input="formatPhone()"
                                placeholder="622 435 165"
                                class="w-full border-0 bg-transparent px-4 py-2.5 font-mono text-sm tracking-[0.08em] text-slate-900 placeholder-slate-400 focus:outline-none"
                                maxlength="18"
                                required
                            >
                        </div>
                        <input type="hidden" name="phone" :value="'34' + rawPhone">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mensaje</label>
                        <textarea name="message" placeholder="Tu mensaje..." rows="2" class="input-focus w-full rounded-lg border-2 border-slate-200 bg-white px-4 py-2.5 text-slate-900 placeholder-slate-400 transition-all focus:border-pink-500 focus:outline-none resize-none" required></textarea>
                    </div>
                    <button type="submit" class="btn-glow w-full gradient-accent rounded-lg px-4 py-2.5 font-semibold text-white shadow-md transition-all hover:shadow-lg active:scale-95">
                        Enviar directo
                    </button>
                </form>
            </section>

            <section
                x-data='queueDiagnosticsFeed(@json(route("openwa.collab.diagnostics")), @json($diagnostics))'
                x-init="initializeDiagnostics()"
                x-on:beforeunload.window="stop()"
                class="card-hover group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all"
            >
                <div class="absolute inset-0 -z-10 opacity-0 transition-opacity group-hover:opacity-100 bg-gradient-to-br from-indigo-50 via-transparent to-transparent"></div>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900">Diagnóstico rápido</h2>
                    <span class="text-[11px] text-slate-500" x-text="`Actualizado ${diagnostics.updated_at}`"></span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="rounded-lg px-3 py-2" :class="diagnostics.worker_active ? 'border border-emerald-200 bg-emerald-50' : 'border border-rose-200 bg-rose-50'">
                        <p class="font-semibold" :class="diagnostics.worker_active ? 'text-emerald-700' : 'text-rose-700'">Worker de cola</p>
                        <p class="mt-1 font-semibold" :class="diagnostics.worker_active ? 'text-emerald-900' : 'text-rose-900'" x-text="diagnostics.worker_active ? 'Activo' : 'Inactivo'"></p>
                        <p class="mt-1 text-[11px] text-slate-600" x-show="diagnostics.worker_heartbeat_age !== null" x-text="`Heartbeat hace ${diagnostics.worker_heartbeat_age}s`"></p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <p class="font-semibold text-slate-700">Session ID</p>
                        <p class="mt-1 truncate font-mono text-slate-900" x-text="diagnostics.session_id"></p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <p class="font-semibold text-slate-700">Webhook</p>
                        <p class="mt-1 truncate font-mono text-slate-900" x-text="diagnostics.webhook_url"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                            <p class="font-semibold text-amber-700">Pendientes</p>
                            <p class="mt-1 text-lg font-bold text-amber-800" x-text="diagnostics.jobs_pending"></p>
                        </div>
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2">
                            <p class="font-semibold text-rose-700">Fallidos</p>
                            <p class="mt-1 text-lg font-bold text-rose-800" x-text="diagnostics.jobs_failed"></p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Main content -->
        <div class="space-y-4 xl:col-span-9">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                <!-- 1. Send to Worker Card -->
                <section class="card-hover group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all">
                <div class="absolute inset-0 -z-10 opacity-0 transition-opacity group-hover:opacity-100 bg-gradient-to-br from-purple-50 via-transparent to-transparent"></div>
                <div class="mb-4 flex items-center gap-3">
                    <div class="gradient-primary rounded-xl p-2.5 text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900">Enviar a Trabajador</h2>
                </div>

                <form method="POST" action="{{ route('openwa.collab.send.user') }}" class="space-y-3">
                    @csrf
                    <!-- Worker Search -->
                    <div x-data="{ open: false, selected: null, query: '', results: [] }" class="relative">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Buscar trabajador</label>
                        <input
                            type="text"
                            x-model="query"
                            @focus="open = true"
                            @input="if (query.length >= 2) { fetch('{{ route('openwa.collab.search-trabajadores') }}?q=' + encodeURIComponent(query)).then(r => r.json()).then(d => results = d) }"
                            class="input-focus w-full rounded-lg border-2 border-slate-200 bg-white px-4 py-2.5 text-slate-900 placeholder-slate-400 transition-all focus:border-purple-500 focus:outline-none"
                            placeholder="Ej: Alejandro, 34622435165..."
                        >
                        <div x-show="open && results.length > 0" @click.outside="open = false; results = []" class="absolute top-full left-0 right-0 mt-2 max-h-48 overflow-y-auto rounded-xl border-2 border-slate-200 bg-white shadow-lg z-10">
                            <template x-for="result in results" :key="result.id">
                                <div @click="selected = result; open = false; query = result.label" class="cursor-pointer border-b border-slate-100 px-4 py-3 text-sm transition-colors hover:bg-purple-50">
                                    <div class="font-semibold text-slate-900" x-text="result.label"></div>
                                    <div class="mt-1 text-xs text-slate-500" x-text="result.email"></div>
                                </div>
                            </template>
                        </div>
                        <input type="hidden" name="trabajador_id" :value="selected?.id || ''">
                    </div>

                    <!-- Message Textarea -->
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mensaje</label>
                        <textarea name="message" rows="3" class="input-focus w-full rounded-lg border-2 border-slate-200 bg-white px-4 py-2.5 text-slate-900 placeholder-slate-400 transition-all focus:border-purple-500 focus:outline-none resize-none" placeholder="Escribe tu mensaje... (máx. 2000 caracteres)" required></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-glow w-full gradient-primary rounded-lg px-6 py-2.5 font-semibold text-white shadow-md transition-all hover:shadow-lg active:scale-95">
                        Enviar a trabajador
                    </button>
                </form>
                </section>

                <!-- 3. Send to Group -->
                <section class="card-hover group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all">
                <div class="absolute inset-0 -z-10 opacity-0 transition-opacity group-hover:opacity-100 bg-gradient-to-br from-green-50 via-transparent to-transparent"></div>
                <div class="mb-4 flex items-center gap-3">
                    <div class="gradient-success rounded-xl p-2.5 text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0H9m6 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900">Enviar a Grupo</h2>
                </div>

                @if ($groups->isEmpty() && $openwaSessionGroups->isEmpty())
                    <div class="rounded-xl border-2 border-slate-200 bg-slate-50 p-4 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5-4a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <p class="text-sm text-slate-600 font-medium">No hay grupos creados</p>
                        <p class="text-xs text-slate-500 mt-1">Crea un grupo en OpenWA para empezar</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @if ($groups->isNotEmpty())
                            <form method="POST" action="{{ route('openwa.collab.send.group') }}" class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                @csrf
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Grupo local (flujo actual)</label>
                                    <select name="group_id" class="input-focus w-full rounded-lg border-2 border-slate-200 bg-white px-4 py-2.5 text-slate-900 transition-all focus:border-blue-500 focus:outline-none" required>
                                        <option value="">-- Selecciona un grupo --</option>
                                        @foreach ($groups as $g)
                                            <option value="{{ $g->id }}">{{ $g->name }} • {{ $g->member_count }} miembros</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Mensaje</label>
                                    <textarea name="message" rows="3" class="input-focus w-full rounded-lg border-2 border-slate-200 bg-white px-4 py-2.5 text-slate-900 placeholder-slate-400 transition-all focus:border-blue-500 focus:outline-none resize-none" placeholder="Escribe tu mensaje..." required></textarea>
                                </div>

                                <button type="submit" class="btn-glow w-full rounded-lg bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-2.5 font-semibold text-white shadow-md transition-all hover:shadow-lg active:scale-95">
                                    Enviar a {{ $groups->sum('member_count') }} miembros
                                </button>
                            </form>
                        @endif

                        @if ($openwaSessionGroups->isNotEmpty())
                            <form method="POST" action="{{ route('openwa.collab.send.group.openwa') }}" class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
                                @csrf
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-emerald-900">Grupo OpenWA (chat_id real)</label>
                                    <select name="chat_id" class="input-focus w-full rounded-lg border-2 border-emerald-200 bg-white px-4 py-2.5 text-slate-900 transition-all focus:border-emerald-500 focus:outline-none" required>
                                        <option value="">-- Selecciona un grupo OpenWA --</option>
                                        @foreach ($openwaSessionGroups as $g)
                                            <option value="{{ $g['chat_id'] }}">{{ $g['name'] }} • {{ $g['chat_id'] }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-emerald-800">Este modo envía directamente al chat_id del grupo en OpenWA.</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-emerald-900">Mensaje</label>
                                    <textarea name="message" rows="3" class="input-focus w-full rounded-lg border-2 border-emerald-200 bg-white px-4 py-2.5 text-slate-900 placeholder-slate-400 transition-all focus:border-emerald-500 focus:outline-none resize-none" placeholder="Escribe tu mensaje..." required></textarea>
                                </div>

                                <button type="submit" class="btn-glow w-full rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-2.5 font-semibold text-white shadow-md transition-all hover:shadow-lg active:scale-95">
                                    Enviar al grupo OpenWA seleccionado
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
                </section>
            </div>

            <!-- 4. Groups Table -->
            <section class="card-hover group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all">
                <div class="absolute inset-0 -z-10 opacity-0 transition-opacity group-hover:opacity-100 bg-gradient-to-br from-amber-50 via-transparent to-transparent"></div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 p-2.5 text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900">Grupos de la sesion OpenWA</h2>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ $openwaSessionGroups->count() ?: $groups->count() }} grupos</span>
                </div>

                @if (!empty($sessionGroupsError))
                    <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        No se pudieron leer los grupos desde OpenWA: {{ $sessionGroupsError }}
                    </div>
                @endif

                @if ($openwaSessionGroups->isNotEmpty())
                    <div class="mb-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                        Mostrando grupos reales de la sesion conectada.
                    </div>
                    <div class="max-h-56 overflow-x-auto overflow-y-auto -mx-5 -mb-5">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-gradient-to-r from-slate-50 to-slate-100">
                                <tr class="border-b-2 border-slate-100">
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Grupo</th>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Miembros</th>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">ID Chat</th>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Origen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($openwaSessionGroups as $g)
                                    <tr class="transition-colors hover:bg-slate-50 cursor-pointer">
                                        <td class="px-5 py-3">
                                            <div class="inline-flex items-center gap-2">
                                                <div class="h-2 w-2 rounded-full bg-gradient-success"></div>
                                                <span class="font-semibold text-slate-900">{{ $g['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3">
                                            @if (!is_null($g['member_count']))
                                                <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                    {{ $g['member_count'] }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-500">N/D</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="font-mono text-xs text-slate-500">{{ substr($g['chat_id'], 0, 26) }}...</span>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-600 whitespace-nowrap">OpenWA</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($groups->isNotEmpty())
                    <div class="mb-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        OpenWA no devolvio grupos. Mostrando grupos locales guardados.
                    </div>
                    <div class="max-h-56 overflow-x-auto overflow-y-auto -mx-5 -mb-5">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-gradient-to-r from-slate-50 to-slate-100">
                                <tr class="border-b-2 border-slate-100">
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Grupo</th>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Miembros</th>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">ID Chat</th>
                                    <th class="px-5 py-3 text-left font-semibold text-slate-700">Creado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($groups as $g)
                                    <tr class="transition-colors hover:bg-slate-50 cursor-pointer">
                                        <td class="px-5 py-3">
                                            <div class="inline-flex items-center gap-2">
                                                <div class="h-2 w-2 rounded-full bg-gradient-success"></div>
                                                <span class="font-semibold text-slate-900">{{ $g->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"></path>
                                                </svg>
                                                {{ $g->member_count }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="font-mono text-xs text-slate-500">{{ substr($g->chat_id, 0, 20) }}...</span>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-600 whitespace-nowrap">{{ $g->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM6 20a9 9 0 0118 0v-2"></path>
                        </svg>
                        <p class="text-sm text-slate-600 font-medium">No hay grupos registrados</p>
                        <p class="text-xs text-slate-500 mt-1">Los grupos de OpenWA o los que guardes localmente apareceran aqui</p>
                    </div>
                @endif
            </section>

            <!-- Recent Messages -->
            <section
                x-data="recentMessagesFeed('{{ route('openwa.collab.recent-messages') }}')"
                x-init="initializeFeed()"
                x-on:beforeunload.window="stop()"
                class="card-hover group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all"
            >
        <div class="absolute inset-0 -z-10 opacity-0 transition-opacity group-hover:opacity-100 bg-gradient-to-br from-indigo-50 via-transparent to-transparent"></div>
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 p-2.5 text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-slate-900">Actividad Reciente</h2>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-slate-500">Últimos 20 mensajes</span>
                <button
                    type="button"
                    @click="refresh()"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refrescar
                </button>
            </div>
        </div>
        <div class="mb-4 flex items-center justify-between text-xs text-slate-500">
            <span>Estados visibles: pending, sent, failed, delivered y read.</span>
            <span x-text="lastUpdated ? `Actualizado a las ${lastUpdated}` : 'Cargando…'"></span>
        </div>

        <div x-ref="feed" class="max-h-72 overflow-y-auto">
            @include('openwa.partials.recent-messages-table', ['recentMessages' => $recentMessages])
        </div>
            </section>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-12 border-t border-slate-200 pt-6 text-center">
        <p class="text-xs text-slate-500">
            <span class="inline-flex items-center gap-1">
                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                OpenWA Pro © 2026
            </span>
        </p>
    </div>
    </div>
@endsection
