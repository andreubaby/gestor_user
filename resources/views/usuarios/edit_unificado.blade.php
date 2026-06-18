<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Registros Asociados | Gestor de Usuarios Babyplant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a',
                        secondary: '#15803d',
                        polifonia: '#15803d',
                        pluton: '#7c3aed',
                        buscador: '#0e7490',
                        buscador2: '#115e59',
                        cronos: '#ee2a0f',
                        semillas: '#2dee0f',
                        store: '#0fc9ee',
                        zeus: '#f710b1',

                        // ✅ NUEVO: Fichajes
                        fichajes: '#2563eb'
                    },
                    boxShadow: {
                        soft: '0 10px 25px rgba(2,6,23,.08)'
                    }
                }
            }
        };
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans&display=swap" rel="stylesheet">

    <style>
        .section-anchor {
            scroll-margin-top: 110px;
        }
    </style>
</head>

<body class="min-h-screen bg-white text-slate-900 font-sans">

@php
    $active = match (true) {
        request()->routeIs('usuarios.*') => 'usuarios',
        request()->routeIs('fichajes.*') || request()->routeIs('fichajes.diarios.*') => 'fichajes',
        request()->routeIs('usuarios.onboarding.*') => 'onboarding',
        request()->routeIs('gestor.gestoria') => 'dashboard',
        request()->routeIs('rrhh.*') => 'rrhh',
        request()->routeIs('groups.assign.*') => 'asignar',
        request()->routeIs('tacografo.*') => 'tacografo',
        request()->routeIs('maria-app') => 'maria-app',
        default => '',
    };

    $tabBase = "inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-semibold transition
                focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200";
    $tabIdle = "text-slate-700 hover:bg-emerald-50 hover:text-emerald-800";
    $tabActive = "bg-emerald-600 text-white shadow";
@endphp

<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

<header class="sticky top-0 z-50 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">

        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">
                    Editar registros asociados
                </h1>
            </div>
        </div>

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

                {{-- Vincular (acceso directo) --}}
                <a href="{{ route('usuarios.vincular') }}"
                   class="{{ $tabBase }} {{ $active==='vincular' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active==='vincular' ? 'bg-white/15' : 'bg-slate-100' }}">🔗</span>
                    Vincular
                </a>

                {{-- RRHH (acceso directo) --}}
                <a href="{{ route('rrhh.documentos.index') }}"
                   class="{{ $tabBase }} {{ $active==='rrhh' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active==='rrhh' ? 'bg-white/15' : 'bg-slate-100' }}">📁</span>
                    RRHH
                </a>

                {{-- Dropdown Más --}}
                <div class="relative group">
                    <button type="button"
                            class="{{ $tabBase }} {{ in_array($active, ['dashboard','asignar','tacografo','maria-app']) ? $tabActive : $tabIdle }}
                           inline-flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full {{ in_array($active, ['dashboard','asignar','tacografo','maria-app']) ? 'bg-white/15' : 'bg-slate-100' }}">⋯</span>
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
                            <a href="/maria-app/"
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

                            {{-- Logout en el dropdown (opcional) --}}
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

