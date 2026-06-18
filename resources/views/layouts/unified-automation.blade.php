<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Automatizaciones')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3/dist/style.min.css">
    @stack('head')
</head>
<body class="ui-shell text-slate-100">
<div class="ui-bg-overlay"></div>
<div class="relative min-h-screen">
    <header class="sticky top-0 z-50 border-b border-white/15 bg-slate-950/55 backdrop-blur-xl">
        <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-7">
            <a href="{{ route('automation.sequences.index') }}" class="inline-flex items-center gap-2 text-white">
                <span class="ui-logo-dot"></span>
                <span class="ui-title text-lg font-semibold tracking-tight">Panel Automation</span>
            </a>
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <span class="hidden text-[11px] font-semibold uppercase tracking-wide text-slate-400 lg:inline">Automatizar</span>
                <a href="{{ route('automation.sequences.index') }}" class="ui-nav-link {{ request()->routeIs('automation.sequences.*') ? 'is-active' : '' }}">Secuencias</a>
                <a href="{{ route('automation.audit.index') }}" class="ui-nav-link {{ request()->routeIs('automation.audit.*') ? 'is-active' : '' }}">Auditoria</a>
                <a href="{{ route('automation.missing-punch.preview') }}" class="ui-nav-link {{ request()->routeIs('automation.missing-punch.*') ? 'is-active' : '' }}">No fichados</a>

                <span class="hidden h-5 w-px bg-white/20 lg:inline"></span>
                <span class="hidden text-[11px] font-semibold uppercase tracking-wide text-slate-400 lg:inline">Mensajeria</span>
                <a href="{{ route('openwa.collab.index') }}" class="ui-nav-link {{ request()->routeIs('openwa.collab.*') ? 'is-active' : '' }}">OpenWA Colab</a>
                <a href="{{ route('openwa.auto.index') }}" class="ui-nav-link {{ request()->routeIs('openwa.auto.*') ? 'is-active' : '' }}">OpenWA Auto</a>

                <span class="hidden h-5 w-px bg-white/20 lg:inline"></span>
                <a href="{{ route('gestor.gestoria') }}" class="ui-nav-link">Gestoria</a>
                <a href="{{ route('automation.sequences.create') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 font-semibold text-white shadow-sm transition hover:bg-blue-500">+ Nueva</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-7">
        <x-ui.flash-messages />
        @yield('content')
    </main>
</div>
@yield('page_scripts')
@stack('scripts')
<x-ui.command-palette />
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@9.0.3" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!window.simpleDatatables || !window.simpleDatatables.DataTable) {
        return;
    }

    document.querySelectorAll('table[data-enhanced-table]').forEach((table) => {
        if (table.dataset.dtInit === '1') {
            return;
        }

        table.dataset.dtInit = '1';

        // Mejora visual y de navegación de tablas largas en el panel.
        new window.simpleDatatables.DataTable(table, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [10, 25, 50],
            labels: {
                placeholder: 'Buscar...',
                perPage: '{select} por página',
                noRows: 'Sin registros disponibles',
                noResults: 'Sin coincidencias',
                info: 'Mostrando {start} a {end} de {rows}',
            },
        });
    });
});
</script>
</body>
</html>

