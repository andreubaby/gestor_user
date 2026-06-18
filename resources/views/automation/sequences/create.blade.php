@extends('layouts.unified-automation')

@section('title', 'Nueva Secuencia | Automatizacion WhatsApp')

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
        .animate-slide-in { animation: slideInUp 0.6s ease-out forwards; }

        .card-hover { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15); }

        .btn-hover { transition: all 0.2s ease; }
        .btn-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }

        .input-focus:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .step-builder {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(96, 165, 250, 0.05) 100%);
            border: 2px dashed rgba(59, 130, 246, 0.3);
        }

        .step-item {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
            border-left: 4px solid #2563eb;
        }

        .panel {
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .panel.active {
            max-height: 500px;
        }

        .sticky-cta {
            position: sticky;
            bottom: 0;
            z-index: 30;
            backdrop-filter: blur(8px);
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen text-slate-900">
    <main class="max-w-5xl mx-auto px-6 py-12">
        <x-ui.section-heading title="Nueva Secuencia" subtitle="Configura pasos, destino y adjuntos en un flujo limpio y consistente." />

        <div class="mb-4 flex flex-wrap items-center gap-2 text-xs">
            <a href="#section-basic" class="px-3 py-1 rounded-lg bg-white/80 text-slate-700 border border-slate-200 hover:bg-white transition">Informacion</a>
            <a href="#section-steps" class="px-3 py-1 rounded-lg bg-white/80 text-slate-700 border border-slate-200 hover:bg-white transition">Pasos</a>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-700 text-sm font-medium animate-slide-in">
                <p class="font-semibold mb-2">⚠️ Errores encontrados:</p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('automation.sequences.store') }}" class="space-y-8">
            @csrf

            <!-- Sección 1: Información Básica -->
            <section id="section-basic" class="gradient-card rounded-2xl p-8 card-hover animate-slide-in">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"/></svg>
                    </div>
                    <h2 class="title-font text-xl font-bold">Información Básica</h2>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    @if(($templates ?? collect())->isNotEmpty())
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                            <label class="block text-xs font-semibold uppercase text-blue-700 mb-2">Crear desde plantilla</label>
                            <div class="flex gap-2">
                                <select id="template-selector" class="flex-1 px-3 py-2 rounded-lg border border-blue-300 bg-white text-sm">
                                    <option value="">-- Selecciona una plantilla --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="apply-template-btn" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">Cargar</button>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nombre *</label>
                        <input type="text" name="name" required maxlength="120" value="{{ old('name') }}" placeholder="Ej: Onboarding nuevos colaboradores" class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white input-focus outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Descripción</label>
                        <textarea name="description" rows="2" placeholder="Describe qué hace esta secuencia..." class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white input-focus outline-none resize-none">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Estado *</label>
                            <select name="status" required class="w-full px-4 py-3 rounded-lg border border-slate-300 bg-white input-focus outline-none">
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}> Activo</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>⭕ Inactivo</option>
                                <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>⏸️ En pausa</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" id="add-step-btn" class="w-full px-4 py-3 gradient-primary rounded-lg text-white font-semibold btn-hover shadow-lg flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Añadir Paso
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección 2: Constructor de Pasos -->
            <section id="section-steps" class="animate-slide-in" style="animation-delay: 0.1s">
                <div class="gradient-card rounded-2xl p-8 card-hover">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 gradient-primary rounded-lg flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <h2 class="title-font text-xl font-bold">Pasos de WhatsApp</h2>
                    </div>

                    <div id="steps-container" class="space-y-4 min-h-[200px]">
                        <div class="step-builder rounded-lg p-8 text-center">
                            <svg class="w-12 h-12 text-blue-400 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <p class="text-slate-600 font-medium">Haz clic en "Añadir Paso" para comenzar</p>
                        </div>
                    </div>

                    <input type="hidden" id="steps-input" name="actions" value="{{ old('actions', '[]') }}">
                </div>
            </section>

            <!-- Botones de Acción -->
            <div class="sticky-cta mt-4 animate-slide-in rounded-2xl border border-slate-200/80 bg-white/95 p-3 shadow-lg" style="animation-delay: 0.2s">
                <div class="mb-2 flex items-center justify-between gap-2 text-xs text-slate-500">
                    <span>Tip: usa Ctrl + Enter para guardar rapido</span>
                    <a href="#section-steps" class="font-semibold text-blue-700 hover:text-blue-800">Ir a pasos</a>
                </div>
                <div class="flex gap-3">
                <a href="{{ route('automation.sequences.index') }}" class="flex-1 px-6 py-3 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold text-center btn-hover hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" class="flex-1 px-6 py-3 gradient-primary rounded-lg text-white font-semibold btn-hover shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Crear Secuencia
                </button>
                </div>
            </div>
        </form>
    </main>