@php
    $items = [];

    if (isset($usuario)) {
        $items[] = [
            'label' => 'Aplicación Principal',
            'color' => 'primary',
            'html'  => view('partials.form_usuario', ['usuario' => $usuario])->render(),
        ];
    }

    if (isset($trabajador)) {
        $items[] = [
            'label' => 'Aplicación Polifonía',
            'color' => 'polifonia',
            'html'  => view('partials.form_trabajador', [
                'trabajador' => $trabajador,
                'vinculo'    => $vinculo ?? null,
            ])->render(),
        ];
    }

    if (isset($usuarioPluton)) {
        $items[] = [
            'label' => 'Aplicación Plutón',
            'color' => 'pluton',
            'html'  => view('partials.form_pluton', ['usuarioPluton' => $usuarioPluton])->render(),
        ];
    }

    if (isset($usuarioBuscador)) {
        $items[] = [
            'label' => 'Usuario Buscador',
            'color' => 'buscador',
            'html'  => view('partials.form_user_buscador', ['userBuscador' => $usuarioBuscador])->render(),
        ];
    }

    if (isset($trabajadorBuscador)) {
        $items[] = [
            'label' => 'Trabajador Buscador',
            'color' => 'buscador2',
            'html'  => view('partials.form_worker_buscador', ['workerBuscador' => $trabajadorBuscador])->render(),
        ];
    }

    if (isset($userCronos)) {
        $items[] = [
            'label' => 'Usuario Cronos',
            'color' => 'cronos',
            'html'  => view('partials.form_user_cronos', ['userCronos' => $userCronos])->render(),
        ];
    }

    if (isset($userSemillas)) {
        $items[] = [
            'label' => 'Usuario Semillas',
            'color' => 'semillas',
            'html'  => view('partials.form_user_semillas', ['userSemillas' => $userSemillas])->render(),
        ];
    }

    if (isset($userStore)) {
        $items[] = [
            'label' => 'Usuario Store',
            'color' => 'store',
            'html'  => view('partials.form_user_store', ['userStore' => $userStore])->render(),
        ];
    }

    if (isset($userZeus)) {
        $items[] = [
            'label' => 'Usuario Zeus',
            'color' => 'zeus',
            'html'  => view('partials.form_user_zeus', ['userZeus' => $userZeus])->render(),
        ];
    }

    if (isset($userFichaje)) {
        $items[] = [
            'label' => 'Usuario Fichajes',
            'color' => 'fichajes',
            'html'  => view('partials.form_user_fichaje', [
                'userFichaje' => $userFichaje,
                'trabajador'  => $trabajador ?? null,
            ])->render(),
        ];
    }
@endphp

<div id="top" class="relative max-w-7xl mx-auto px-4 py-6">

    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-2xl md:text-3xl font-semibold text-emerald-800 tracking-tight">
                Editar Registros Asociados
            </h2>
            <p class="text-sm text-slate-600 mt-2">
                Usa ← → (teclado) o los botones. Cambia entre apps sin perder el contexto.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if(isset($vinculo))
                <a href="{{ route('usuarios.vincular.edit', ['vinculo' => $vinculo->id]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 text-white font-semibold shadow
                          hover:bg-amber-600 transition focus:outline-none focus:ring-4 focus:ring-amber-200">
                    🔗 Editar Vinculación
                </a>
            @else
                <a href="{{ route('usuarios.vincular') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow
                          hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200">
                    ➕ Crear Vinculación
                </a>
            @endif

            <a href="{{ route('usuarios.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-800 font-semibold
                      hover:bg-slate-200 transition focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                ← Volver
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
        <aside class="lg:col-span-4 xl:col-span-3">
            <div class="sticky top-24 rounded-2xl bg-white ring-1 ring-emerald-100 p-4 shadow-soft">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Módulos disponibles</p>
                <div class="mt-3 space-y-2">
                    @forelse($items as $index => $item)
                        <a href="#section-{{ $index }}"
                           class="flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-emerald-50 hover:text-emerald-900 hover:ring-emerald-200 transition">
                            <span>{{ $item['label'] }}</span>
                            <span class="text-xs text-slate-400">{{ $index + 1 }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No hay registros para mostrar.</p>
                    @endforelse
                </div>
            </div>
        </aside>

        <section class="lg:col-span-8 xl:col-span-9 space-y-5">
            @forelse($items as $index => $item)
                <article id="section-{{ $index }}" class="section-anchor rounded-2xl bg-white ring-1 ring-emerald-100 shadow-soft overflow-hidden">
                    <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 bg-emerald-50/60">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Módulo {{ $index + 1 }}</p>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $item['label'] }}</h3>
                        </div>
                        <a href="#top"
                           class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                            Ir arriba
                        </a>
                    </header>

                    <div class="p-5">
                        {!! $item['html'] !!}
                    </div>
                </article>
            @empty
                <article class="rounded-2xl bg-white ring-1 ring-emerald-100 p-5 shadow-soft">
                    <p class="text-sm text-slate-600">No hay registros para mostrar.</p>
                </article>
            @endforelse
        </section>
    </div>
</div>

</body>
</html>
