<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tacógrafo | Gestor de Usuarios Babyplant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<body class="min-h-screen bg-slate-50 text-slate-900">

@php
    $active = match (true) {
        request()->routeIs('usuarios.*') => 'usuarios',
        request()->routeIs('fichajes.*') || request()->routeIs('fichajes.diarios.*') => 'fichajes',
        request()->routeIs('usuarios.onboarding.*') => 'onboarding',
        request()->routeIs('gestor.gestoria') => 'dashboard',
        request()->routeIs('rrhh.*') => 'rrhh',
        request()->routeIs('groups.assign.*') => 'asignar',
        request()->routeIs('tacografo.*') => 'tacografo',
        default => '',
    };

    $tabBase = "inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-semibold transition
                focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200";
    $tabIdle = "text-slate-700 hover:bg-emerald-50 hover:text-emerald-800";
    $tabActive = "bg-emerald-600 text-white shadow";

    // Camiones con fecha expirada (> 3 meses desde hoy)
    $limite3m = now()->subMonths(3);
    $camionesExpirados = $tacografos->filter(fn($t) =>
        $t->tipo === 'camion' && $t->fecha && $t->fecha->lt($limite3m)
    );
    $totalExpirados = $camionesExpirados->count();
@endphp

<!-- Fondo decorativo -->
<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

{{-- HEADER --}}
<header class="sticky top-0 z-40 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">Tacógrafo</h1>
            </div>
        </div>

        {{-- pega aquí tu nav actual --}}
        {{-- NAV --}}
        @php
            // Base styles (ponlos donde ya los tengas, esto es por si lo pegas tal cual)
            $tabBase = "inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-semibold transition
                        focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200";
            $tabIdle = "text-slate-700 hover:bg-emerald-50 hover:text-emerald-800";
            $tabActive = "bg-emerald-600 text-white shadow";
        @endphp

        <nav class="flex items-center gap-3">
            {{-- Tabs izquierda --}}
            <div class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-1.5 py-1 shadow-sm">

                {{-- Listado --}}
                <a href="{{ route('usuarios.index') }}"
                   class="{{ $tabBase }} {{ $active==='usuarios' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active==='usuarios' ? 'bg-white/15' : 'bg-slate-100' }}">👤</span>
                    Listado
                </a>

                {{-- Fichajes --}}
                <a href="{{ route('fichajes.diarios.index') }}"
                   class="{{ $tabBase }} {{ $active==='fichajes' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active==='fichajes' ? 'bg-white/15' : 'bg-slate-100' }}">⏱️</span>
                    Fichajes
                </a>

                {{-- Onboarding --}}
                <a href="{{ route('usuarios.onboarding.create') }}"
                   class="{{ $tabBase }} {{ $active==='onboarding' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active==='onboarding' ? 'bg-white/15' : 'bg-slate-100' }}">🧾</span>
                    Onboarding
                </a>

                {{-- Dropdown Más --}}
                <div class="relative group">
                    <button type="button"
                            class="{{ $tabBase }} {{ in_array($active, ['dashboard','rrhh','vincular','asignar','tacografo']) ? $tabActive : $tabIdle }}
                           inline-flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full {{ in_array($active, ['dashboard','rrhh','vincular','asignar','tacografo']) ? 'bg-white/15' : 'bg-slate-100' }}">⋯</span>
                        Más
                        <span class="grid h-5 w-5 place-items-center rounded-full bg-slate-100 text-slate-700 transition
                             group-hover:bg-emerald-100 group-hover:text-emerald-800">
                    <svg class="h-3.5 w-3.5 transition-transform group-hover:rotate-180 group-focus-within:rotate-180"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
                    </button>

                    {{-- buffer para hover --}}
                    <div class="absolute right-0 top-full h-2 w-56"></div>

                    <div class="absolute right-0 mt-2 hidden w-72 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5
                        group-hover:block group-focus-within:block">
                        <div class="p-2">

                            {{-- Dashboard --}}
                            <a href="{{ route('gestor.gestoria') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                              {{ $active==='dashboard' ? 'bg-emerald-50 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900' }}">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100">🏠</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Dashboard</div>
                                    <div class="text-xs text-slate-500">Vista general</div>
                                </div>
                            </a>

                            <div class="my-2 h-px bg-slate-100"></div>

                            {{-- RRHH --}}
                            <a href="{{ route('rrhh.documentos.index') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                              {{ $active==='rrhh' ? 'bg-emerald-50 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900' }}">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-sky-50 ring-1 ring-sky-100">📁</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">RRHH</div>
                                    <div class="text-xs text-slate-500">Documentos</div>
                                </div>
                            </a>

                            {{-- Vincular --}}
                            <a href="{{ route('usuarios.vincular') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                              {{ $active==='vincular' ? 'bg-emerald-50 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900' }}">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100">🔗</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Vincular</div>
                                    <div class="text-xs text-slate-500">Unificar cuentas</div>
                                </div>
                            </a>

                            {{-- Asignar grupo --}}
                            <a href="{{ route('groups.assign.create') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                              {{ $active==='asignar' ? 'bg-emerald-50 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900' }}">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-violet-50 ring-1 ring-violet-100">👥</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Asignar grupo</div>
                                    <div class="text-xs text-slate-500">Gestión de grupos</div>
                                </div>
                            </a>

                            {{-- Tacógrafo --}}
                            <a href="{{ route('tacografo.index') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                              {{ $active==='tacografo' ? 'bg-emerald-50 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900' }}">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-amber-50 ring-1 ring-amber-100">🚚</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Tacógrafo</div>
                                    <div class="text-xs text-slate-500">Camión / Camionero</div>
                                </div>
                            </a>

                            {{-- Maria App --}}
                            <a href="{{ request()->getScheme() }}://{{ request()->getHost() }}:{{ parse_url(config('app.url'), PHP_URL_PORT) }}/maria-app"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                              {{ $active==='maria-app' ? 'bg-emerald-50 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900' }}">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-pink-50 ring-1 ring-pink-100">🌸</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Maria App</div>
                                    <div class="text-xs text-slate-500">Aplicación María</div>
                                </div>
                            </a>

                            {{-- Nuevo usuario fichajes --}}
                            <a href="{{ route('fichajes.users.create') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                                      text-slate-700 hover:bg-emerald-50 hover:text-emerald-900">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-teal-50 ring-1 ring-teal-100">➕</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Nuevo usuario</div>
                                    <div class="text-xs text-slate-500">BD de fichajes</div>
                                </div>
                            </a>

                            <div class="my-2 h-px bg-slate-100"></div>

                            {{-- Logout --}}
                            <a href="#"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 transition">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-red-50 ring-1 ring-red-100">⎋</span>
                                Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>

