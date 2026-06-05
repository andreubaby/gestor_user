@extends('layouts.unified-automation')

@section('title', 'OpenWA | Mensajes automaticos')

@push('head')
    <style>
        * { font-family: 'Inter', sans-serif; }
        .card-hover { transition: all .25s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 20px 35px rgba(15, 23, 42, 0.08); }
    </style>
@endpush

@section('content')
<div class="text-slate-900">
@php
    $active = 'openwa-automation';
    $automationName = old('automation_name', '');
@endphp

<div class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.card class="mb-6 rounded-3xl border-emerald-200 bg-white/85 backdrop-blur" :hover="false" padding="lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">OpenWA</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">Mensajes automáticos</h1>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">
                    Encadena varios mensajes en orden para personas, grupos locales o grupos reales de OpenWA.
                    Cada paso se encola respetando el retraso acumulado.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button as="a" :href="route('gestor.gestoria')" variant="secondary" size="lg">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Dashboard
                </x-ui.button>
                <x-ui.button as="a" :href="route('openwa.collab.index')" variant="success" size="lg">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6m0 0v6m0-6L10 16M5 7h4m-4 0v10a2 2 0 002 2h10"/></svg>
                    Colaboraciones
                </x-ui.button>
                <x-ui.button as="a" :href="route('automation.sequences.index')" size="lg">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Secuencias Programadas
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>

    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm">
            <span class="font-semibold">Éxito:</span> {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm">
            <span class="font-semibold">Error:</span> {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm">
            <p class="font-semibold">Revisa los campos de la secuencia:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
        <main class="space-y-5 xl:col-span-8">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm card-hover">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Nueva secuencia automática</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Añade tantos pasos como necesites. El retraso se aplica de forma acumulada entre pasos.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600">
                        <p><span class="font-semibold text-slate-900">Tipos:</span> personas, grupos locales y grupos OpenWA</p>
                        <p class="mt-1"><span class="font-semibold text-slate-900">Orden:</span> el mismo que ves en la lista</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('openwa.auto.send') }}" id="automation-form" class="mt-6 space-y-5">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="automation_name" class="mb-2 block text-sm font-semibold text-slate-700">Nombre de la secuencia</label>
                            <input id="automation_name" type="text" name="automation_name" value="{{ $automationName }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="Ej: Bienvenida nuevos clientes">
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-semibold text-slate-700">Acciones</p>
                            <div class="flex flex-wrap gap-3">
                                <button type="button" id="add-step-btn" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <span>+</span> Añadir paso
                                </button>
                                <button type="button" id="reset-steps-btn" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Limpiar secuencia
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-3 text-sm text-emerald-900">
                        <p class="font-semibold">Consejo rápido</p>
                        <p class="mt-1">Si dos pasos tienen retraso cero, se envían seguidos respetando el orden de la lista.</p>
                    </div>

                    <div id="automation-steps" class="space-y-4"></div>

                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-sm text-slate-600">
                            Cuando pulses <span class="font-semibold text-slate-900">Encolar secuencia</span>, cada paso se enviará según su retraso acumulado.
                        </p>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Encolar secuencia
                        </button>
                    </div>
                </form>
            </section>
        </main>

        <aside class="space-y-5 xl:col-span-4">
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm card-hover">
                <h2 class="text-lg font-semibold text-slate-900">Resumen de la secuencia</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pasos</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900" id="summary-step-count">0</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Retraso total</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900" id="summary-delay">0m</p>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vista previa</p>
                    <ul id="summary-list" class="mt-3 space-y-2 text-sm text-slate-700"></ul>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm card-hover">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">Estado OpenWA</h2>
                    @if (!empty($diagnostics['worker_active']))
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Worker activo</span>
                    @else
                        <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Worker inactivo</span>
                    @endif
                </div>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sesión</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $session['name'] ?? 'N/D' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jobs en cola</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $diagnostics['jobs_pending'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mensajes pending</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $diagnostics['messages_pending'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grupos locales</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $groups->count() }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grupos OpenWA</p>
                        <p class="mt-1 font-medium text-slate-900">{{ $openwaSessionGroups->count() }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm card-hover">
                <h2 class="text-lg font-semibold text-slate-900">Disponibles en esta secuencia</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-emerald-900">
                        <p class="font-semibold">Personas</p>
                        <p class="mt-1 text-xs text-emerald-800">Puedes enviar por teléfono directo o usando un trabajador.</p>
                    </div>
                    <div class="rounded-2xl bg-blue-50 px-4 py-3 text-blue-900">
                        <p class="font-semibold">Grupos locales</p>
                        <p class="mt-1 text-xs text-blue-800">Se envían al grupo local y luego a sus miembros, como en tu flujo actual.</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 px-4 py-3 text-amber-900">
                        <p class="font-semibold">Grupos OpenWA</p>
                        <p class="mt-1 text-xs text-amber-800">Envía directo al chat_id real del grupo conectado.</p>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>

<template id="automation-step-template">
    <article class="automation-step rounded-3xl border border-slate-200 bg-white p-5 shadow-sm" data-step-index="__INDEX__">
        <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Paso __STEP__</p>
                <h3 class="mt-1 text-lg font-semibold text-slate-900">Mensaje programado</h3>
            </div>
            <button type="button" data-remove-step class="inline-flex items-center gap-2 self-start rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                Eliminar
            </button>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label for="step-target-type-__INDEX__" class="mb-2 block text-sm font-semibold text-slate-700">Tipo de destino</label>
                <select id="step-target-type-__INDEX__" name="steps[__INDEX__][type]" data-target-type class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="person">Persona</option>
                    <option value="local_group">Grupo local</option>
                    <option value="openwa_group">Grupo OpenWA</option>
                </select>
            </div>

            <div>
                <label for="step-delay-__INDEX__" class="mb-2 block text-sm font-semibold text-slate-700">Retraso tras el paso anterior (min)</label>
                <input id="step-delay-__INDEX__" type="number" min="0" max="10080" value="0" name="steps[__INDEX__][delay_minutes]" data-delay class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
            </div>
        </div>

        <div data-panel-person class="mt-4 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="step-person-mode-__INDEX__" class="mb-2 block text-sm font-semibold text-emerald-900">Modo persona</label>
                    <select id="step-person-mode-__INDEX__" name="steps[__INDEX__][person_mode]" data-person-mode class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                        <option value="phone">Teléfono directo</option>
                        <option value="worker">Trabajador buscado</option>
                    </select>
                </div>
                <div data-person-phone-box>
                    <label for="step-phone-__INDEX__" class="mb-2 block text-sm font-semibold text-emerald-900">Teléfono</label>
                    <input id="step-phone-__INDEX__" type="text" name="steps[__INDEX__][phone]" data-person-phone class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="34622435165">
                </div>
            </div>

            <div data-person-worker-box class="mt-4 hidden">
                <label for="step-worker-search-__INDEX__" class="mb-2 block text-sm font-semibold text-emerald-900">Buscar trabajador</label>
                <input id="step-worker-search-__INDEX__" type="text" data-worker-search class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="Escribe nombre, email o teléfono...">
                <input type="hidden" name="steps[__INDEX__][trabajador_id]" data-worker-id>
                <div data-worker-results class="mt-2 hidden max-h-48 overflow-y-auto rounded-2xl border border-emerald-200 bg-white shadow-sm"></div>
                <p data-worker-selected class="mt-2 text-xs text-emerald-800">Ningún trabajador seleccionado.</p>
            </div>
        </div>

        <div data-panel-local-group class="mt-4 hidden rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
            <label for="step-local-group-__INDEX__" class="mb-2 block text-sm font-semibold text-blue-900">Grupo local</label>
            <select id="step-local-group-__INDEX__" name="steps[__INDEX__][group_id]" data-local-group class="w-full rounded-2xl border border-blue-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                <option value="">-- Selecciona un grupo --</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }} • {{ $group->member_count }} miembros</option>
                @endforeach
            </select>
        </div>

        <div data-panel-openwa-group class="mt-4 hidden rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
            <label for="step-openwa-group-__INDEX__" class="mb-2 block text-sm font-semibold text-amber-900">Grupo OpenWA</label>
            <select id="step-openwa-group-__INDEX__" name="steps[__INDEX__][chat_id]" data-openwa-group class="w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100">
                <option value="">-- Selecciona un grupo OpenWA --</option>
                @foreach ($openwaSessionGroups as $group)
                    <option value="{{ $group['chat_id'] }}">{{ $group['name'] }} • {{ $group['chat_id'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-4">
            <label for="step-message-__INDEX__" class="mb-2 block text-sm font-semibold text-slate-700">Mensaje</label>
            <textarea id="step-message-__INDEX__" name="steps[__INDEX__][message]" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="Escribe el mensaje de este paso..."></textarea>
        </div>
    </article>
</template>

<script>
    window.OPENWA_WORKER_SEARCH_URL = @json(route('openwa.collab.search-trabajadores'));
    window.OPENWA_OLD_STEPS = @json(old('steps', []));

    document.addEventListener('DOMContentLoaded', () => {
        const stepsContainer = document.getElementById('automation-steps');
        const template = document.getElementById('automation-step-template');
        const addStepBtn = document.getElementById('add-step-btn');
        const resetBtn = document.getElementById('reset-steps-btn');
        const summaryCount = document.getElementById('summary-step-count');
        const summaryDelay = document.getElementById('summary-delay');
        const summaryList = document.getElementById('summary-list');

        const sourceSteps = Array.isArray(window.OPENWA_OLD_STEPS) && window.OPENWA_OLD_STEPS.length > 0
            ? window.OPENWA_OLD_STEPS
            : [{}];

        const debounceTimers = new WeakMap();

        function buildStepHtml(index) {
            return template.innerHTML
                .replaceAll('__INDEX__', String(index))
                .replaceAll('__STEP__', String(index + 1));
        }

        function setValue(el, value) {
            if (!el) return;
            el.value = value ?? '';
        }

        function updatePanels(stepEl) {
            const typeSelect = stepEl.querySelector('[data-target-type]');
            const personPanel = stepEl.querySelector('[data-panel-person]');
            const localGroupPanel = stepEl.querySelector('[data-panel-local-group]');
            const openwaGroupPanel = stepEl.querySelector('[data-panel-openwa-group]');
            const personModeSelect = stepEl.querySelector('[data-person-mode]');
            const personPhoneBox = stepEl.querySelector('[data-person-phone-box]');
            const personWorkerBox = stepEl.querySelector('[data-person-worker-box]');

            const type = typeSelect?.value || 'person';
            personPanel?.classList.toggle('hidden', type !== 'person');
            localGroupPanel?.classList.toggle('hidden', type !== 'local_group');
            openwaGroupPanel?.classList.toggle('hidden', type !== 'openwa_group');

            if (type === 'person') {
                const mode = personModeSelect?.value || 'phone';
                personPhoneBox?.classList.toggle('hidden', mode !== 'phone');
                personWorkerBox?.classList.toggle('hidden', mode !== 'worker');
            }
        }

        function clearWorkerSelection(stepEl) {
            const workerId = stepEl.querySelector('[data-worker-id]');
            const workerSelected = stepEl.querySelector('[data-worker-selected]');
            if (workerId) workerId.value = '';
            if (workerSelected) workerSelected.textContent = 'Ningún trabajador seleccionado.';
        }

        function bindWorkerSearch(stepEl) {
            const searchInput = stepEl.querySelector('[data-worker-search]');
            const resultsBox = stepEl.querySelector('[data-worker-results]');
            const workerId = stepEl.querySelector('[data-worker-id]');
            const workerSelected = stepEl.querySelector('[data-worker-selected]');

            if (!searchInput || !resultsBox || !workerId || !workerSelected) return;

            searchInput.addEventListener('input', () => {
                const query = searchInput.value.trim();
                clearTimeout(debounceTimers.get(searchInput));

                if (query.length < 2) {
                    resultsBox.innerHTML = '';
                    resultsBox.classList.add('hidden');
                    return;
                }

                const timer = setTimeout(async () => {
                    try {
                        const response = await fetch(`${window.OPENWA_WORKER_SEARCH_URL}?q=${encodeURIComponent(query)}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!response.ok) return;

                        const results = await response.json();
                        resultsBox.innerHTML = '';

                        if (!Array.isArray(results) || results.length === 0) {
                            resultsBox.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">Sin resultados.</div>';
                            resultsBox.classList.remove('hidden');
                            return;
                        }

                        results.forEach((result) => {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'block w-full border-b border-slate-100 px-4 py-3 text-left text-sm transition hover:bg-emerald-50';
                            item.innerHTML = `<div class="font-semibold text-slate-900">${result.label}</div><div class="mt-1 text-xs text-slate-500">${result.email || ''}</div>`;
                            item.addEventListener('click', () => {
                                workerId.value = result.id;
                                searchInput.value = result.label;
                                workerSelected.textContent = `Seleccionado: ${result.label}`;
                                resultsBox.classList.add('hidden');
                            });
                            resultsBox.appendChild(item);
                        });

                        resultsBox.classList.remove('hidden');
                    } catch (error) {
                        console.error(error);
                    }
                }, 300);

                debounceTimers.set(searchInput, timer);
            });
        }

        function bindStep(stepEl) {
            const typeSelect = stepEl.querySelector('[data-target-type]');
            const personModeSelect = stepEl.querySelector('[data-person-mode]');
            const removeBtn = stepEl.querySelector('[data-remove-step]');
            const allInputs = stepEl.querySelectorAll('input, textarea, select');

            typeSelect?.addEventListener('change', () => {
                updatePanels(stepEl);
                refreshSummary();
            });

            personModeSelect?.addEventListener('change', () => {
                updatePanels(stepEl);
                clearWorkerSelection(stepEl);
                refreshSummary();
            });

            removeBtn?.addEventListener('click', () => {
                if (stepsContainer.children.length === 1) return;
                stepEl.remove();
                reindexSteps();
                refreshSummary();
            });

            allInputs.forEach((input) => {
                input.addEventListener('input', refreshSummary);
                input.addEventListener('change', refreshSummary);
            });

            bindWorkerSearch(stepEl);
            updatePanels(stepEl);
        }

        function reindexSteps() {
            Array.from(stepsContainer.children).forEach((stepEl, index) => {
                stepEl.dataset.stepIndex = String(index);
                const stepTitle = stepEl.querySelector('p.text-xs.font-semibold.uppercase');
                if (stepTitle) stepTitle.textContent = `Paso ${index + 1}`;

                stepEl.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/steps\[\d+]/, `steps[${index}]`);
                });
            });
        }

        function createStep(step = {}) {
            const index = stepsContainer.children.length;
            const wrapper = document.createElement('div');
            wrapper.innerHTML = buildStepHtml(index, step).trim();
            const stepEl = wrapper.firstElementChild;

            setValue(stepEl.querySelector('[data-target-type]'), step.type || 'person');
            setValue(stepEl.querySelector('[data-delay]'), step.delay_minutes ?? 0);
            setValue(stepEl.querySelector('[data-person-mode]'), step.person_mode || 'phone');
            setValue(stepEl.querySelector('[data-person-phone]'), step.phone || '');
            setValue(stepEl.querySelector('[data-worker-id]'), step.trabajador_id || '');
            setValue(stepEl.querySelector('[data-local-group]'), step.group_id || '');
            setValue(stepEl.querySelector('[data-openwa-group]'), step.chat_id || '');
            setValue(stepEl.querySelector('textarea[name$="[message]"]'), step.message || '');

            stepsContainer.appendChild(stepEl);
            bindStep(stepEl);
            updatePanels(stepEl);
            refreshSummary();
        }

        function clearSteps() {
            stepsContainer.innerHTML = '';
            createStep({});
        }

        function refreshSummary() {
            const stepEls = Array.from(stepsContainer.children);
            let totalDelay = 0;
            const listItems = [];

            stepEls.forEach((stepEl, index) => {
                const type = stepEl.querySelector('[data-target-type]')?.value || 'person';
                const delay = parseInt(stepEl.querySelector('[data-delay]')?.value || '0', 10) || 0;
                const message = (stepEl.querySelector('textarea[name$="[message]"]')?.value || '').trim();
                const label = type === 'person'
                    ? ((stepEl.querySelector('[data-person-mode]')?.value === 'worker')
                        ? 'Persona (trabajador)' : 'Persona (teléfono)')
                    : (type === 'local_group' ? 'Grupo local' : 'Grupo OpenWA');

                totalDelay += delay;
                        listItems.push(`<li class="rounded-2xl bg-white px-3 py-2 shadow-sm ring-1 ring-slate-100"><span class="font-semibold text-slate-900">Paso ${index + 1}</span> · ${label}${message ? `<div class="mt-1 text-xs text-slate-500 break-words">${message}</div>` : ''}<div class="mt-1 text-xs text-slate-400">+${delay} min tras el paso anterior</div></li>`);
            });

            summaryCount.textContent = String(stepEls.length);
            summaryDelay.textContent = `${totalDelay}m`;
            summaryList.innerHTML = listItems.length ? listItems.join('') : '<li class="text-slate-500">Añade un paso para ver la vista previa.</li>';
        }

        addStepBtn.addEventListener('click', () => createStep({}));
        resetBtn.addEventListener('click', clearSteps);

        sourceSteps.forEach((step) => createStep(step || {}));
        reindexSteps();
        refreshSummary();
    });
</script>
@endsection



