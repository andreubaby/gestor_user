<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabajadores Polifonía | Gestor de Usuarios Babyplant</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a',   // verde Babyplant
                        secondary: '#15803d', // verde más oscuro
                        bp: {
                            green: '#16a34a',
                            green2: '#22c55e'
                        }
                    },
                    boxShadow: {
                        soft: '0 10px 25px rgba(2,6,23,.08)'
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-white text-slate-900">

{{-- Fondo suave blanco/verde (solo visual) --}}
<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

<header class="sticky top-0 z-40 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">

        {{-- Branding --}}
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">
                    Trabajadores · Polifonía
                </h1>
            </div>
        </div>

        {{-- Navegación (sin Perfil / Config) --}}
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

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

@php
    $q = request()->query();

    $sortLink = function($field) use ($q) {
        $currentSort = $q['sort'] ?? 'nombre';
        $currentDir  = $q['dir'] ?? 'asc';
        $dir = ($currentSort === $field && $currentDir === 'asc') ? 'desc' : 'asc';
        return route('usuarios.index', array_merge($q, ['sort' => $field, 'dir' => $dir, 'page' => 1]));
    };

    $sortIcon = function($field) use ($q) {
        $currentSort = $q['sort'] ?? 'nombre';
        $currentDir  = $q['dir'] ?? 'asc';
        if ($currentSort !== $field) return '';
        return $currentDir === 'asc' ? '↑' : '↓';
    };
@endphp

<main class="max-w-8xl mx-auto space-y-5 px-4 pb-10">

    <!-- ✅ Toolbar alineada (buscador izq + export der) -->
    <section class="bg-white/80 backdrop-blur rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5">
        <div class="flex flex-col gap-4">

            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl font-semibold text-emerald-800 tracking-tight">Trabajadores de Polifonía</h2>
                    <p class="text-sm text-slate-600 mt-1">
                        Por: <span class="font-medium">{{ $sort ?? 'nombre' }}</span> ·
                        Orden: <span class="font-medium">{{ $dir ?? 'asc' }}</span>
                    </p>
                </div>

                <!-- Exportar Excel (derecha) -->
                <div class="flex items-center gap-3 flex-wrap">
                    <a href="{{ route('usuarios.export.excel', request()->query()) }}"
                       target="_blank" rel="noopener"
                       title="Exporta el listado con los filtros actuales"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white
                              hover:bg-emerald-700 transition font-semibold focus:outline-none focus:ring-4 focus:ring-emerald-300/40 shadow">
                        ⬇️ Exportar Excel
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <form method="GET" action="{{ route('usuarios.index') }}"
                  class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end"
                  onsubmit="setLoading(true)">

                <!-- Buscar -->
                <div class="md:col-span-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre</label>
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Buscar trabajador…"
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                                  focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                </div>

                <!-- Estado -->
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Estado</label>
                    <select name="activo"
                            onchange="setLoading(true); this.form.submit()"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                                   focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                        <option value="">Todos</option>
                        <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <!-- Grupo -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Grupo</label>
                    <select name="grupo"
                            onchange="setLoading(true); this.form.submit()"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                   focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                        <option value="">Todos</option>

                        @foreach(($groups ?? []) as $g)
                            <option value="{{ $g->id }}" {{ (string)request('grupo') === (string)$g->id ? 'selected' : '' }}>
                                {{ $g->name ?? $g->nombre ?? ('Grupo '.$g->id) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Botón Buscar + Loading -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3">
                        <button id="btnBuscar" type="submit"
                                class="inline-flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold
                                       hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200 shadow">
                            <span>🔎</span> Buscar
                        </button>
                        <div id="loading" class="hidden text-sm text-slate-500">
                            Cargando…
                        </div>
                    </div>
                </div>

                <!-- Limpiar -->
                <div class="md:col-span-2 md:flex md:justify-end">
                    @if(request()->hasAny(['search','activo','grupo','sort','dir']))
                        <a href="{{ route('usuarios.index') }}"
                           class="inline-flex px-3 py-2 text-sm font-medium text-slate-600 rounded-xl hover:bg-red-50 hover:text-red-600 transition">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </section>

    <!-- Stats -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">Total</div>
            <div class="text-2xl font-semibold text-slate-900">{{ $stats['total'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">Vinculados</div>
            <div class="text-2xl font-semibold text-slate-900">{{ $stats['vinculados'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">Activos</div>
            <div class="text-2xl font-semibold text-slate-900">{{ $stats['activos'] ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-emerald-100 p-4 shadow-sm">
            <div class="text-xs text-slate-500">Inactivos</div>
            <div class="text-2xl font-semibold text-slate-900">{{ $stats['inactivos'] ?? 0 }}</div>
        </div>
    </section>

    <!-- Tabla -->
    <section class="overflow-x-auto bg-white shadow-md rounded-2xl ring-1 ring-emerald-100">
        <table class="min-w-full divide-y divide-emerald-100 text-sm table-auto">
            <thead class="bg-gradient-to-r from-emerald-700 to-emerald-600 text-white uppercase tracking-wider text-xs sticky top-0 z-10">
            <tr>
                <th class="px-6 py-3 text-left w-[26%]">
                    <a href="{{ $sortLink('nombre') }}"
                       class="hover:underline focus:outline-none focus:ring-2 focus:ring-white/40 rounded"
                       onclick="setLoading(true)">
                        Nombre {{ $sortIcon('nombre') }}
                    </a>
                </th>
                <th class="px-6 py-3 text-left w-[10%]">Bienestar</th>
                <th class="px-6 py-3 text-left w-[28%]">
                    <a href="{{ $sortLink('email') }}"
                       class="hover:underline focus:outline-none focus:ring-2 focus:ring-white/40 rounded"
                       onclick="setLoading(true)">
                        Email {{ $sortIcon('email') }}
                    </a>
                </th>
                <th class="px-6 py-3 text-left w-[12%]">
                    <a href="{{ $sortLink('vinculado') }}"
                       class="hover:underline focus:outline-none focus:ring-2 focus:ring-white/40 rounded"
                       onclick="setLoading(true)">
                        Vinculado {{ $sortIcon('vinculado') }}
                    </a>
                </th>
                <th class="px-6 py-3 text-left w-[10%]">
                    <a href="{{ $sortLink('activo') }}"
                       class="hover:underline focus:outline-none focus:ring-2 focus:ring-white/40 rounded"
                       onclick="setLoading(true)">
                        Estado {{ $sortIcon('activo') }}
                    </a>
                </th>
                <th class="px-6 py-3 text-left w-[14%]">Permisos</th>
                <th class="px-6 py-3 text-left w-[10%]">Acciones</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
            @forelse($agrupados as $registro)
                @php
                    $vinculado = !empty($registro->uuid);
                    $editRoute = $vinculado
                        ? route('usuarios.edit.uuid', ['uuid' => $registro->uuid])
                        : route('trabajadores.edit', ['id' => $registro->id]);

                    $emailNorm = mb_strtolower(trim($registro->email ?? ''));
                    $sinEmail = $emailNorm === '';
                    $emailDuplicado = $emailNorm !== '' && in_array($emailNorm, $dupEmails ?? [], true);

                    $vincularRoute = route('usuarios.vincular', array_filter([
                        'email' => $registro->email ?? null,
                    ]));

                    $aus = $registro->ausencias ?? [
                        'vacaciones'=>['count'=>0,'items'=>[]],
                        'permiso'=>['count'=>0,'items'=>[]],
                        'baja'=>['count'=>0,'items'=>[]]
                    ];
                    $ausJson = htmlspecialchars(json_encode($aus, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                @endphp

                <tr
                    class="transition hover:bg-emerald-50/40 {{ $vinculado ? 'bg-amber-50 ring-1 ring-amber-200' : 'even:bg-slate-50/40' }}"
                    data-worker="{{ $registro->id }}"
                    data-nombre="{{ e($registro->nombre) }}"
                    data-ausencias="{{ $ausJson }}"
                    role="button"
                    tabindex="0"
                    aria-label="Abrir {{ $registro->nombre }}"
                    onclick="goRow('{{ $editRoute }}', event)"
                    onkeydown="if(event.key==='Enter'){ goRow('{{ $editRoute }}', event) }"
                >
                    <!-- Nombre -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-slate-900">{{ $registro->nombre }}</span>

                            @if($vinculado)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                                    🔗 Vinculado
                                </span>
                            @endif

                            @if($sinEmail)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    ⚠ Sin email
                                </span>
                            @endif

                            @if($emailDuplicado)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                                    ⚠ Email duplicado
                                </span>
                            @endif
                        </div>
                    </td>

                    <!-- Caritas -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $valores = $registro->bienestar_ultimos ?? [];

                            $map = [
                                1 => ['🙂', 'bg-emerald-500 text-emerald-800 ring-emerald-800'],
                                2 => ['😐', 'bg-yellow-300 text-yellow-800 ring-yellow-800'],
                                3 => ['🙁', 'bg-orange-400 text-orange-800 ring-orange-800'],
                                4 => ['😡', 'bg-red-400 text-red-800 ring-red-800'],
                            ];

                            $gris = 'bg-slate-100 text-slate-500 ring-slate-200';
                        @endphp

                        <button type="button"
                                class="inline-flex items-center gap-1"
                                onclick="event.stopPropagation(); openFichajesModal(this)"
                                aria-label="Ver historial de fichajes"
                                data-worker="{{ $registro->id }}"
                                data-nombre="{{ e($registro->nombre) }}"
                                data-email="{{ e($registro->email ?? '') }}">
                            @for ($i = 0; $i < 4; $i++)
                                @php $b = $valores[$i] ?? null; @endphp

                                @if(isset($map[$b]))
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-sm ring-1 {{ $map[$b][1] }}"
                                          title="Bienestar {{ $b }}">
                                        {{ $map[$b][0] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-sm ring-1 {{ $gris }}"
                                          title="Sin fichaje">
                                        🙂
                                    </span>
                                @endif
                            @endfor
                        </button>
                    </td>

                    <!-- Email -->
                    <td class="px-6 py-4 text-slate-600">
                        <span class="break-all">{{ $registro->email }}</span>
                    </td>

                    <!-- Vinculado -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($vinculado)
                            <span class="px-2 py-1 text-xs rounded-full font-semibold bg-amber-100 text-amber-700">
                                Vinculado
                            </span>
                        @else
                            @if(!$sinEmail)
                                <a href="{{ $vincularRoute }}"
                                   title="Ir a vincular este trabajador"
                                   onclick="event.stopPropagation(); setLoading(true);"
                                   class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full font-semibold
                                          bg-slate-100 text-slate-700 hover:bg-purple-100 hover:text-purple-700 transition
                                          focus:outline-none focus:ring-4 focus:ring-purple-300/40">
                                    ➕ Sin vincular
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full font-semibold bg-slate-100 text-slate-400"
                                      title="No se puede vincular sin email">
                                    ➕ Sin vincular
                                </span>
                            @endif
                        @endif
                    </td>

                    <!-- Estado -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <form method="POST"
                              action="{{ route('trabajadores.toggleActivo', ['id' => $registro->id]) }}"
                              onsubmit="event.stopPropagation(); this.querySelector('input[name=scroll_y]').value = window.scrollY; setLoading(true);">
                            @csrf
                            <input type="hidden" name="scroll_y" value="0">

                            <button type="submit"
                                    onclick="event.stopPropagation();"
                                    class="px-2.5 py-1 text-xs rounded-full font-semibold inline-flex items-center gap-1
                                    transition focus:outline-none focus:ring-4
                                    {{ ($registro->activo ?? 0)
                                        ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 focus:ring-emerald-200'
                                        : 'bg-red-100 text-red-700 hover:bg-red-200 focus:ring-red-200' }}"
                                    title="Cambiar estado">
                                {{ ($registro->activo ?? 0) ? '✅ Activo' : '⛔ Inactivo' }}
                            </button>
                        </form>
                    </td>

                    <!-- Ausencias (HORIZONTAL) -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="inline-flex items-center gap-2">
                            <button type="button"
                                    onclick="event.stopPropagation(); openAusenciasModal(this, 'vacaciones')"
                                    class="px-2 py-1 text-xs rounded-full font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition
                                           focus:outline-none focus:ring-4 focus:ring-blue-200/50"
                                    title="Ver vacaciones">
                                🏖 Vac <span id="vacCount-{{ $registro->id }}">{{ $aus['vacaciones']['count'] ?? 0 }}</span>
                            </button>

                            <button type="button"
                                    onclick="event.stopPropagation(); openAusenciasModal(this, 'permiso')"
                                    class="px-2 py-1 text-xs rounded-full font-semibold bg-amber-100 text-amber-700 hover:bg-amber-200 transition
                                           focus:outline-none focus:ring-4 focus:ring-amber-200/50"
                                    title="Ver permisos">
                                📝 Per <span id="perCount-{{ $registro->id }}">{{ $aus['permiso']['count'] ?? 0 }}</span>
                            </button>

                            <button type="button"
                                    onclick="event.stopPropagation(); openAusenciasModal(this, 'baja')"
                                    class="px-2 py-1 text-xs rounded-full font-semibold bg-red-100 text-red-700 hover:bg-red-200 transition
                                           focus:outline-none focus:ring-4 focus:ring-red-200/50"
                                    title="Ver bajas">
                                🏥 Baj <span id="bajCount-{{ $registro->id }}">{{ $aus['baja']['count'] ?? 0 }}</span>
                            </button>
                        </div>
                    </td>

                    <!-- Acciones -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <a href="{{ $editRoute }}"
                               title="Editar"
                               onclick="event.stopPropagation(); setLoading(true);"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 transition
                                      text-sm font-semibold focus:outline-none focus:ring-4 focus:ring-blue-300/40">
                                ✏️ <span class="hidden lg:inline">Editar</span>
                            </a>

                            <button type="button"
                                    title="{{ $sinEmail ? 'No hay email para copiar' : 'Copiar email' }}"
                                    {{ $sinEmail ? 'disabled' : '' }}
                                    onclick="event.stopPropagation(); copyToClipboard('{{ $registro->email ?? '' }}')"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-sm font-semibold focus:outline-none focus:ring-4
                                           {{ $sinEmail
                                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed focus:ring-slate-200/0'
                                                : 'bg-slate-50 text-slate-700 hover:bg-slate-100 focus:ring-slate-300/40' }}">
                                📋 <span class="hidden lg:inline">Email</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-6 text-center text-slate-500">
                        No se encontraron trabajadores.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <!-- Paginación -->
    <div class="pt-2">
        {{ $agrupados->appends(request()->query())->links() }}
    </div>
</main>

<!-- Toast -->
<span id="toast"
      class="fixed bottom-6 right-6 z-[60] hidden rounded-xl bg-slate-900 text-white px-4 py-2 text-sm shadow-lg">
  Copiado ✅
</span>

<!-- ✅ MODAL FICHAJES -->
<div id="fichajesModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 p-3 sm:p-6 z-50">
    <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl ring-1 ring-gray-200 overflow-hidden">

        <div class="px-5 py-4 border-b flex items-center justify-between bg-white">
            <div>
                <h3 id="fichajesTitle" class="text-lg font-semibold text-gray-900">Historial de fichajes</h3>
                <p id="fichajesSub" class="text-sm text-gray-500 mt-0.5">—</p>
            </div>
            <button type="button" onclick="closeFichajesModal()"
                    class="text-gray-500 hover:text-gray-800" aria-label="Cerrar">✖</button>
        </div>

        <div class="p-5 overflow-auto max-h-[70vh]">
            <div id="fichajesLoading" class="text-sm text-gray-500">Cargando…</div>

            <div id="fichajesEmpty" class="hidden text-sm text-gray-500">
                No hay fichajes para mostrar.
            </div>

            <div id="fichajesError" class="hidden text-sm text-red-600">
                Error cargando los fichajes.
            </div>

            <ul id="fichajesList" class="hidden divide-y divide-gray-100"></ul>
        </div>

        <div class="px-5 py-4 border-t flex items-center justify-end gap-2 bg-white">
            <button type="button" onclick="closeFichajesModal()"
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 transition">
                Cerrar
            </button>
        </div>

    </div>
</div>

<!-- ✅ MODAL AUSENCIAS (CALENDARIO) -->
<div id="ausModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 p-3 sm:p-6 z-50">
    <div class="w-full max-w-7xl bg-white rounded-2xl shadow-xl ring-1 ring-gray-200
                max-h-[92vh] overflow-hidden">

        <!-- Header fijo -->
        <div class="px-5 py-4 border-b flex items-center justify-between bg-white">
            <div>
                <h3 id="ausTitle" class="text-lg font-semibold text-gray-900">Ausencias</h3>
                <p id="ausSub" class="text-sm text-gray-500 mt-0.5">—</p>
            </div>
            <button type="button" onclick="closeAusenciasModal()"
                    class="text-gray-500 hover:text-gray-800" aria-label="Cerrar">✖</button>
        </div>

        <!-- Cuerpo con scroll -->
        <div class="p-5 space-y-4 overflow-auto max-h-[calc(92vh-140px)]">

            <!-- Tabs -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" id="tabVac" onclick="switchAusTab('vacaciones')"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition focus:outline-none">
                    🏖 Vacaciones
                </button>

                <button type="button" id="tabPer" onclick="switchAusTab('permiso')"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition focus:outline-none">
                    📝 Permisos
                </button>

                <button type="button" id="tabBaj" onclick="switchAusTab('baja')"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition focus:outline-none">
                    🏥 Bajas
                </button>
            </div>

            <div class="hidden">
                <span class="bg-blue-50 bg-blue-100 text-blue-700 text-blue-800 ring-1 ring-2 ring-blue-200 ring-blue-300 hover:bg-blue-100"></span>
                <span class="bg-amber-50 bg-amber-100 text-amber-700 text-amber-900 ring-amber-200 ring-amber-300 hover:bg-amber-100"></span>
                <span class="bg-red-50 bg-red-100 text-red-700 text-red-800 ring-red-200 ring-red-300 hover:bg-red-100"></span>
            </div>

            <!-- Toolbar: Año calendario | Descargar | Año asignación -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">

                <!-- Año calendario -->
                <div class="flex flex-col items-start gap-2">
                    <div class="text-sm font-semibold text-gray-800">Año del calendario</div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="prevCalendarYear()"
                                class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">‹</button>

                        <div id="calYearLabel" class="min-w-[72px] text-center font-semibold text-gray-900">—</div>

                        <button type="button" onclick="nextCalendarYear()"
                                class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">›</button>
                    </div>
                </div>

                <!-- Centro: descargar + acciones -->
                <div class="flex items-center justify-center gap-2 flex-wrap">
                    <button type="button" onclick="downloadCalendarPdf()"
                            class="px-4 py-2 rounded-lg bg-white ring-1 ring-gray-200 hover:bg-gray-50 text-sm font-medium">
                        Descargar calendario
                    </button>

                    <button type="button" onclick="clearSelection()"
                            class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 transition text-sm">
                        Limpiar selección
                    </button>

                    <button type="button" onclick="debugSelection()"
                            class="px-4 py-2 rounded-lg bg-primary text-white hover:bg-secondary transition text-sm">
                        Ver selección
                    </button>
                </div>

                <!-- Año asignación -->
                <div class="flex flex-col items-end gap-2">
                    <div class="text-sm font-semibold text-gray-800">Año asignación días</div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="prevBucketYear()"
                                class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">‹</button>

                        <div id="bucketYearLabel" class="min-w-[72px] text-center font-semibold text-gray-900">—</div>

                        <button type="button" onclick="nextBucketYear()"
                                class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">›</button>
                    </div>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="flex items-center gap-3 text-xs text-gray-600 flex-wrap">
                <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-blue-200"></span> Vac existente</span>
                <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-amber-200"></span> Per existente</span>
                <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-red-200"></span> Baj existente</span>
                <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded bg-emerald-200"></span> Seleccionado</span>
            </div>

            <div id="vacPdfWrap" class="hidden rounded-xl ring-1 ring-gray-200 bg-gray-50 p-3">
                <div class="text-sm font-semibold text-gray-800">PDFs (Vacaciones)</div>
                <div id="vacPdfButtons" class="flex flex-wrap gap-2 mt-2"></div>
                <p class="text-xs text-gray-500 mt-1">Genera un PDF con todas las vacaciones del año.</p>
            </div>

            <div id="perPdfWrap" class="hidden rounded-xl ring-1 ring-gray-200 bg-gray-50 p-3">
                <div class="text-sm font-semibold text-gray-800">PDFs (Permisos)</div>
                <div id="perPdfButtons" class="flex flex-wrap gap-2 mt-2"></div>
                <p class="text-xs text-gray-500 mt-1">Genera un PDF con todos los permisos del año.</p>
            </div>

            <div id="bajPdfWrap" class="hidden rounded-xl ring-1 ring-gray-200 bg-gray-50 p-3">
                <div class="text-sm font-semibold text-gray-800">PDFs (Bajas)</div>
                <div id="bajPdfButtons" class="flex flex-wrap gap-2 mt-2"></div>
                <p class="text-xs text-gray-500 mt-1">Genera un PDF con todas las bajas del año.</p>
            </div>

            <!-- Calendario ANUAL (12 meses) -->
            <div id="ausBody" class="space-y-3">
                <div id="calYearGrid"
                     class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-5">
                </div>
            </div>
        </div>

        <!-- Footer fijo -->
        <div class="px-5 py-4 border-t flex items-center justify-end gap-2 bg-white">
            <button type="button" onclick="closeAusenciasModal()"
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    window.APP = {
        routes: {
            storeDays: @json(route('trabajadores.dias.store', ['trabajador' => '__ID__'])),
            getDays: @json(route('trabajadores.dias.get', ['trabajador' => '__ID__'])),
            pdfVac: @json(route('trabajadores.vacaciones.pdf', ['trabajador' => '__ID__'])),
            pdfPer:   @json(route('trabajadores.permisos.pdf',   ['trabajador' => '__ID__'])),
            pdfBaj:   @json(route('trabajadores.bajas.pdf',      ['trabajador' => '__ID__'])),
            fichajes: @json(route('trabajadores.fichajes.get', ['trabajador' => '__ID__'])),
            fichajesUnificado: @json(route('usuarios.fichajes.unificado', ['trabajador' => '__ID__'])),
        },
        csrf: @json(csrf_token()),
    };
</script>

<script src="{{ asset('js/ausencias.js') }}" defer></script>

@if(session()->has('scroll_y'))
    <script>
        // Restaura scroll tras el redirect
        window.addEventListener('load', () => {
            const y = Number(@json(session('scroll_y'))) || 0;
            window.scrollTo({ top: y, left: 0, behavior: 'auto' });
        });
    </script>
@endif
</body>
</html>
