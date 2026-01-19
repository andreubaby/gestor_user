<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar onboarding | Gestor de Usuarios Babyplant</title>
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
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">Enviar onboarding</h1>
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

            <a href="{{ route('rrhh.documentos.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
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

<main class="relative max-w-7xl mx-auto px-4 py-8 space-y-5">

    {{-- Encabezado --}}
    <section class="bg-white/80 backdrop-blur rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-emerald-800 tracking-tight">Enviar formación previa</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Selecciona un trabajador y un pack de contenido. Se enviará un email con enlaces a vídeos / PDFs / imágenes.
                </p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Formulario --}}
        <section class="bg-white rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5">
            <h3 class="text-lg font-semibold text-slate-900">Datos del envío</h3>

            <form method="POST" action="{{ route('usuarios.onboarding.send') }}" class="mt-4 space-y-4" id="onboardingForm">
                @csrf

                {{-- Trabajador --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Trabajador</label>
                    <select name="trabajador_id" id="trabajadorSelect"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                                   focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none"
                            required>
                        <option value="" selected disabled>Selecciona un trabajador…</option>
                        @foreach($workers as $w)
                            <option value="{{ $w->id }}"
                                    data-email="{{ $w->email }}"
                                    data-nombre="{{ $w->nombre }}">
                                {{ $w->nombre }} {{ $w->activo ? '' : ' (Inactivo)' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">
                        Se enviará al email del trabajador (si existe). Si no tiene email, te avisará.
                    </p>
                </div>

                {{-- Pack --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Pack de onboarding</label>
                    <select name="pack" id="packSelect"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                                   focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none"
                            required>
                        <option value="" selected disabled>Selecciona un pack…</option>
                        @foreach(($packs ?? []) as $key => $p)
                            <option value="{{ $key }}">{{ $p['name'] ?? $key }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Email destino (solo vista) --}}
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs text-slate-500">Destino</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" id="destinoNombre">—</div>
                    <div class="text-sm text-slate-700 break-all" id="destinoEmail">—</div>
                </div>

                {{-- Botón --}}
                <button type="submit" id="sendBtn"
                        class="w-full rounded-2xl bg-emerald-600 px-4 py-3 font-semibold text-white shadow
                               transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200
                               disabled:opacity-60 disabled:cursor-not-allowed">
                    ✉️ Enviar email de onboarding
                </button>

                <p class="text-xs text-slate-500">
                    Al enviar, quedará registrado en el sistema (cuando activemos el backend).
                </p>
            </form>
        </section>

        {{-- Previsualización --}}
        <section class="bg-white rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Previsualización</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        Así quedará el contenido del email (ordenado por secciones).
                    </p>
                </div>
            </div>

            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">Asunto</div>
                <div class="mt-1 text-sm font-semibold text-slate-900" id="previewSubject">
                    Babyplant Formación previa
                </div>

                <div class="mt-4 text-sm text-slate-700 leading-relaxed" id="previewIntro">
                    Hola, revisa estos materiales antes de comenzar.
                </div>

                <div class="mt-4 space-y-3" id="previewItems">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                        Selecciona un pack para ver el contenido.
                    </div>
                </div>
            </div>

            <div class="mt-4 text-xs text-slate-500" id="previewSummary"></div>
        </section>

    </div>
</main>

<script>
    // Packs desde backend (para previsualización)
    const PACKS = @json($packs ?? []);

    const trabajadorSelect = document.getElementById('trabajadorSelect');
    const packSelect = document.getElementById('packSelect');

    const destinoNombre = document.getElementById('destinoNombre');
    const destinoEmail  = document.getElementById('destinoEmail');

    const previewItems   = document.getElementById('previewItems');
    const previewSummary = document.getElementById('previewSummary');
    const sendBtn = document.getElementById('sendBtn');

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    function iconByType(type) {
        const t = String(type || '').toLowerCase();
        if (t === 'video') return '🎬';
        if (t === 'pdf') return '📄';
        if (t === 'image') return '🖼️';
        return '🔗';
    }

    function renderPreview() {
        const packKey = packSelect?.value;
        const pack = packKey ? PACKS[packKey] : null;

        previewItems.replaceChildren();
        previewSummary.textContent = '';

        if (!pack || !Array.isArray(pack.items) || pack.items.length === 0) {
            const div = document.createElement('div');
            div.className = "rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600";
            div.textContent = "Selecciona un pack para ver el contenido.";
            previewItems.appendChild(div);
            return;
        }

        // agrupar por type para que el email quede “bonito”
        const groups = {};
        for (const it of pack.items) {
            const type = String(it.type || 'link').toLowerCase();
            if (!groups[type]) groups[type] = [];
            groups[type].push(it);
        }

        const order = ['video','pdf','image','link','other'];
        const types = Object.keys(groups).sort((a,b) => (order.indexOf(a) - order.indexOf(b)));

        let total = 0;

        for (const type of types) {
            const items = groups[type] || [];
            total += items.length;

            const section = document.createElement('div');
            section.className = "rounded-2xl border border-slate-200 bg-white p-3";

            const h = document.createElement('div');
            h.className = "text-xs font-semibold text-slate-500 uppercase tracking-wider";
            h.textContent = (type === 'video' ? 'Vídeos' :
                type === 'pdf' ? 'PDFs' :
                    type === 'image' ? 'Imágenes' :
                        'Enlaces');
            section.appendChild(h);

            const ul = document.createElement('ul');
            ul.className = "mt-2 space-y-2";

            for (const it of items) {
                const li = document.createElement('li');
                li.className = "flex items-center justify-between gap-2 rounded-xl bg-slate-50 p-2";

                const left = document.createElement('div');
                left.className = "min-w-0";

                const title = document.createElement('div');
                title.className = "text-sm font-semibold text-slate-900 truncate";
                title.textContent = `${iconByType(type)} ${it.title || 'Recurso'}`;

                const desc = document.createElement('div');
                desc.className = "text-xs text-slate-600";
                desc.textContent = it.description || '';

                left.appendChild(title);
                if (it.description) left.appendChild(desc);

                const btn = document.createElement('div');
                btn.className = "shrink-0 inline-flex items-center rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white opacity-80";
                btn.textContent = "Ver";

                li.appendChild(left);
                li.appendChild(btn);

                ul.appendChild(li);
            }

            section.appendChild(ul);
            previewItems.appendChild(section);
        }

        previewSummary.textContent = `Pack seleccionado: ${pack.name || packKey} · Total recursos: ${total}`;
    }

    function renderDestino() {
        const opt = trabajadorSelect?.selectedOptions?.[0];
        const nombre = opt?.dataset?.nombre || '—';
        const email  = opt?.dataset?.email  || '—';

        destinoNombre.textContent = nombre;
        destinoEmail.textContent = email;

        // Deshabilita enviar si no hay email
        if (email === '—' || String(email).trim() === '') {
            sendBtn.disabled = true;
            sendBtn.title = 'Este trabajador no tiene email';
        } else {
            sendBtn.disabled = false;
            sendBtn.title = '';
        }
    }

    trabajadorSelect?.addEventListener('change', renderDestino);
    packSelect?.addEventListener('change', renderPreview);

    // inicial
    renderDestino();
    renderPreview();
</script>

</body>
</html>
