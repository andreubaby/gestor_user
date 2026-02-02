<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Gestor de Usuarios Babyplant</title>
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bp: { green: '#16a34a', green2: '#22c55e' }
                    },
                    boxShadow: { soft:'0 10px 25px rgba(2,6,23,.08)' }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-white text-slate-900">

@php
    // Para resaltar la opción activa de esta vista:
    $active = $active ?? 'dashboard';

    $tabBase = "inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold transition
                focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200";
    $tabIdle = "text-slate-700 hover:bg-emerald-50 hover:text-emerald-800";
    $tabActive = "bg-emerald-600 text-white shadow-sm hover:bg-emerald-700";
@endphp

{{-- Fondo suave blanco/verde --}}
<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

{{-- Navbar --}}
<header class="sticky top-0 z-40 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-4">

        {{-- Branding --}}
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-12 w-12 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">Dashboard</h1>
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

{{-- Logout form --}}
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

<main class="relative mx-auto max-w-7xl px-4 py-8 sm:py-10">

    <section class="rounded-3xl border border-emerald-200 bg-white/80 p-6 shadow-soft backdrop-blur sm:p-10">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                ¡Bienvenido, {{ Auth::user()->name }}! 👋
            </h2>
            <p class="text-sm sm:text-base text-slate-600">
                Accesos rápidos para la gestión diaria del sistema.
            </p>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">

            {{-- Usuarios --}}
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-emerald-900">Usuarios</h3>
                        <p class="mt-2 text-sm text-slate-700">Listado y edición de usuarios.</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white shadow ring-1 ring-emerald-200">👤</span>
                </div>
                <a href="{{ route('usuarios.index') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white
                      hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200 transition">
                    Ir a Usuarios →
                </a>
            </div>

            {{-- Fichajes --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Fichajes</h3>
                        <p class="mt-2 text-sm text-slate-700">Fichajes diarios y exportaciones.</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 shadow ring-1 ring-emerald-200">⏱️</span>
                </div>
                <a href="{{ route('fichajes.diarios.index') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800
                      hover:bg-slate-100 focus:ring-4 focus:ring-slate-300/40 transition">
                    Ir a Fichajes →
                </a>
            </div>

            {{-- Onboarding --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Onboarding</h3>
                        <p class="mt-2 text-sm text-slate-700">Altas y documentación inicial.</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 shadow ring-1 ring-emerald-200">🧾</span>
                </div>
                <a href="{{ route('usuarios.onboarding.create') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900
                      hover:bg-emerald-100 focus:ring-4 focus:ring-emerald-200 transition">
                    Ir a Onboarding →
                </a>
            </div>

            {{-- RRHH --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">RRHH</h3>
                        <p class="mt-2 text-sm text-slate-700">Documentación laboral.</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 shadow ring-1 ring-sky-200">📁</span>
                </div>
                <a href="{{ route('rrhh.documentos.index') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-900
                      hover:bg-sky-100 focus:ring-4 focus:ring-sky-200 transition">
                    Ir a RRHH →
                </a>
            </div>

            {{-- Vincular --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Vincular</h3>
                        <p class="mt-2 text-sm text-slate-700">Unificar cuentas por email.</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 shadow ring-1 ring-emerald-200">🔗</span>
                </div>
                <a href="{{ route('usuarios.vincular') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900
                      hover:bg-emerald-100 focus:ring-4 focus:ring-emerald-200 transition">
                    Ir a Vincular →
                </a>
            </div>

            {{-- Asignar grupo --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Asignar grupo</h3>
                        <p class="mt-2 text-sm text-slate-700">Gestión de grupos.</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-50 shadow ring-1 ring-violet-200">👥</span>
                </div>
                <a href="{{ route('groups.assign.create') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-900
                      hover:bg-violet-100 focus:ring-4 focus:ring-violet-200 transition">
                    Ir a Asignar grupo →
                </a>
            </div>

            {{-- Tacógrafo --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Tacógrafo</h3>
                        <p class="mt-2 text-sm text-slate-700">Camiones y camioneros.</p>
                    </div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-50 shadow ring-1 ring-amber-200">🚚</span>
                </div>
                <a href="{{ route('tacografo.index') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white
                      hover:bg-slate-800 focus:ring-4 focus:ring-slate-300/40 transition">
                    Ir a Tacógrafo →
                </a>
            </div>

        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-white p-4">
            <p class="text-sm text-slate-600">
                Sesión iniciada como <span class="font-semibold text-slate-900">{{ Auth::user()->email }}</span>
            </p>

            <a href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-sm font-semibold text-red-700 hover:text-red-800 underline underline-offset-4">
                Cerrar sesión
            </a>
        </div>
    </section>

</main>

</body>
</html>
