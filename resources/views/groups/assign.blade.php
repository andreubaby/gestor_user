<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar usuarios a grupos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>

    {{-- TomSelect --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <style>
        .ts-wrapper.multi .ts-control{
            border-radius: 0.75rem;
            padding: .5rem .75rem;
            border-color: rgb(226 232 240);
            box-shadow: 0 1px 2px rgba(2,6,23,.05);
        }
        .ts-wrapper.multi .ts-control:focus,
        .ts-wrapper.multi .ts-control:focus-within{
            border-color: rgb(52 211 153);
            box-shadow: 0 0 0 4px rgba(167, 243, 208, .7);
        }
        .ts-dropdown{
            border-radius: .75rem;
            border-color: rgb(226 232 240);
            box-shadow: 0 15px 30px rgba(2,6,23,.10);
            overflow: hidden;
        }
        .ts-dropdown .active{ background: rgba(16,185,129,.12); }
    </style>
</head>

<body class="min-h-screen bg-white text-slate-900">

<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

<header class="sticky top-0 z-40 border-b border-emerald-200 bg-white/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-3">
        <div class="flex items-center gap-3">
            <img src="{{ asset('img/babyplant.svg') }}"
                 alt="Babyplant"
                 class="h-14 w-14 rounded-2xl bg-white p-2 shadow ring-1 ring-emerald-200">
            <div class="leading-tight">
                <p class="text-xs font-semibold text-emerald-700">Gestor de Usuarios</p>
                <h1 class="text-lg font-semibold tracking-tight text-slate-900">
                    Asignar usuarios a grupos
                </h1>
            </div>
        </div>

        <nav class="flex flex-wrap items-center gap-2">
            <a href="{{ route('usuarios.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Listado
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

<form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

<main class="relative max-w-7xl mx-auto px-4 py-6 space-y-6">

    @if(session('ok'))
        <div class="rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 p-4 text-emerald-900 text-sm">
            ✅ {{ session('ok') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl bg-red-50 ring-1 ring-red-200 p-4 text-red-900 text-sm">
            <div class="font-semibold mb-2">Errores:</div>
            <ul class="list-disc pl-6 space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="bg-white/80 backdrop-blur rounded-2xl ring-1 ring-emerald-100 shadow p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-2xl font-semibold text-emerald-800 tracking-tight">Asignación masiva</h2>
                <p class="text-sm text-slate-600 mt-1">
                    Selecciona un grupo y añade varios usuarios de golpe.
                </p>
            </div>
        </div>

        {{-- Cambiar de grupo (recarga mostrando miembros) --}}
        <form method="GET" action="{{ route('groups.assign.create') }}" class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
            <div class="md:col-span-7">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Grupo</label>
                <select name="group_id"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                               focus:outline-none focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400">
                    <option value="">-- Selecciona un grupo --</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ (string)optional($selectedGroup)->id === (string)$g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-5 flex gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition
                               focus:outline-none focus:ring-4 focus:ring-emerald-200 shadow">
                    Ver miembros
                </button>

                <a href="{{ route('groups.assign.create') }}"
                   class="px-4 py-2.5 rounded-xl bg-white ring-1 ring-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition
                          focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                    Reset
                </a>
            </div>
        </form>

        {{-- Añadir masivo --}}
        <form method="POST" action="{{ route('groups.assign.store') }}" class="mt-6 space-y-4">
            @csrf

            <input type="hidden" name="group_id" value="{{ optional($selectedGroup)->id }}">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-12">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Usuarios (selección múltiple)
                    </label>

                    <select id="trabajador_ids"
                            name="trabajador_ids[]"
                            multiple
                            class="ts w-full">
                        @foreach($trabajadores as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->nombre }}{{ !empty($t->email) ? ' · '.$t->email : '' }} (ID {{ $t->id }})
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-slate-500 mt-2">
                        Tip: escribe para buscar por nombre/email.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="submit"
                        @if(!optional($selectedGroup)->id) disabled @endif
                        class="px-6 py-2.5 rounded-xl font-semibold shadow transition
                               focus:outline-none focus:ring-4
                               {{ optional($selectedGroup)->id
                                    ? 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-200'
                                    : 'bg-slate-100 text-slate-400 cursor-not-allowed' }}">
                    Añadir al grupo
                </button>
            </div>

            @if(!optional($selectedGroup)->id)
                <p class="text-xs text-amber-700 bg-amber-50 ring-1 ring-amber-200 rounded-xl p-3">
                    ⚠️ Selecciona primero un grupo para poder guardar.
                </p>
            @endif
        </form>
    </section>

    {{-- Miembros actuales --}}
    <section class="bg-white rounded-2xl ring-1 ring-emerald-100 shadow p-5">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">
                    Miembros actuales
                    @if($selectedGroup) <span class="text-emerald-700">· {{ $selectedGroup->name }}</span> @endif
                </h3>
                <p class="text-sm text-slate-600 mt-1">
                    @if($selectedGroup)
                        {{ $members->count() }} usuario(s) en este grupo.
                    @else
                        Elige un grupo para ver sus miembros.
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500">
                <tr class="border-b">
                    <th class="py-2 text-left">Nombre</th>
                    <th class="py-2 text-left">Email</th>
                    <th class="py-2 text-left">ID</th>
                    <th class="py-2 text-right">Acción</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @if(!$selectedGroup)
                    <tr>
                        <td colspan="4" class="py-4 text-slate-500">—</td>
                    </tr>
                @else
                    @forelse($members as $m)
                        <tr>
                            <td class="py-2 font-semibold text-slate-900">{{ $m->nombre }}</td>
                            <td class="py-2 text-slate-600">{{ $m->email ?? '—' }}</td>
                            <td class="py-2 text-slate-600">{{ $m->id }}</td>
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('groups.assign.detach') }}"
                                      onsubmit="return confirm('¿Quitar a este usuario del grupo?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="group_id" value="{{ $selectedGroup->id }}">
                                    <input type="hidden" name="trabajador_id" value="{{ $m->id }}">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-100 transition font-semibold
                                                   focus:outline-none focus:ring-4 focus:ring-red-200">
                                        Quitar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-slate-500">No hay miembros aún.</td>
                        </tr>
                    @endforelse
                @endif
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sel = document.getElementById('trabajador_ids');
        if (!sel) return;

        new TomSelect(sel, {
            plugins: ['remove_button', 'clear_button'],
            maxOptions: 5000,
            create: false,
            searchField: ['text'],
            placeholder: 'Escribe para buscar y selecciona varios…',

            closeAfterSelect: true,   // 🔹 cierra el desplegable al seleccionar
            hideSelected: true,       // 🔹 oculta ya seleccionados
            clearSearchOnBlur: false, // 🔹 no esperar al blur

            onItemAdd() {
                // 🔹 LIMPIA el texto del buscador tras seleccionar
                this.setTextboxValue('');
                this.refreshOptions(false);
            },

            render: {
                no_results: function(){
                    return `<div class="py-2 px-3 text-sm text-slate-500">Sin resultados</div>`;
                }
            }
        });
    });
</script>

</body>
</html>