</div>

<template id="step-template">
    <div class="step-item rounded-2xl p-6 card-hover group">
        <div class="flex items-start justify-between mb-4 pb-4 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-sm step-number">1</div>
                <p class="title-font font-semibold text-slate-900">Paso <span class="step-num">1</span></p>
            </div>
            <button type="button" class="remove-step px-3 py-1 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-700 text-xs font-semibold transition-all opacity-0 group-hover:opacity-100">
                Eliminar
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Tipo de Destino</label>
                <select name="steps[__INDEX__][type]" class="target-type w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
                    <option value="person"> Persona</option>
                    <option value="local_group"> Grupo Local</option>
                    <option value="openwa_group"> Grupo OpenWA</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Retraso (min)</label>
                <input type="number" name="steps[__INDEX__][delay_minutes]" value="0" min="0" class="delay-minutes w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
            </div>
        </div>

        <!-- Panel Persona -->
        <div class="panel panel-person">
            <div class="space-y-4 mb-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Modo</label>
                    <select name="steps[__INDEX__][person_mode]" class="person-mode w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
                        <option value="phone">☎️ Teléfono directo</option>
                        <option value="worker"> Buscar trabajador</option>
                    </select>
                </div>

                <div class="person-phone-box">
                    <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Teléfono</label>
                    <input type="text" name="steps[__INDEX__][phone]" placeholder="34622435165" class="person-phone w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
                </div>

                <div class="person-worker-box hidden">
                    <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Buscar Trabajador</label>
                    <input type="text" class="worker-search w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none" placeholder="Nombre, email o teléfono...">
                    <input type="hidden" name="steps[__INDEX__][trabajador_id]" class="worker-id">
                    <p class="worker-selected text-xs text-slate-600 mt-2">Sin seleccionar</p>
                </div>
            </div>
        </div>

        <!-- Panel Grupo Local -->
        <div class="panel panel-local-group hidden">
            <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Grupo Local</label>
            <select name="steps[__INDEX__][group_id]" class="local-group w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
                <option value="">-- Selecciona un grupo --</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Panel Grupo OpenWA -->
        <div class="panel panel-openwa-group hidden">
            <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Grupo OpenWA</label>
            <select name="steps[__INDEX__][chat_id]" class="openwa-group w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
                <option value="">-- Selecciona un grupo --</option>
                @forelse ($openwaSessionGroups as $group)
                    <option value="{{ $group['chat_id'] ?? '' }}">{{ $group['name'] ?? 'Grupo sin nombre' }}</option>
                @empty
                    <option value="" disabled>No hay grupos OpenWA disponibles</option>
                @endforelse
            </select>
        </div>

        <!-- Mensaje -->
        <div class="mt-4">
            <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Mensaje (opcional si adjuntas archivo)</label>
            <textarea name="steps[__INDEX__][message]" rows="3" placeholder="Escribe el mensaje a enviar..." class="message w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none resize-none"></textarea>
        </div>

        <!-- Adjunto opcional -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">URL archivo (opcional)</label>
                <input type="url" name="steps[__INDEX__][attachment_url]" placeholder="https://.../archivo.pdf" class="attachment-url w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 uppercase block mb-2">Nombre archivo (opcional)</label>
                <input type="text" name="steps[__INDEX__][attachment_name]" placeholder="catalogo.pdf o video.mp4" class="attachment-name w-full px-3 py-2 rounded-lg border border-slate-300 bg-white input-focus outline-none">
            </div>
        </div>

        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs font-semibold text-slate-600 uppercase mb-2">Subir desde tu ordenador</p>
            <div class="flex flex-wrap items-center gap-2">
                <input type="file" class="attachment-file text-xs" multiple accept=".pdf,.mp4,.mov,.avi,.mkv,.webm,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip">
                <button type="button" class="upload-attachment-btn px-3 py-1 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition">Subir archivo</button>
                <span class="upload-status text-xs text-slate-500">Sin archivo</span>
            </div>
            <div class="mt-2 attachment-list text-xs text-slate-600"></div>
        </div>
    </div>
