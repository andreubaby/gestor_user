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
                        bp: {
                            green: '#16a34a',
                            green2: '#22c55e'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-white text-slate-900">

{{-- Fondo suave blanco/verde --}}
<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

{{-- Navbar --}}
<header class="relative border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">

        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-12 w-12 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">Babyplant</h1>
            </div>
        </div>

        <nav class="flex items-center gap-2 sm:gap-3">
            <a href="{{ route('usuarios.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                Usuarios
            </a>

            <a href="{{ route('usuarios.vincular') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition">
                Vincular
            </a>

            <a href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-red-50 hover:text-red-700 transition">
                Salir
            </a>
        </nav>
    </div>
</header>

{{-- Logout form --}}
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

<main class="relative mx-auto max-w-7xl px-4 py-8 sm:py-10">

    <section class="rounded-3xl border border-emerald-200 bg-white/80 p-6 shadow-xl backdrop-blur sm:p-10">
        <div class="flex flex-col gap-2">
            <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight">
                ¡Bienvenido, {{ Auth::user()->name }}!
            </h2>
            <p class="text-sm sm:text-base text-slate-600">
                Desde aquí puedes gestionar usuarios y vincular cuentas en el sistema.
            </p>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2">
            {{-- Card: Usuarios --}}
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-emerald-900">Usuarios</h3>
                        <p class="mt-2 text-sm text-slate-700">
                            Consulta y edita los usuarios registrados.
                        </p>
                    </div>

                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white shadow ring-1 ring-emerald-200">
                        👤
                    </span>
                </div>

                <a href="{{ route('usuarios.index') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white
                          hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200 transition">
                    Ir a Usuarios
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

            {{-- Card: Vincular --}}
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm hover:shadow-md transition">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Vincular</h3>
                        <p class="mt-2 text-sm text-slate-700">
                            Vincula cuentas por email para unificar registros.
                        </p>
                    </div>

                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 shadow ring-1 ring-emerald-200">
                        🔗
                    </span>
                </div>

                <a href="{{ route('usuarios.vincular') }}"
                   class="mt-5 inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900
                          hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-200 transition">
                    Ir a Vincular
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-between rounded-2xl border border-emerald-200 bg-white p-4">
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
