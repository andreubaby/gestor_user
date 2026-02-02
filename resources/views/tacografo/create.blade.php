<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nuevo registro | Tacógrafo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

{{-- HEADER: pega aquí tu mismo header/nav --}}
<header class="sticky top-0 z-40 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">Tacógrafo · Nuevo</h1>
            </div>
        </div>

        {{-- pega aquí tu nav actual --}}
        <nav class="flex items-center justify-between">
            <!-- IZQ: Tabs / acciones -->
            <div class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-1.5 py-1 shadow-sm">
                <a href="{{ route('usuarios.index') }}"
                   class="inline-flex items-center rounded-full px-3 py-2 text-sm font-semibold text-slate-700
                  hover:bg-emerald-50 hover:text-emerald-800 transition
                  focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200">
                    Listado
                </a>

                <a href="{{ route('usuarios.vincular') }}"
                   class="inline-flex items-center rounded-full px-3 py-2 text-sm font-semibold text-slate-700
                  hover:bg-emerald-50 hover:text-emerald-800 transition
                  focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200">
                    Vincular
                </a>

                <!-- Responsive: si se estrecha, se va a “Más” -->
                <a href="{{ route('fichajes.diarios.index') }}"
                   class="hidden lg:inline-flex items-center rounded-full px-3 py-2 text-sm font-semibold text-slate-700
                  hover:bg-emerald-50 hover:text-emerald-800 transition
                  focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200">
                    Fichajes
                </a>

                <!-- Dropdown “Más” -->
                <div class="relative group">
                    <button type="button"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold text-slate-700
                           hover:bg-slate-50 hover:text-slate-900 transition
                           focus:outline-none focus-visible:ring-4 focus-visible:ring-slate-200">
                        Más
                        <span class="grid h-5 w-5 place-items-center rounded-full bg-slate-100 text-slate-700 transition
                             group-hover:bg-emerald-100 group-hover:text-emerald-800">
                    <svg class="h-3.5 w-3.5 transition-transform group-hover:rotate-180 group-focus-within:rotate-180"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
                    </button>

                    <!-- buffer para que no se cierre al mover el ratón -->
                    <div class="absolute right-0 top-full h-2 w-56"></div>

                    <div class="absolute right-0 mt-2 hidden w-64 overflow-hidden rounded-2xl border border-slate-200
                        bg-white shadow-xl ring-1 ring-black/5
                        group-hover:block group-focus-within:block">
                        <div class="p-1.5">
                            <a href="{{ route('usuarios.onboarding.create') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700
                              hover:bg-emerald-50 hover:text-emerald-900 transition">
                                <span class="h-2 w-2 rounded-full bg-emerald-500/70"></span>
                                Onboarding
                            </a>

                            <a href="{{ route('rrhh.documentos.index') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700
                              hover:bg-emerald-50 hover:text-emerald-900 transition">
                                <span class="h-2 w-2 rounded-full bg-sky-500/70"></span>
                                RRHH
                            </a>

                            <a href="{{ route('groups.assign.create') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700
                              hover:bg-emerald-50 hover:text-emerald-900 transition">
                                <span class="h-2 w-2 rounded-full bg-violet-500/70"></span>
                                Asignar grupo
                            </a>

                            <a href="{{ route('tacografo.index') }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700
                              hover:bg-emerald-50 hover:text-emerald-900 transition">
                                <span class="h-2 w-2 rounded-full bg-violet-500/70"></span>
                                Tacógrafo
                            </a>

                            <!-- En pequeño, Fichajes va aquí -->
                            <a href="{{ route('fichajes.diarios.index') }}"
                               class="lg:hidden flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700
                              hover:bg-emerald-50 hover:text-emerald-900 transition">
                                <span class="h-2 w-2 rounded-full bg-amber-500/70"></span>
                                Fichajes
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DER: Logout separado (limpio, no duplica) -->
            <a href="#"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold
              text-red-600 shadow-sm hover:bg-red-50 hover:text-red-700 transition
              focus:outline-none focus-visible:ring-4 focus-visible:ring-red-200">
                Salir
            </a>
        </nav>
    </div>
</header>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>

<main class="max-w-3xl mx-auto px-4 pb-10 pt-6 relative">

    <section class="rounded-3xl border border-emerald-100 bg-white/80 backdrop-blur shadow-soft overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Crear registro</h2>
                    <p class="text-sm text-slate-600 mt-1">Añade un registro de camión o camionero.</p>
                </div>

                <a href="{{ route('tacografo.index') }}"
                   class="inline-flex items-center gap-2 rounded-2xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-800
                          hover:bg-slate-200 transition">
                    ← Volver
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('tacografo.store') }}" class="p-6 space-y-5">
            @csrf

            {{-- Errores --}}
            @if ($errors->any())
                <div class="rounded-2xl bg-red-50 ring-1 ring-red-200 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold mb-1">Revisa el formulario:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo</label>
                    <select name="tipo"
                            class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white shadow-sm
                                   focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                        <option value="camion" {{ old('tipo','camion') === 'camion' ? 'selected' : '' }}>🚚 Camión</option>
                        <option value="camionero" {{ old('tipo') === 'camionero' ? 'selected' : '' }}>🧑‍✈️ Camionero</option>
                    </select>
                </div>

                <div class="md:col-span-5">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Valor</label>
                    <input type="text" name="valor" value="{{ old('valor') }}"
                           placeholder="Matrícula / Nombre / Identificador..."
                           class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white shadow-sm
                                  focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha', $today) }}"
                           class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white shadow-sm
                                  focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">
                </div>

                <div class="md:col-span-12">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="4"
                              placeholder="Opcional..."
                              class="w-full px-4 py-3 border border-slate-200 rounded-2xl bg-white shadow-sm
                                     focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400 focus:outline-none">{{ old('observaciones') }}</textarea>
                </div>

                <div class="md:col-span-12">
                    <label class="inline-flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200">
                        <input type="checkbox" name="activo" value="1"
                               class="h-4 w-4 rounded border-slate-300"
                            {{ old('activo', 1) ? 'checked' : '' }}>
                        <span class="text-sm font-semibold text-slate-800">Activo</span>
                        <span class="text-xs text-slate-500">(si lo desmarcas, se guarda como inactivo)</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white
                               shadow hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200">
                    ✅ Guardar
                </button>

                <a href="{{ route('tacografo.index') }}"
                   class="inline-flex items-center gap-2 rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-800
                          hover:bg-slate-200 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
</main>

</body>
</html>