<main class="max-w-7xl mx-auto space-y-5 px-4 pb-10 pt-6 relative">

    {{-- Toast success --}}
    @if(session('success'))
        <div class="rounded-2xl bg-white ring-1 ring-emerald-200 px-4 py-3 shadow-soft">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 grid h-8 w-8 place-items-center rounded-xl bg-emerald-100 text-emerald-700">
                    ✓
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900">Acción completada</div>
                    <div class="text-sm text-slate-600">{{ session('success') }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Banner alerta camiones > 3 meses --}}
    @if($totalExpirados > 0)
        <div id="bannerExpirados"
             class="flex items-start gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
            <div class="mt-0.5 grid h-10 w-10 flex-shrink-0 place-items-center rounded-2xl bg-amber-100 text-2xl">
                ⚠️
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-amber-900">
                    {{ $totalExpirados }} {{ $totalExpirados === 1 ? 'camión tiene' : 'camiones tienen' }}
                    la fecha de tacógrafo desactualizada (más de 3 meses)
                </p>
                <ul class="mt-1.5 space-y-0.5">
                    @foreach($camionesExpirados as $ce)
                        <li class="text-xs text-amber-800">
                            🚚 <span class="font-semibold">{{ $ce->valor }}</span>
                            — último registro:
                            <span class="font-semibold">
                                {{ optional($ce->fecha)->format('d/m/Y') ?? 'Sin fecha' }}
                            </span>
                            <span class="ml-1 rounded-full bg-amber-200 px-2 py-0.5 text-[11px] font-semibold text-amber-900">
                                {{ optional($ce->fecha)->locale('es')->diffForHumans() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <button type="button"
                    onclick="document.getElementById('bannerExpirados').remove()"
                    class="mt-0.5 h-8 w-8 flex-shrink-0 grid place-items-center rounded-xl bg-amber-100 text-amber-700
                           hover:bg-amber-200 transition text-sm font-bold"
                    title="Cerrar aviso">
                ✕
            </button>
        </div>
    @endif

    {{-- Top bar --}}
    <section class="rounded-3xl border border-emerald-100 bg-white/80 backdrop-blur shadow-soft">
        <div class="p-5 md:p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-2xl font-semibold text-slate-900 tracking-tight">
                            Registros de tacógrafo
                        </h2>

                        <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-800 px-3 py-1 text-xs font-semibold ring-1 ring-emerald-100">
                            Camión / Camionero
                        </span>
                    </div>

                    <p class="text-sm text-slate-600 mt-1">
                        Busca por valor u observaciones, filtra por tipo y estado.
                    </p>
                </div>

                <a href="{{ route('tacografo.create') }}"
                   class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white
                          shadow hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200">
                    <span class="grid h-6 w-6 place-items-center rounded-xl bg-white/15">＋</span>
                    Nuevo registro
                </a>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('tacografo.index') }}"
                  class="mt-5 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                <div class="md:col-span-5">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Buscar</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
                        <input type="text" name="q" value="{{ $q }}"
                               placeholder="valor u observaciones..."
                               class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                                      focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo</label>
                    <select name="tipo"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                                   focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                        <option value="">Todos</option>
                        <option value="camion" {{ $tipo === 'camion' ? 'selected' : '' }}>🚚 Camión</option>
                        <option value="camionero" {{ $tipo === 'camionero' ? 'selected' : '' }}>🧑‍✈️ Camionero</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Estado</label>
                    <select name="activo"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-2xl shadow-sm bg-white
                                   focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                        <option value="" {{ ($activo === null || $activo === '') ? 'selected' : '' }}>Todos</option>
                        <option value="1" {{ (string)$activo === '1' ? 'selected' : '' }}>✅ Activos</option>
                        <option value="0" {{ (string)$activo === '0' ? 'selected' : '' }}>⛔ Inactivos</option>
                    </select>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl
                                   bg-slate-900 text-white font-semibold hover:bg-slate-800 transition
                                   focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                        Aplicar
                    </button>

                    <a href="{{ route('tacografo.index') }}"
                       class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-2xl
                              bg-slate-100 text-slate-800 hover:bg-slate-200 transition font-semibold
                              focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </section>

    {{-- Table --}}
    <section class="overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-soft">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm table-auto">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wider sticky top-0 z-10">
                <tr>
                    <th class="px-6 py-4 text-left">Tipo</th>
                    <th class="px-6 py-4 text-left">Valor</th>
                    <th class="px-6 py-4 text-left">Fecha</th>
                    <th class="px-6 py-4 text-left">Observaciones</th>
                    <th class="px-6 py-4 text-left">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
                </thead>

                <tbody id="tacografoTbody" class="divide-y divide-slate-100">
                @forelse($tacografos as $t)
                    @php
                        $tipoLabel = $t->tipo === 'camion' ? '🚚 Camión' : '🧑‍✈️ Camionero';
                        $tipoChip  = $t->tipo === 'camion'
                            ? 'bg-sky-50 text-sky-800 ring-sky-100'
                            : 'bg-violet-50 text-violet-800 ring-violet-100';
                        $expirado  = $t->tipo === 'camion' && $t->fecha && $t->fecha->lt(now()->subMonths(3));
                    @endphp

                    <tr class="hover:bg-emerald-50/40 transition {{ $expirado ? 'bg-amber-50/50' : '' }}"
                        data-row-id="{{ $t->id }}"
                        data-tipo="{{ $t->tipo }}"
                        data-expirado="{{ $expirado ? '1' : '0' }}">

                        <td class="px-6 py-4">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $tipoChip }}">
                            {{ $tipoLabel }}
                        </span>
                        </td>

                        <td class="px-6 py-4">
                        <span class="inline-flex items-center rounded-2xl bg-slate-100 px-3 py-1.5 font-semibold text-slate-800">
                            {{ $t->valor }}
                        </span>
                        </td>

                        <td class="px-6 py-4 text-slate-700 tabular-nums">
                            <div class="flex items-center gap-2 flex-wrap">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-xl {{ $expirado ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-200 hover:bg-amber-200' : 'bg-slate-100 text-slate-800 hover:bg-emerald-50 hover:text-emerald-800' }} px-3 py-1.5 text-sm font-semibold transition"
                                    data-open-fecha
                                    data-id="{{ $t->id }}"
                                    data-fecha="{{ optional($t->fecha)->format('Y-m-d') }}"
                                    data-tipo="{{ $t->tipo }}"
                                    data-valor="{{ $t->valor }}"
                                >
                                    📅 <span data-fecha-text>{{ optional($t->fecha)->format('d/m/Y') }}</span>
                                </button>
                                @if($expirado)
                                    <span data-badge-expirado
                                          class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                        ⚠️ +3 meses
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 text-slate-600 max-w-[520px]">
                        <span class="{{ $t->observaciones ? '' : 'text-slate-400' }}">
                            {{ $t->observaciones ?: 'Sin observaciones' }}
                        </span>
                        </td>

                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('tacografo.toggle', $t) }}">
                                @csrf
                                <button class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition
                                {{ $t->activo
                                    ? 'bg-emerald-50 text-emerald-800 ring-emerald-100 hover:bg-emerald-100'
                                    : 'bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100' }}">
                                    <span class="h-2 w-2 rounded-full {{ $t->activo ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $t->activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </form>
                        </td>

                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="{{ route('tacografo.edit', $t) }}"
                               class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-slate-100 text-slate-700
                                  hover:bg-slate-200 transition"
                               title="Editar">
                                ✏️
                            </a>

                            <form method="POST" action="{{ route('tacografo.destroy', $t) }}" class="inline"
                                  onsubmit="return confirm('¿Eliminar este registro?');">
                                @csrf @method('DELETE')
                                <button class="inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-red-50 text-red-700
                                           hover:bg-red-100 transition"
                                        title="Borrar">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                            No hay registros con esos filtros.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-4 py-3 bg-white">
            {{ $tacografos->links() }}
        </div>
    </section>

    <!-- Modal Fecha -->
    <div id="fechaModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm"></div>

        <div class="relative mx-auto mt-24 w-[92%] max-w-md">
            <div class="rounded-3xl bg-white shadow-xl ring-1 ring-black/5 overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div id="fechaSubtitle" class="text-sm font-semibold text-emerald-700">Tacógrafo</div>
                            <div class="text-xl font-semibold text-slate-900">Cambiar fecha</div>
                            <div id="fechaTitle" class="mt-1 text-sm text-slate-600"></div>
                        </div>
                        <button type="button" id="fechaClose"
                                class="h-9 w-9 rounded-2xl bg-slate-100 hover:bg-slate-200 transition grid place-items-center">
                            ✕
                        </button>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <input type="date" id="fechaInput"
                           class="w-full px-4 py-3 border border-slate-200 rounded-2xl shadow-sm bg-white
                              focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">

                    <div class="text-xs text-slate-500">
                        Al elegir un día se guardará automáticamente.
                    </div>

                    <div id="fechaMsg" class="hidden rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 px-4 py-3 text-sm text-emerald-900">
                        Guardado ✅
                    </div>

                    <div id="fechaErr" class="hidden rounded-2xl bg-red-50 ring-1 ring-red-200 px-4 py-3 text-sm text-red-800">
                        Error ❌
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<script>

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('fechaModal');
        const closeBtn = document.getElementById('fechaClose');
        const input = document.getElementById('fechaInput');
        const msg = document.getElementById('fechaMsg');
        const err = document.getElementById('fechaErr');
        const subtitle = document.getElementById('fechaSubtitle');
        const title = document.getElementById('fechaTitle');

        const tbody = document.getElementById('tacografoTbody');

        let currentId = null;
        let currentBtn = null;

        const openModal = (id, fecha, btn) => {
            currentId = id;
            currentBtn = btn;

            input.value = fecha || '';
            msg.classList.add('hidden');
            err.classList.add('hidden');

            const tipo = btn.dataset.tipo === 'camion' ? '🚚 Camión' : '🧑‍✈️ Camionero';
            subtitle.textContent = `Tacógrafo · ${tipo}`;
            title.textContent = btn.dataset.valor ? `Valor: ${btn.dataset.valor}` : '';

            modal.classList.remove('hidden');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            currentId = null;
            currentBtn = null;
        };

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            // click en el overlay
            if (e.target === modal.firstElementChild) closeModal();
        });

        // Click en cualquier fecha editable
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-open-fecha]');
            if (!btn) return;
            openModal(btn.dataset.id, btn.dataset.fecha, btn);
        });

        function reorderRows() {
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr'))
                // ignora la fila "No hay registros..."
                .filter(r => r.dataset.rowId);

            const tipoOrder = (tipo) => (tipo === 'camion' ? 0 : 1);

            const getFechaIso = (row) => {
                // para camiones la fecha está en el botón
                const btn = row.querySelector('[data-open-fecha]');
                return btn?.dataset?.fecha || '';
            };

            rows.sort((a, b) => {
                const ta = tipoOrder(a.dataset.tipo);
                const tb = tipoOrder(b.dataset.tipo);
                if (ta !== tb) return ta - tb;

                // Dentro de camiones: fecha desc (YYYY-MM-DD)
                if (a.dataset.tipo === 'camion' && b.dataset.tipo === 'camion') {
                    const fa = getFechaIso(a);
                    const fb = getFechaIso(b);
                    if (fa !== fb) return fb.localeCompare(fa);
                }

                // fallback por id desc (estabilidad)
                const ida = parseInt(a.dataset.rowId, 10);
                const idb = parseInt(b.dataset.rowId, 10);
                return idb - ida;
            });

            const frag = document.createDocumentFragment();
            rows.forEach(r => frag.appendChild(r));
            tbody.appendChild(frag);
        }

        // Guardar al cambiar fecha
        input.addEventListener('change', async () => {
            if (!currentId || !currentBtn) return;

            msg.classList.add('hidden');
            err.classList.add('hidden');

            try {
                const url = `{{ url('/tacografo') }}/${currentId}/fecha`;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ fecha: input.value })
                });

                const contentType = res.headers.get('content-type') || '';
                let data = null;

                if (contentType.includes('application/json')) {
                    data = await res.json();
                } else {
                    const text = await res.text(); // probablemente HTML (login / error)
                    err.textContent = 'Respuesta no JSON. Mira consola.';
                    err.classList.remove('hidden');
                    console.error('Respuesta no JSON:', text);
                    return;
                }

                if (!res.ok || data.ok === false) {
                    err.textContent = data.message || `Error guardando (HTTP ${res.status})`;
                    err.classList.remove('hidden');
                    console.error('Error JSON:', data);
                    return;
                }

                // 1) Actualizar texto y dataset del botón
                const span = currentBtn.querySelector('[data-fecha-text]');
                if (span) {
                    span.textContent = data.fecha;
                } else {
                    currentBtn.textContent = `📅 ${data.fecha}`;
                }
                currentBtn.dataset.fecha = data.fecha_iso;

                // 2) Recalcular si la fecha nueva supera los 3 meses
                const row = currentBtn.closest('tr');
                const now3m = new Date();
                now3m.setMonth(now3m.getMonth() - 3);
                const nuevaFecha = data.fecha_iso ? new Date(data.fecha_iso) : null;
                const sigueExpirado = nuevaFecha && nuevaFecha < now3m && currentBtn.dataset.tipo === 'camion';

                if (row) {
                    // Fondo fila
                    row.dataset.expirado = sigueExpirado ? '1' : '0';
                    row.classList.toggle('bg-amber-50/50', sigueExpirado);

                    // Badge ⚠️ +3 meses
                    const badgeContainer = currentBtn.parentElement;
                    let badge = badgeContainer?.querySelector('[data-badge-expirado]');
                    if (sigueExpirado && !badge && badgeContainer) {
                        badge = document.createElement('span');
                        badge.dataset.badgeExpirado = '';
                        badge.className = 'inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-200';
                        badge.textContent = '⚠️ +3 meses';
                        badgeContainer.appendChild(badge);
                    } else if (!sigueExpirado && badge) {
                        badge.remove();
                    }

                    // Color del botón de fecha
                    if (sigueExpirado) {
                        currentBtn.className = currentBtn.className
                            .replace('bg-slate-100 text-slate-800 hover:bg-emerald-50 hover:text-emerald-800', '')
                            + ' bg-amber-100 text-amber-900 ring-1 ring-amber-200 hover:bg-amber-200';
                    } else {
                        currentBtn.className = currentBtn.className
                            .replace('bg-amber-100 text-amber-900 ring-1 ring-amber-200 hover:bg-amber-200', '')
                            + ' bg-slate-100 text-slate-800 hover:bg-emerald-50 hover:text-emerald-800';
                    }

                    // Animación/feedback fila
                    row.classList.add('ring-2','ring-emerald-300');
                    setTimeout(() => row.classList.remove('ring-2','ring-emerald-300'), 900);
                }

                // 3) Reordenar tabla (solo página actual)
                reorderRows();

                // 4) Mensaje + cerrar
                msg.classList.remove('hidden');
                setTimeout(closeModal, 350);

            } catch (e) {
                err.textContent = 'Error de red guardando fecha';
                err.classList.remove('hidden');
            }
        });
    });
</script>

</body>
</html>