</template>

@endsection

@section('page_scripts')
@php
    $templatesJson = ($templates ?? collect())->keyBy('id')->map(function ($t) {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'description' => $t->description,
            'actions' => $t->actions,
        ];
    })->toArray();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const container = document.getElementById('steps-container');
    const template = document.getElementById('step-template');
    const addBtn = document.getElementById('add-step-btn');
    const stepsInput = document.getElementById('steps-input');
    const uploadUrl = @json(route('automation.api.upload-attachment'));
    const csrfToken = @json(csrf_token());
    const templateSelector = document.getElementById('template-selector');
    const applyTemplateBtn = document.getElementById('apply-template-btn');
    const templates = @json($templatesJson);
    const autosaveKey = 'automation-sequence-create-draft-v1';

    let stepIndex = 0;

    function autoSelectSingleOption(selectElement) {
        if (!selectElement || selectElement.value) return;
        const validOptions = Array.from(selectElement.options).filter((option) => option.value && !option.disabled);
        if (validOptions.length === 1) {
            selectElement.value = validOptions[0].value;
        }
    }

    function createStep(data = {}) {
        const html = template.innerHTML.replaceAll('__INDEX__', stepIndex);
        const el = document.createElement('div');
        el.innerHTML = html;
        const step = el.firstElementChild;

        // Set values
        if (data.type) step.querySelector('.target-type').value = data.type;
        if (data.delay_minutes) step.querySelector('.delay-minutes').value = data.delay_minutes;
        if (data.person_mode) step.querySelector('.person-mode').value = data.person_mode;
        if (data.phone) step.querySelector('.person-phone').value = data.phone;
        if (data.group_id) step.querySelector('.local-group').value = data.group_id;
        if (data.chat_id) step.querySelector('.openwa-group').value = data.chat_id;
        if (data.message) step.querySelector('.message').value = data.message;
        if (data.attachment_url) step.querySelector('.attachment-url').value = data.attachment_url;
        if (data.attachment_name) step.querySelector('.attachment-name').value = data.attachment_name;
        if (Array.isArray(data.attachment_urls) && data.attachment_urls.length > 0) {
            step.querySelector('.attachment-url').value = data.attachment_urls[0] || '';
            if (Array.isArray(data.attachment_names) && data.attachment_names.length > 0) {
                step.querySelector('.attachment-name').value = data.attachment_names[0] || '';
            }
            step.dataset.attachmentUrls = JSON.stringify(data.attachment_urls);
            step.dataset.attachmentNames = JSON.stringify(data.attachment_names || []);
        }

        // Events
        const typeSelect = step.querySelector('.target-type');
        const modeSelect = step.querySelector('.person-mode');
        const removeBtn = step.querySelector('.remove-step');
        const uploadBtn = step.querySelector('.upload-attachment-btn');
        const fileInput = step.querySelector('.attachment-file');
        const uploadStatus = step.querySelector('.upload-status');
        const attachmentUrlInput = step.querySelector('.attachment-url');
        const attachmentNameInput = step.querySelector('.attachment-name');
        const attachmentList = step.querySelector('.attachment-list');

        autoSelectSingleOption(step.querySelector('.local-group'));
        autoSelectSingleOption(step.querySelector('.openwa-group'));

        function parseStepAttachments() {
            let attachmentUrls = [];
            let attachmentNames = [];

            try {
                attachmentUrls = JSON.parse(step.dataset.attachmentUrls || '[]');
                attachmentNames = JSON.parse(step.dataset.attachmentNames || '[]');
            } catch (_) {
                attachmentUrls = [];
                attachmentNames = [];
            }

            if (attachmentUrls.length === 0 && (attachmentUrlInput?.value || '').trim() !== '') {
                attachmentUrls = [(attachmentUrlInput?.value || '').trim()];
                attachmentNames = [((attachmentNameInput?.value || '').trim())];
            }

            return attachmentUrls.map((url, i) => ({
                url,
                name: attachmentNames[i] || '',
            })).filter((item) => item.url);
        }

        function writeStepAttachments(items) {
            const clean = items.filter((item) => item?.url).map((item) => ({
                url: String(item.url).trim(),
                name: String(item.name || '').trim(),
            }));

            step.dataset.attachmentUrls = JSON.stringify(clean.map((x) => x.url));
            step.dataset.attachmentNames = JSON.stringify(clean.map((x) => x.name));

            attachmentUrlInput.value = clean[0]?.url || '';
            attachmentNameInput.value = clean[0]?.name || '';

            renderAttachmentList();
            updateStepsInput();
        }

        function renderAttachmentList() {
            if (!attachmentList) return;
            const items = parseStepAttachments();

            if (items.length === 0) {
                attachmentList.innerHTML = '<p class="text-slate-500">Sin archivos adjuntos.</p>';
                return;
            }

            attachmentList.innerHTML = items.map((item, idx) => `
                <div class="flex items-center justify-between gap-2 rounded border border-slate-200 bg-white px-2 py-1 mb-1">
                    <a class="text-blue-600 underline break-all" href="${item.url}" target="_blank">${item.name || item.url}</a>
                    <button type="button" class="remove-attachment-btn text-red-600 font-semibold" data-idx="${idx}">Quitar</button>
                </div>
            `).join('');

            attachmentList.querySelectorAll('.remove-attachment-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const idx = Number(btn.getAttribute('data-idx'));
                    const next = parseStepAttachments().filter((_, i) => i !== idx);
                    writeStepAttachments(next);
                });
            });
        }

        function updateVisibility() {
            const type = typeSelect.value;
            const panels = {
                person: step.querySelector('.panel-person'),
                local_group: step.querySelector('.panel-local-group'),
                openwa_group: step.querySelector('.panel-openwa-group')
            };

            Object.entries(panels).forEach(([k, p]) => {
                if (!p) return;
                const isCurrent = k === type;
                p.classList.toggle('active', isCurrent);
                p.classList.toggle('hidden', !isCurrent);
            });

            if (type === 'person') {
                const mode = modeSelect.value;
                step.querySelector('.person-phone-box').classList.toggle('hidden', mode !== 'phone');
                step.querySelector('.person-worker-box').classList.toggle('hidden', mode !== 'worker');
            }

            updateStepsInput();
        }

        typeSelect.addEventListener('change', updateVisibility);
        modeSelect.addEventListener('change', updateVisibility);
        removeBtn.addEventListener('click', () => { step.remove(); stepIndex--; reindex(); updateStepsInput(); });

        // Búsqueda de trabajadores
        const workerSearch = step.querySelector('.worker-search');
        const workerId = step.querySelector('.worker-id');
        const workerSelected = step.querySelector('.worker-selected');

        if (workerSearch) {
            let searchTimeout;
            workerSearch.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                const query = workerSearch.value.trim();

                if (query.length < 2) {
                    workerSelected.textContent = 'Sin seleccionar';
                    return;
                }

                searchTimeout = setTimeout(async () => {
                    try {
                        const response = await fetch(`{{ route('automation.api.search-trabajadores') }}?q=${encodeURIComponent(query)}`);
                        if (!response.ok) return;

                        const results = await response.json();
                        console.log('Resultados de búsqueda:', results);

                        if (results.length > 0) {
                            const firstResult = results[0];
                            workerId.value = firstResult.id;
                            workerSelected.textContent = `✅ ${firstResult.label}`;
                            workerSearch.value = firstResult.nombre;
                        } else {
                            workerSelected.textContent = '❌ No encontrado';
                        }
                    } catch (error) {
                        console.error('Error en búsqueda:', error);
                        workerSelected.textContent = '⚠️ Error en búsqueda';
                    }
                }, 300);
            });
        }

        if (uploadBtn && fileInput && uploadStatus && attachmentUrlInput && attachmentNameInput) {
            uploadBtn.addEventListener('click', async () => {
                const files = Array.from(fileInput.files || []);

                if (files.length === 0) {
                    uploadStatus.textContent = 'Selecciona un archivo primero';
                    uploadStatus.className = 'upload-status text-xs text-amber-600';
                    return;
                }

                uploadStatus.textContent = 'Subiendo...';
                uploadStatus.className = 'upload-status text-xs text-blue-600';

                const formData = new FormData();
                files.forEach((file) => formData.append('files[]', file));

                try {
                    const response = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error('Error subiendo archivo');
                    }

                    const data = await response.json();
                    const uploaded = Array.isArray(data.files) ? data.files : [];
                    if (uploaded.length === 0) {
                        throw new Error('No se recibieron archivos subidos');
                    }

                    const urls = uploaded.map((f) => f.url).filter(Boolean);
                    const names = uploaded.map((f) => f.filename || '').filter((_, i) => Boolean(urls[i]));

                    const merged = [...parseStepAttachments(), ...urls.map((url, i) => ({ url, name: names[i] || '' }))];
                    const unique = [];
                    const seen = new Set();
                    merged.forEach((item) => {
                        if (seen.has(item.url)) return;
                        seen.add(item.url);
                        unique.push(item);
                    });

                    writeStepAttachments(unique);

                    uploadStatus.textContent = `${uploaded.length} archivo(s) subido(s)`;
                    uploadStatus.className = 'upload-status text-xs text-emerald-600';
                } catch (error) {
                    uploadStatus.textContent = 'Error al subir archivo';
                    uploadStatus.className = 'upload-status text-xs text-red-600';
                    console.error(error);
                }
            });
        }

        attachmentUrlInput?.addEventListener('change', () => {
            if ((step.dataset.attachmentUrls || '[]') === '[]') {
                renderAttachmentList();
            }
        });

        attachmentNameInput?.addEventListener('change', () => {
            if ((step.dataset.attachmentUrls || '[]') === '[]') {
                renderAttachmentList();
            }
        });

        step.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('change', updateStepsInput);
            el.addEventListener('input', updateStepsInput);
        });

        if (container.querySelector('.step-builder')) {
            container.innerHTML = '';
        }

        container.appendChild(step);
        stepIndex++;
        updateVisibility();
        renderAttachmentList();
    }

    function clearSteps() {
        container.innerHTML = '';
        stepIndex = 0;
    }

    function reindex() {
        container.querySelectorAll('.step-item').forEach((step, idx) => {
            step.querySelector('.step-num').textContent = idx + 1;
            step.querySelector('.step-number').textContent = idx + 1;
            step.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/steps\[\d+]/, `steps[${idx}]`);
            });
        });
    }

    function updateStepsInput() {
        const steps = [];
        container.querySelectorAll('.step-item').forEach(step => {
            const type = step.querySelector('.target-type').value;
            const data = { type, delay_minutes: parseInt(step.querySelector('.delay-minutes').value) || 0, message: step.querySelector('.message').value };
            const attachmentUrl = step.querySelector('.attachment-url')?.value || '';
            const attachmentName = step.querySelector('.attachment-name')?.value || '';

            let attachmentUrls = [];
            let attachmentNames = [];

            try {
                attachmentUrls = JSON.parse(step.dataset.attachmentUrls || '[]');
                attachmentNames = JSON.parse(step.dataset.attachmentNames || '[]');
            } catch (_) {
                attachmentUrls = [];
                attachmentNames = [];
            }

            if (attachmentUrls.length === 0 && attachmentUrl.trim() !== '') {
                attachmentUrls = [attachmentUrl.trim()];
                attachmentNames = [attachmentName.trim()];
            }

            if (attachmentUrls.length > 0) {
                data.attachment_urls = attachmentUrls;
                data.attachment_names = attachmentNames;
                data.attachment_url = attachmentUrls[0];
                data.attachment_name = attachmentNames[0] || '';
            }

            if (type === 'person') {
                const mode = step.querySelector('.person-mode').value;
                data.person_mode = mode;
                if (mode === 'phone') data.phone = step.querySelector('.person-phone').value;
                else data.trabajador_id = step.querySelector('.worker-id').value;
            } else if (type === 'local_group') {
                data.group_id = step.querySelector('.local-group').value;
            } else if (type === 'openwa_group') {
                data.chat_id = step.querySelector('.openwa-group').value;
            }

            steps.push(data);
        });
        stepsInput.value = JSON.stringify(steps);
    }

    addBtn.addEventListener('click', () => { createStep(); reindex(); });

    autoSelectSingleOption(templateSelector);

    applyTemplateBtn?.addEventListener('click', () => {
        const templateId = templateSelector?.value;
        if (!templateId || !templates[templateId]) return;

        const selected = templates[templateId];
        const actions = Array.isArray(selected.actions) ? selected.actions : [];

        document.querySelector('input[name="name"]').value = `${selected.name} (nueva)`;
        document.querySelector('textarea[name="description"]').value = selected.description || '';

        clearSteps();
        if (actions.length > 0) {
            actions.forEach((s) => createStep(s));
        } else {
            createStep();
        }
        reindex();
        updateStepsInput();
    });

    function persistDraft() {
        const payload = {
            name: document.querySelector('input[name="name"]')?.value || '',
            description: document.querySelector('textarea[name="description"]')?.value || '',
            status: document.querySelector('select[name="status"]')?.value || 'active',
            actions: stepsInput.value || '[]',
            saved_at: Date.now(),
        };
        localStorage.setItem(autosaveKey, JSON.stringify(payload));
    }

    function restoreDraft() {
        try {
            const raw = localStorage.getItem(autosaveKey);
            if (!raw) return false;
            const draft = JSON.parse(raw);
            if (!draft || typeof draft !== 'object') return false;

            const shouldRestore = window.confirm('Se encontró un borrador local de esta secuencia. ¿Quieres restaurarlo?');
            if (!shouldRestore) return false;

            document.querySelector('input[name="name"]').value = draft.name || '';
            document.querySelector('textarea[name="description"]').value = draft.description || '';
            document.querySelector('select[name="status"]').value = draft.status || 'active';

            const actions = JSON.parse(draft.actions || '[]');
            clearSteps();
            if (Array.isArray(actions) && actions.length > 0) {
                actions.forEach((s) => createStep(s));
            } else {
                createStep();
            }
            reindex();
            updateStepsInput();
            return true;
        } catch (_) {
            return false;
        }
    }

    let saveTimeout;
    document.querySelectorAll('input, textarea, select').forEach((el) => {
        el.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(persistDraft, 300);
        });
        el.addEventListener('change', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(persistDraft, 150);
        });
    });

    form?.addEventListener('submit', () => {
        localStorage.removeItem(autosaveKey);
    });

    document.addEventListener('keydown', (event) => {
        if (event.ctrlKey && event.key === 'Enter' && form) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    // Load old data
    try {
        const old = {!! json_encode(old('steps', [])) !!};
        if (Array.isArray(old) && old.length > 0) {
            old.forEach(s => createStep(s));
        } else {
            createStep();
        }
    } catch (e) {
        createStep();
    }

    restoreDraft();

    reindex();
});
</script>
@endsection

