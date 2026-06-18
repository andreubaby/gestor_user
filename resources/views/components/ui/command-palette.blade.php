@php
    $items = [
        ['label' => 'Inicio Gestoria', 'hint' => 'Dashboard principal', 'url' => route('gestor.gestoria')],
        ['label' => 'Usuarios', 'hint' => 'Listado de trabajadores', 'url' => route('usuarios.index')],
        ['label' => 'Vincular usuarios', 'hint' => 'Asociacion por UUID/email', 'url' => route('usuarios.vincular')],
        ['label' => 'Automation - Secuencias', 'hint' => 'Listado de secuencias', 'url' => route('automation.sequences.index')],
        ['label' => 'Automation - Nueva secuencia', 'hint' => 'Crear automatizacion', 'url' => route('automation.sequences.create')],
        ['label' => 'Automation - Auditoria', 'hint' => 'Eventos y exportes', 'url' => route('automation.audit.index')],
        ['label' => 'OpenWA Colaboraciones', 'hint' => 'Mensajeria colaborativa', 'url' => route('openwa.collab.index')],
        ['label' => 'OpenWA Automatizaciones', 'hint' => 'Envios automaticos', 'url' => route('openwa.auto.index')],
        ['label' => 'RRHH Documentos', 'hint' => 'Generacion PDF/ZIP', 'url' => route('rrhh.documentos.index')],
    ];
@endphp

<div id="global-command-palette" class="hidden fixed inset-0 z-[120]">
    <div class="absolute inset-0 bg-slate-900/50" data-palette-close="1"></div>

    <div class="relative mx-auto mt-16 w-[min(720px,92vw)] rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-slate-800">Acciones rapidas</p>
            <p class="text-xs text-slate-500">Ctrl + K</p>
        </div>

        <input
            id="global-command-palette-input"
            type="text"
            autocomplete="off"
            placeholder="Buscar modulo o accion..."
            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
        >

        <ul id="global-command-palette-results" class="mt-3 max-h-[55vh] space-y-1 overflow-y-auto"></ul>
    </div>
</div>

<script>
(() => {
    if (window.__globalCommandPaletteInit) {
        return;
    }
    window.__globalCommandPaletteInit = true;

    const items = @json($items);
    const root = document.getElementById('global-command-palette');
    const input = document.getElementById('global-command-palette-input');
    const list = document.getElementById('global-command-palette-results');

    if (!root || !input || !list) {
        return;
    }

    let activeIndex = 0;
    let filtered = items.slice();

    const render = () => {
        list.innerHTML = '';

        if (filtered.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500';
            empty.textContent = 'Sin coincidencias';
            list.appendChild(empty);
            return;
        }

        filtered.forEach((item, index) => {
            const li = document.createElement('li');
            const active = index === activeIndex;
            li.className = active
                ? 'cursor-pointer rounded-lg border border-blue-200 bg-blue-50 px-3 py-2'
                : 'cursor-pointer rounded-lg border border-transparent px-3 py-2 hover:border-slate-200 hover:bg-slate-50';
            li.innerHTML = `<p class="text-sm font-semibold text-slate-900">${item.label}</p><p class="text-xs text-slate-500">${item.hint}</p>`;
            li.addEventListener('mouseenter', () => {
                activeIndex = index;
                render();
            });
            li.addEventListener('click', () => {
                window.location.href = item.url;
            });
            list.appendChild(li);
        });
    };

    const openPalette = () => {
        filtered = items.slice();
        activeIndex = 0;
        root.classList.remove('hidden');
        input.value = '';
        render();
        setTimeout(() => input.focus(), 0);
    };

    const closePalette = () => {
        root.classList.add('hidden');
    };

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const typing = target instanceof HTMLElement
            && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);

        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            if (root.classList.contains('hidden')) {
                openPalette();
            } else {
                closePalette();
            }
            return;
        }

        if (root.classList.contains('hidden')) {
            return;
        }

        if (event.key === 'Escape') {
            closePalette();
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (filtered.length > 0) {
                activeIndex = (activeIndex + 1) % filtered.length;
                render();
            }
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (filtered.length > 0) {
                activeIndex = (activeIndex - 1 + filtered.length) % filtered.length;
                render();
            }
            return;
        }

        if (event.key === 'Enter' && !typing && filtered[activeIndex]) {
            window.location.href = filtered[activeIndex].url;
        }
    });

    root.addEventListener('click', (event) => {
        const target = event.target;
        if (target instanceof HTMLElement && target.dataset.paletteClose === '1') {
            closePalette();
        }
    });

    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        filtered = items.filter((item) => {
            return `${item.label} ${item.hint}`.toLowerCase().includes(q);
        });
        activeIndex = 0;
        render();
    });
})();
</script>

