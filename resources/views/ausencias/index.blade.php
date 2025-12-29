<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis ausencias</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Instrument Sans"', 'ui-sans-serif'] },
                    colors: { primary: '#2563eb', secondary: '#1e40af' }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans&display=swap" rel="stylesheet">
</head>

<body class="bg-gradient-to-b from-gray-50 via-white to-gray-100 font-sans p-6">

<header class="bg-white shadow mb-8">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-primary">Mis ausencias</h1>
        <nav class="space-x-4 flex items-center">
            <a href="{{ route('usuarios.index') }}" class="text-gray-700 hover:text-primary font-medium">Listado</a>
            <a href="{{ route('gestor.gestoria') }}" class="text-gray-700 hover:text-primary font-medium">Perfil</a>
            <a href="{{ route('usuarios.vincular') }}" class="text-gray-700 hover:text-purple-600 font-medium">Vincular</a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-gray-700 hover:text-red-600 font-medium">Salir</a>
        </nav>
    </div>
</header>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

@php
    $q = request()->query();

    $tipos = [
        '' => 'Todos',
        'vacaciones' => 'Vacaciones',
        'permiso' => 'Permiso',
        'baja' => 'Baja',
    ];

    $years = $years ?? [date('Y')];
    $anio = request('year', date('Y'));
    $tipo = request('tipo', '');
@endphp

    <!-- Filtros + acciones -->
<div class="max-w-7xl mx-auto mb-6">
    <div class="bg-white rounded-xl ring-1 ring-gray-200 p-4">
        <form method="GET" action="{{ route('ausencias.index') }}" class="flex flex-wrap items-end gap-4" onsubmit="setLoading(true)">
            <!-- Año -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                <select name="year"
                        onchange="setLoading(true); this.form.submit()"
                        class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (string)$anio === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tipo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo"
                        onchange="setLoading(true); this.form.submit()"
                        class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    @foreach($tipos as $k => $label)
                        <option value="{{ $k }}" {{ (string)$tipo === (string)$k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Desde -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
            </div>

            <!-- Hasta -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
            </div>

            <!-- Buscar -->
            <div class="flex items-center gap-3">
                <button id="btnBuscar" type="submit"
                        class="px-4 py-2 bg-primary text-white font-medium rounded-lg hover:bg-secondary transition focus:outline-none focus:ring-4 focus:ring-primary/30">
                    Filtrar
                </button>
                <div id="loading" class="hidden text-sm text-gray-500">Cargando…</div>
            </div>

            <!-- Limpiar -->
            <div class="flex gap-2">
                @if(request()->hasAny(['year','tipo','from','to']))
                    <a href="{{ route('ausencias.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 underline hover:text-red-500">
                        Limpiar
                    </a>
                @endif
            </div>

            <!-- Acciones -->
            <div class="flex items-center gap-3 flex-wrap ml-auto">
                <button type="button"
                        onclick="openModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition font-medium">
                    ➕ Solicitar ausencia
                </button>

                @if(Route::has('ausencias.export.excel'))
                    <a href="{{ route('ausencias.export.excel', request()->query()) }}"
                       onclick="setLoading(true)"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition font-medium">
                        ⬇️ Exportar Excel
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<main class="max-w-7xl mx-auto space-y-6">

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl ring-1 ring-gray-200 p-4">
            <div class="text-xs text-gray-500">Total días</div>
            <div class="text-2xl font-semibold">{{ $stats['total'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-xl ring-1 ring-gray-200 p-4">
            <div class="text-xs text-gray-500">Vacaciones</div>
            <div class="text-2xl font-semibold">{{ $stats['vacaciones'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-xl ring-1 ring-gray-200 p-4">
            <div class="text-xs text-gray-500">Permisos</div>
            <div class="text-2xl font-semibold">{{ $stats['permiso'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-xl ring-1 ring-gray-200 p-4">
            <div class="text-xs text-gray-500">Bajas</div>
            <div class="text-2xl font-semibold">{{ $stats['baja'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto bg-white shadow-md rounded-xl ring-1 ring-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-primary text-white uppercase tracking-wider text-xs">
            <tr>
                <th class="px-6 py-3 text-left">Rango</th>
                <th class="px-6 py-3 text-left">Tipo</th>
                <th class="px-6 py-3 text-left">Año</th>
                <th class="px-6 py-3 text-left">Días</th>
                <th class="px-6 py-3 text-left">Acciones</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
            @forelse($rangos as $r)
                @php
                    $badge = match($r['tipo']) {
                        'vacaciones' => 'bg-blue-100 text-blue-700',
                        'permiso' => 'bg-amber-100 text-amber-700',
                        'baja' => 'bg-red-100 text-red-700',
                        default => 'bg-gray-100 text-gray-700'
                    };
                @endphp

                <tr class="even:bg-gray-50 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap text-gray-800">
                        {{ $r['from'] }} → {{ $r['to'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full font-medium {{ $badge }}">
                            {{ ucfirst($r['tipo']) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ $r['vacation_year'] ?? '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                        {{ $r['count'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form method="POST" action="{{ route('ausencias.destroy.rango') }}"
                              onsubmit="return confirm('¿Eliminar este rango completo?')"
                              class="inline-flex">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="tipo" value="{{ $r['tipo'] }}">
                            <input type="hidden" name="from" value="{{ $r['from_raw'] }}">
                            <input type="hidden" name="to" value="{{ $r['to_raw'] }}">
                            <button type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition text-sm font-medium">
                                🗑️ <span class="hidden sm:inline">Eliminar</span>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                        No hay ausencias con estos filtros.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if(isset($paginator))
        <div class="pt-2">
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    @endif
</main>

<!-- MODAL: Solicitar ausencia -->
<div id="modal" class="fixed inset-0 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl ring-1 ring-gray-200">
        <div class="px-5 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Solicitar ausencia</h3>
            <button type="button" onclick="closeModal()" class="text-gray-500 hover:text-gray-800">✖</button>
        </div>

        <form method="POST" action="{{ route('ausencias.store') }}" class="p-5 space-y-4" onsubmit="setLoading(true)">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select id="tipoModal" name="tipo"
                            onchange="toggleYear()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
                        <option value="vacaciones">Vacaciones</option>
                        <option value="permiso">Permiso</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>

                <div id="yearBox">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año vacacional</label>
                    <input type="number" name="vacation_year" value="{{ date('Y') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
                    <input type="date" name="from" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
                    <input type="date" name="to" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
                <input type="text" name="notas" maxlength="255"
                       placeholder="Ej: médico, cita, incidencia..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-primary focus:outline-none">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal()"
                        class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition font-medium">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function setLoading(isLoading){
        const el = document.getElementById('loading');
        const btn = document.getElementById('btnBuscar');
        if (!el) return;
        if (isLoading) {
            el.classList.remove('hidden');
            if (btn) btn.disabled = true;
        } else {
            el.classList.add('hidden');
            if (btn) btn.disabled = false;
        }
    }

    function openModal(){
        document.getElementById('modal').classList.remove('hidden');
        document.getElementById('modal').classList.add('flex');
        toggleYear();
    }

    function closeModal(){
        const m = document.getElementById('modal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function toggleYear(){
        const tipo = document.getElementById('tipoModal')?.value;
        const box = document.getElementById('yearBox');
        if (!box) return;

        // Año vacacional solo tiene sentido para vacaciones
        if (tipo === 'vacaciones') box.classList.remove('hidden');
        else box.classList.add('hidden');
    }
</script>

</body>
</html>
