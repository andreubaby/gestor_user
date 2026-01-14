<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Registros Asociados | Gestor de Usuarios Babyplant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

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
        .ry-6 { transform: translateX(-16rem) scale(.92) rotateY(8deg); }
        .ry--6 { transform: translateX(16rem) scale(.92) rotateY(-8deg); }
        .card-base { will-change: transform, opacity; }
        .glass { background: rgba(255,255,255,.80); backdrop-filter: blur(10px); }
    </style>
</head>

<body class="min-h-screen bg-white text-slate-900 font-sans">

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
            'html'  => view('partials.form_trabajador', ['trabajador' => $trabajador])->render(),
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

<div class="relative max-w-7xl mx-auto px-4 py-6"
     x-data="carousel()"
     x-init="init"
     @keydown.window.arrow-right.prevent="next()"
     @keydown.window.arrow-left.prevent="prev()"
     @keydown.window.escape.prevent="goBack()">

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

    <div class="mt-5 flex items-center gap-2 flex-wrap">
        <template x-for="(item, i) in items" :key="i">
            <button type="button"
                    @click="jump(i)"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold ring-1 transition
                           focus:outline-none focus:ring-4"
                    :class="i===current
                        ? 'bg-emerald-600 text-white ring-emerald-600 focus:ring-emerald-200'
                        : 'bg-white/80 text-slate-700 ring-slate-200 hover:bg-emerald-50 hover:ring-emerald-200 focus:ring-slate-200'">
                <span x-text="item.label"></span>
            </button>
        </template>
    </div>

    <div class="mt-6 relative flex items-center justify-center h-[560px] overflow-hidden rounded-3xl ring-1 ring-emerald-100 bg-white/60 backdrop-blur shadow-soft">

        <template x-for="(item, index) in items" :key="index">
            <div class="card-base absolute w-[360px] sm:w-[420px] h-[500px] rounded-2xl ring-1 bg-white shadow-xl p-6 overflow-y-auto transition-all duration-700"
                 :class="cardClass(index)">

                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-500">Módulo</div>
                        <h3 class="text-xl font-semibold tracking-tight"
                            :class="'text-' + item.color"
                            x-text="item.label"></h3>
                    </div>

                    <div class="inline-flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full"
                              :class="'bg-' + item.color"></span>
                        <span class="text-xs font-semibold text-slate-500"
                              x-text="(current+1) + ' / ' + items.length"></span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4" x-html="item.html"></div>
            </div>
        </template>

        <div class="absolute left-4 top-1/2 -translate-y-1/2">
            <button @click="prev"
                    class="h-11 w-11 rounded-full bg-white ring-1 ring-slate-200 shadow hover:bg-emerald-50 hover:ring-emerald-200 transition
                           text-slate-700 font-bold focus:outline-none focus:ring-4 focus:ring-emerald-200"
                    aria-label="Anterior">
                ‹
            </button>
        </div>

        <div class="absolute right-4 top-1/2 -translate-y-1/2">
            <button @click="next"
                    class="h-11 w-11 rounded-full bg-white ring-1 ring-slate-200 shadow hover:bg-emerald-50 hover:ring-emerald-200 transition
                           text-slate-700 font-bold focus:outline-none focus:ring-4 focus:ring-emerald-200"
                    aria-label="Siguiente">
                ›
            </button>
        </div>
    </div>

    <p class="text-center mt-6 text-xs text-slate-500">
        Atajos: <span class="font-semibold">←</span> anterior · <span class="font-semibold">→</span> siguiente · <span class="font-semibold">ESC</span> volver
    </p>
</div>

<script>
    function carousel() {
        return {
            current: 0,
            items: @json($items),

            init() {
                if (!this.items.length) {
                    this.items = [{
                        label: 'Sin datos',
                        color: 'primary',
                        html: '<p class="text-sm text-slate-600">No hay registros para mostrar.</p>'
                    }];
                }
            },

            next() { this.current = (this.current + 1) % this.items.length; },
            prev() { this.current = (this.current - 1 + this.items.length) % this.items.length; },
            jump(i) { this.current = i; },

            goBack() {
                window.location.href = @json(route('usuarios.index'));
            },

            cardClass(index) {
                const offset = index - this.current;

                const n = this.items.length;
                let o = offset;
                if (o >  n/2) o -= n;
                if (o < -n/2) o += n;

                const base = "ring-slate-200";

                if (o === 0) {
                    return `${base} z-30 opacity-100 translate-x-0 scale-100`;
                } else if (o === -1) {
                    return `${base} z-20 opacity-100 ry-6`;
                } else if (o === 1) {
                    return `${base} z-20 opacity-100 ry--6`;
                } else {
                    return `${base} z-10 opacity-0 pointer-events-none translate-x-0 scale-75`;
                }
            }
        };
    }
</script>

</body>
</html>
