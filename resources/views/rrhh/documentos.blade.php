<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RRHH · Documentos</title>
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary:'#16a34a', secondary:'#15803d' },
                    boxShadow: { soft:'0 10px 25px rgba(2,6,23,.08)' }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-white text-slate-900">

<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

<header class="sticky top-0 z-40 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">RRHH · Documentos</h1>
            </div>
        </div>

        <nav class="flex flex-wrap items-center gap-2">
            <a href="{{ route('usuarios.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Listado
            </a>

            <a href="{{ route('usuarios.vincular') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Vincular
            </a>

            <a href="{{ route('fichajes.diarios.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                Fichajes diarios
            </a>

            <a href="{{ route('usuarios.onboarding.create') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                Onboarding
            </a>

            <a href="{{ route('rrhh.documentos.index') ?? '#' }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                RRHH
            </a>

            <a href="{{ route('groups.assign.create') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Asignar grupo
            </a>

            <a href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-red-50 hover:text-red-700 transition
                      focus:outline-none focus:ring-4 focus:ring-red-200">
                Salir
            </a>
        </nav>
    </div>
</header>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>

<main class="relative mx-auto max-w-5xl px-4 py-8 space-y-5">

    <section class="bg-white/80 backdrop-blur rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-emerald-800 tracking-tight">Generador de documentos</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Selecciona trabajador + puesto + documentos y genera el PDF (1) o descarga un ZIP (varios).
                </p>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="bg-white rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5">

        <!-- BÚSQUEDA + TIP (más aire) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-6 items-start">
            <div class="lg:col-span-8">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Buscar trabajador</label>

                <div class="relative">
                    <input id="workerSearch" type="text" inputmode="search"
                           placeholder="Escribe nombre, email o DNI…"
                           class="w-full pl-10 pr-28 px-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                          focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                    <div class="absolute inset-y-0 left-3 flex items-center text-slate-400">🔎</div>

                    <button type="button" id="clearSearch"
                            class="absolute inset-y-0 right-2 my-1.5 px-3 rounded-xl text-xs font-semibold
                           bg-slate-100 text-slate-700 hover:bg-slate-200 transition
                           focus:outline-none focus:ring-4 focus:ring-slate-200">
                        Limpiar
                    </button>
                </div>

                <div class="mt-2 text-xs text-slate-500">
                    <span id="workerCount">—</span>
                </div>
            </div>

            <div class="lg:col-span-4">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 text-emerald-700">💡</div>
                        <div>
                            <div class="text-xs font-semibold text-emerald-800">Tip</div>
                            <div class="text-sm text-emerald-900/80">
                                Filtra y si queda 1 resultado, se selecciona solo.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('rrhh.documentos.pdf') }}" class="space-y-4" id="rrhhForm">
            @csrf

            <!-- FORM (grid con más separación) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Trabajador</label>
                    <select name="trabajador_id" id="trabajador"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                           focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none"
                            required>
                        <option value="" selected disabled>Selecciona un trabajador…</option>
                        @foreach($workers as $w)
                            <option value="{{ $w->id }}"
                                    data-nombre="{{ $w->nombre }}"
                                    data-email="{{ $w->email }}"
                                    data-nif="{{ $w->nif ?? '' }}"
                                    data-empresa="{{ $w->empresa ?? '' }}">
                                {{ $w->nombre }} {{ (int)$w->activo === 1 ? '' : '(Inactivo)' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha</label>
                    <input type="date" name="fecha" id="fecha"
                           value="{{ now()->format('Y-m-d') }}"
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                          focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none"
                           required>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Puesto</label>
                    <select name="puesto" id="puesto"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                           focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none"
                            required>
                        <option value="" selected disabled>Selecciona un puesto…</option>
                        <option value="Oficina">Oficina</option>
                        <option value="Bandejero">Bandejero</option>
                        <option value="Injertos">Injertos</option>
                        <option value="Camionero">Camionero</option>
                        <option value="Producción">Producción</option>
                        <option value="Siembras">Siembras</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">PDF único</label>
                    <select name="tipo" id="tipoSingle"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                           focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                        <option value="" selected>— (usa ZIP si quieres varios)</option>
                        @foreach($tipos as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- ✅ CHIPS ABAJO (fila completa, no empuja el grid) -->
                <div class="md:col-span-12">
                    <div class="mt-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold text-slate-600">Accesos rápidos</div>
                                <div class="text-sm font-semibold text-slate-900">Puesto</div>
                            </div>
                            <button type="button" id="clearPuesto"
                                    class="px-3 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200
                                   text-slate-700 hover:bg-slate-100 transition
                                   focus:outline-none focus:ring-4 focus:ring-slate-200">
                                Quitar puesto
                            </button>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2" id="puestoChips">
                            @php($puestos = ['Oficina','Bandejero','Injertos','Camionero','Producción','Siembras'])
                            @foreach($puestos as $p)
                                <button type="button"
                                        data-puesto="{{ $p }}"
                                        class="chip rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700
                                       hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-800 transition
                                       focus:outline-none focus:ring-4 focus:ring-emerald-200">
                                    {{ $p }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- MULTI TIPOS -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Documentos para ZIP</div>
                        <div class="text-xs text-slate-500">Selecciona uno o varios y descarga el ZIP.</div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" id="selAll"
                                class="px-3 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 transition">
                            Seleccionar todo
                        </button>
                        <button type="button" id="selNone"
                                class="px-3 py-2 rounded-xl text-sm font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 transition">
                            Ninguno
                        </button>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2" id="tiposGrid">
                    @foreach($tipos as $k => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 hover:bg-slate-100 transition cursor-pointer">
                            <input type="checkbox" name="tipos[]" value="{{ $k }}"
                                   class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-200">
                            <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-3 text-xs text-slate-500 flex items-center justify-between gap-2">
                    <div>
                        Seleccionados: <span id="tiposCount" class="font-semibold">0</span>
                    </div>
                    <div id="zipHint" class="text-slate-500"></div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs text-slate-500">Resumen</div>
                <div class="mt-1 text-sm font-semibold text-slate-900" id="sumNombre">—</div>
                <div class="text-sm text-slate-700 break-all" id="sumEmail">—</div>
                <div class="text-xs text-slate-500 mt-1" id="sumExtra"></div>
                <div class="mt-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                        🧰 Puesto: <span id="sumPuesto" class="text-slate-900">—</span>
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" id="btnGenPdf"
                        class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold
                               hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200 shadow">
                    📄 Generar PDF (único)
                </button>

                <button type="button" id="btnGenNewTab"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-800 hover:bg-slate-200 transition font-semibold
                               focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                    Abrir PDF en nueva pestaña
                </button>

                <button type="button" id="btnZip"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold
                               hover:bg-indigo-700 transition focus:outline-none focus:ring-4 focus:ring-indigo-200 shadow
                               disabled:opacity-60 disabled:cursor-not-allowed">
                    📦 Descargar ZIP (seleccionados)
                </button>

                <span class="text-xs text-slate-500 self-center">
                    (se genera y se descarga/abre; RRHH lo guarda donde quiera)
                </span>
            </div>

        </form>
    </section>

</main>

<script>
    const trabajador = document.getElementById('trabajador');
    const fechaEl = document.getElementById('fecha');
    const puestoEl = document.getElementById('puesto');

    const sumNombre = document.getElementById('sumNombre');
    const sumEmail = document.getElementById('sumEmail');
    const sumExtra = document.getElementById('sumExtra');
    const sumPuesto = document.getElementById('sumPuesto');

    const form = document.getElementById('rrhhForm');
    const btnNewTab = document.getElementById('btnGenNewTab');

    const search = document.getElementById('workerSearch');
    const clearSearch = document.getElementById('clearSearch');
    const workerCount = document.getElementById('workerCount');

    const tiposGrid = document.getElementById('tiposGrid');
    const tiposCount = document.getElementById('tiposCount');
    const zipHint = document.getElementById('zipHint');

    const btnZip = document.getElementById('btnZip');
    const selAll = document.getElementById('selAll');
    const selNone = document.getElementById('selNone');

    const tipoSingle = document.getElementById('tipoSingle');

    const puestoChips = document.getElementById('puestoChips');

    const clearPuesto = document.getElementById('clearPuesto');
    clearPuesto?.addEventListener('click', () => {
        puestoEl.value = '';
        puestoEl.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // ✅ Presets: puesto -> tipos[] que se auto-marcan en el ZIP
    const presetsPorPuesto = {
        "Siembras": [
            "epis_general_entrega",
            "maq_siembra_aut",
            "vehiculo_uso_conservacion_aut",
            "entrega_info",
        ],
        "Oficina": [
            "epis_general_entrega",
            "vehiculo_uso_conservacion_aut",
            "entrega_info",
        ],
        "Bandejero": [
            "epis_bandejero_entrega",
            "maq_bandejero_aut",
            "vehiculo_uso_conservacion_aut",
            "entrega_info",
        ],

        "Injertos": [
            "epis_general_entrega",
            "maq_empaquetadora_injertadora_aut",
            "vehiculo_uso_conservacion_aut",
            "entrega_info",
        ],

        "Camionero": [
            "firma_epis_caminero",
            'maq_conductor_aut',
            "vehiculo_uso_conservacion_aut",
            "entrega_info",
        ],

        "Producción": [
            "epis_general_entrega",
            "maq_produccion_aut",
            'it2_manejo_segadora',
            'maq_semillero_aut',
            "vehiculo_uso_conservacion_aut",
            "entrega_info",
        ],
    };

    function applyPresetForPuesto(puestoValue) {
        if (!tiposGrid) return;

        const preset = presetsPorPuesto[puestoValue] || null;

        // Si no hay preset, no tocamos nada
        if (!preset) return;

        const wanted = new Set(preset);

        for (const cb of tiposGrid.querySelectorAll('input[type="checkbox"][name="tipos[]"]')) {
            cb.checked = wanted.has(cb.value);
        }

        refreshZipUI();
    }

    // Cache de opciones originales (menos placeholder)
    const placeholderOpt = trabajador.querySelector('option[value=""]');
    const allOptions = [...trabajador.querySelectorAll('option')].filter(o => o.value !== '');

    function refreshSummary() {
        const opt = trabajador?.selectedOptions?.[0];
        if (!opt || !opt.value) {
            sumNombre.textContent = '—';
            sumEmail.textContent = '—';
            sumExtra.textContent = '';
        } else {
            sumNombre.textContent = opt.dataset.nombre || '—';
            sumEmail.textContent = opt.dataset.email || '—';

            const nif = (opt.dataset.nif || '').trim();
            const emp = (opt.dataset.empresa || '').trim();

            sumExtra.textContent = [
                emp ? `Empresa: ${emp}` : '',
                nif ? `DNI: ${nif}` : ''
            ].filter(Boolean).join(' · ');
        }

        sumPuesto.textContent = (puestoEl?.value || '').trim() || '—';
    }

    function setChipActive() {
        const v = (puestoEl?.value || '').trim();
        if (!puestoChips) return;

        for (const b of puestoChips.querySelectorAll('button.chip')) {
            const isOn = (b.dataset.puesto || '') === v;
            b.classList.toggle('bg-emerald-600', isOn);
            b.classList.toggle('text-white', isOn);
            b.classList.toggle('border-emerald-600', isOn);

            b.classList.toggle('bg-white', !isOn);
            b.classList.toggle('text-slate-700', !isOn);
            b.classList.toggle('border-slate-200', !isOn);
        }
    }

    trabajador?.addEventListener('change', () => {
        refreshSummary();
        refreshZipUI();
    });
    fechaEl?.addEventListener('change', refreshZipUI);

    puestoEl?.addEventListener('change', () => {
        refreshSummary();
        setChipActive();
        applyPresetForPuesto((puestoEl?.value || '').trim());
        refreshZipUI();
    });

    // Chips: click -> selecciona en select
    puestoChips?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-puesto]');
        if (!btn) return;
        const v = btn.dataset.puesto || '';
        if (!v) return;
        puestoEl.value = v;
        puestoEl.dispatchEvent(new Event('change', { bubbles: true }));
    });

    refreshSummary();
    setChipActive();

    function normalize(s) {
        return String(s || '')
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function optionHaystack(opt) {
        const text = opt.textContent || '';
        const nombre = opt.dataset.nombre || '';
        const email = opt.dataset.email || '';
        const nif = opt.dataset.nif || '';
        return normalize(`${text} ${nombre} ${email} ${nif}`);
    }

    function applyFilter() {
        const q = normalize(search.value).trim();
        const prevValue = trabajador.value;

        let filtered = allOptions;
        if (q) filtered = allOptions.filter(opt => optionHaystack(opt).includes(q));

        trabajador.replaceChildren();
        trabajador.appendChild(placeholderOpt);
        for (const opt of filtered) trabajador.appendChild(opt);

        if (prevValue && filtered.some(o => o.value === prevValue)) {
            trabajador.value = prevValue;
            refreshSummary();
        } else if (filtered.length === 1) {
            trabajador.value = filtered[0].value;
            trabajador.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            trabajador.value = '';
            refreshSummary();
        }

        workerCount.textContent = q
            ? `Resultados: ${filtered.length} / ${allOptions.length}`
            : `Total: ${allOptions.length}`;

        refreshZipUI();
    }

    search?.addEventListener('input', applyFilter);

    clearSearch?.addEventListener('click', () => {
        search.value = '';
        applyFilter();
        search.focus();
    });

    applyFilter();

    // --- ZIP multi ---
    function getCheckedTipos() {
        return [...tiposGrid.querySelectorAll('input[type="checkbox"][name="tipos[]"]')]
            .filter(i => i.checked)
            .map(i => i.value);
    }

    function refreshZipUI() {
        const checked = getCheckedTipos();
        tiposCount.textContent = String(checked.length);

        const hasWorker = !!trabajador.value;
        const hasFecha = !!(fechaEl?.value || '').trim();
        const hasPuesto = !!(puestoEl?.value || '').trim();

        const hints = [];
        if (!hasWorker) hints.push('Selecciona trabajador');
        if (!hasFecha) hints.push('Selecciona fecha');
        if (!hasPuesto) hints.push('Selecciona puesto');
        if (checked.length === 0) hints.push('Marca documentos');

        zipHint.textContent = hints.length ? `Falta: ${hints.join(' · ')}` : '';

        btnZip.disabled = !(hasWorker && hasFecha && hasPuesto && checked.length > 0);
    }

    tiposGrid?.addEventListener('change', refreshZipUI);

    selAll?.addEventListener('click', () => {
        for (const i of tiposGrid.querySelectorAll('input[type="checkbox"][name="tipos[]"]')) i.checked = true;
        refreshZipUI();
    });

    selNone?.addEventListener('click', () => {
        for (const i of tiposGrid.querySelectorAll('input[type="checkbox"][name="tipos[]"]')) i.checked = false;
        refreshZipUI();
    });

    // PDF único: validar tipo + base (trabajador, fecha, puesto)
    form?.addEventListener('submit', (e) => {
        if (!trabajador.value) {
            e.preventDefault(); alert('Selecciona un trabajador.');
            return;
        }
        if (!(fechaEl?.value || '').trim()) {
            e.preventDefault(); alert('Selecciona una fecha.');
            return;
        }
        if (!(puestoEl?.value || '').trim()) {
            e.preventDefault(); alert('Selecciona un puesto.');
            return;
        }
        if (!tipoSingle.value) {
            e.preventDefault();
            alert('Para generar un PDF único, elige un documento en "PDF único". Si quieres varios, usa el ZIP.');
        }
    });

    btnNewTab?.addEventListener('click', () => {
        if (!trabajador.value) { alert('Selecciona un trabajador.'); return; }
        if (!(fechaEl?.value || '').trim()) { alert('Selecciona una fecha.'); return; }
        if (!(puestoEl?.value || '').trim()) { alert('Selecciona un puesto.'); return; }
        if (!tipoSingle.value) { alert('Elige un documento en "PDF único" antes de abrirlo en nueva pestaña.'); return; }

        form.setAttribute('target', '_blank');
        form.submit();
        form.removeAttribute('target');
    });

    // Descargar ZIP (POST) -> route rrhh.documentos.zip
    btnZip?.addEventListener('click', () => {
        const tipos = getCheckedTipos();

        if (!trabajador.value) { alert('Selecciona un trabajador antes de descargar el ZIP.'); return; }
        if (!(fechaEl?.value || '').trim()) { alert('Selecciona una fecha antes de descargar el ZIP.'); return; }
        if (!(puestoEl?.value || '').trim()) { alert('Selecciona un puesto antes de descargar el ZIP.'); return; }
        if (tipos.length === 0) { alert('Selecciona al menos un documento para el ZIP.'); return; }

        const tmp = document.createElement('form');
        tmp.method = 'POST';
        tmp.action = "{{ route('rrhh.documentos.zip') }}";

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = "{{ csrf_token() }}";
        tmp.appendChild(csrf);

        const wid = document.createElement('input');
        wid.type = 'hidden';
        wid.name = 'trabajador_id';
        wid.value = trabajador.value;
        tmp.appendChild(wid);

        const f = document.createElement('input');
        f.type = 'hidden';
        f.name = 'fecha';
        f.value = fechaEl.value;
        tmp.appendChild(f);

        const p = document.createElement('input');
        p.type = 'hidden';
        p.name = 'puesto';
        p.value = puestoEl.value;
        tmp.appendChild(p);

        for (const t of tipos) {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'tipos[]';
            inp.value = t;
            tmp.appendChild(inp);
        }

        document.body.appendChild(tmp);
        tmp.submit();
        tmp.remove();
    });

    refreshZipUI();
</script>

</body>
</html>
