<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario | Gestor de Usuarios Babyplant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Instrument Sans"', 'ui-sans-serif']
                    },
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
        .glass { background: rgba(255,255,255,.80); backdrop-filter: blur(10px); }
    </style>
</head>

<body class="min-h-screen bg-white text-slate-900 font-sans">

<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

{{-- ✅ Navbar estilo Babyplant (igual que tu segunda plantilla) --}}
<header class="sticky top-0 z-50 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">

        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">
                    Editar registros
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
    use App\Models\UsuarioVinculado;

    $tipo = isset($usuario)
        ? 'usuario'
        : (isset($trabajador)
            ? 'trabajador'
            : (isset($usuarioPluton)
                ? 'pluton'
                : null));

    $id = $usuario->id ?? $trabajador->id ?? $usuarioPluton->id ?? null;

    $vinculo = $tipo
        ? UsuarioVinculado::where("{$tipo}_id", $id)->first()
        : null;
@endphp

<div class="relative max-w-7xl mx-auto px-4 py-6">

    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h2 class="text-2xl md:text-3xl font-semibold text-emerald-800 tracking-tight">
                Editar Registros
            </h2>
            <p class="text-sm text-slate-600 mt-2">
                Modifica los registros asociados en cada aplicación. Los formularios se muestran por módulos.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if($vinculo)
                <a href="{{ route('usuarios.vincular.uuid', ['uuid' => $vinculo->uuid]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 text-white font-semibold shadow
                          hover:bg-amber-600 transition focus:outline-none focus:ring-4 focus:ring-amber-200">
                    🔗 Editar Vinculación
                </a>
            @else
                @if(isset($trabajador))
                    <a href="{{ route('usuarios.vincular', ['trabajador_id' => $trabajador->id]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow
                              hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200">
                        ➕ Crear Vinculación
                    </a>
                @else
                    <a href="{{ route('usuarios.vincular') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow
                              hover:bg-emerald-700 transition focus:outline-none focus:ring-4 focus:ring-emerald-200">
                        ➕ Crear Vinculación
                    </a>
                @endif
            @endif

            <a href="{{ route('usuarios.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-800 font-semibold
                      hover:bg-slate-200 transition focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                ← Volver
            </a>
        </div>
    </div>

    {{-- Grid de módulos (tu estructura original) --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        @if(isset($usuario))
            <section class="bg-white/80 glass rounded-2xl shadow-soft ring-1 ring-emerald-100 p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-500">Módulo</div>
                        <h3 class="text-xl font-semibold tracking-tight text-primary">Aplicación Principal</h3>
                    </div>
                    <span class="h-2.5 w-2.5 rounded-full bg-primary mt-2"></span>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    @include('partials.form_usuario', ['usuario' => $usuario])
                </div>
            </section>
        @endif

        @if(isset($trabajador))
            <section class="bg-white/80 glass rounded-2xl shadow-soft ring-1 ring-emerald-100 p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-500">Módulo</div>
                        <h3 class="text-xl font-semibold tracking-tight text-polifonia">Aplicación Polifonía</h3>
                    </div>
                    <span class="h-2.5 w-2.5 rounded-full bg-polifonia mt-2"></span>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    @include('partials.form_trabajador', ['trabajador' => $trabajador])
                </div>
            </section>
        @endif

        @if(isset($usuarioPluton))
            <section class="bg-white/80 glass rounded-2xl shadow-soft ring-1 ring-emerald-100 p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-500">Módulo</div>
                        <h3 class="text-xl font-semibold tracking-tight text-pluton">Aplicación Plutón</h3>
                    </div>
                    <span class="h-2.5 w-2.5 rounded-full bg-pluton mt-2"></span>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    @include('partials.form_pluton', ['usuarioPluton' => $usuarioPluton])
                </div>
            </section>
        @endif

        @if(isset($usuarioBuscador))
            <section class="bg-white/80 glass rounded-2xl shadow-soft ring-1 ring-emerald-100 p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-500">Módulo</div>
                        <h3 class="text-xl font-semibold tracking-tight text-buscador">Usuario Buscador</h3>
                    </div>
                    <span class="h-2.5 w-2.5 rounded-full bg-buscador mt-2"></span>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    @include('partials.form_user_buscador', ['usuarioBuscador' => $usuarioBuscador])
                </div>
            </section>
        @endif

        @if(isset($trabajadorBuscador))
            <section class="bg-white/80 glass rounded-2xl shadow-soft ring-1 ring-emerald-100 p-6">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-semibold text-slate-500">Módulo</div>
                        <h3 class="text-xl font-semibold tracking-tight text-buscador2">Trabajador Buscador</h3>
                    </div>
                    <span class="h-2.5 w-2.5 rounded-full bg-buscador2 mt-2"></span>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    @include('partials.form_worker_buscador', ['trabajadorBuscador' => $trabajadorBuscador])
                </div>
            </section>
        @endif

        {{-- Si en el futuro añades más (cronos, semillas, store, zeus, fichajes), puedes copiar el mismo patrón --}}
    </div>


</div>

</body>
</html>
