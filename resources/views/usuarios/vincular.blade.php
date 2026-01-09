<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vincular Usuarios | Gestor de Usuarios Babyplant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a',   // Babyplant
                        secondary: '#15803d',
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

    {{-- TomSelect --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <style>
        /* Ajuste visual TomSelect para que encaje con Tailwind */
        .ts-wrapper.single .ts-control{
            border-radius: 0.75rem; /* rounded-xl */
            padding: .625rem 1rem;  /* similar a py-2.5 px-4 */
            border-color: rgb(226 232 240); /* slate-200 */
            box-shadow: 0 1px 2px rgba(2,6,23,.05);
        }
        .ts-wrapper.single .ts-control:focus,
        .ts-wrapper.single .ts-control:focus-within{
            border-color: rgb(52 211 153); /* emerald-400 */
            box-shadow: 0 0 0 4px rgba(167, 243, 208, .7); /* emerald-200 */
        }
        .ts-dropdown{
            border-radius: .75rem;
            border-color: rgb(226 232 240);
            box-shadow: 0 15px 30px rgba(2,6,23,.10);
            overflow: hidden;
        }
        .ts-dropdown .active{
            background: rgba(16,185,129,.12);
        }
    </style>
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
                    Vincular
                </h1>
            </div>
        </div>

        {{-- Navegación (solo lo que existe) --}}
        <nav class="flex flex-wrap items-center gap-2">
            <a href="{{ route('usuarios.index') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 transition
                      focus:outline-none focus:ring-4 focus:ring-emerald-200">
                Listado
            </a>

            <a href="{{ route('usuarios.vincular') }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200
                      hover:bg-emerald-100 transition focus:outline-none focus:ring-4 focus:ring-emerald-200">
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

<form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

@php
    use Illuminate\Support\Str;

    $vinculo = $vinculo ?? null;

    $uuidActual = old('uuid')
        ?? ($uuid ?? optional($vinculo)->uuid ?? (string) Str::uuid());

    $val = function(string $key, $fallback = null) {
        return old($key)
            ?? session($key.'_preseleccionado')
            ?? $fallback;
    };

    $usuarioSeleccionadoId      = $val('usuario_id', optional($vinculo)->usuario_id);
    $trabajadorSeleccionadoId   = $val('trabajador_id', optional($vinculo)->trabajador_id);
    $plutonSeleccionadoId       = $val('pluton_id', optional($vinculo)->pluton_id);

    $userBuscadorSeleccionado   = $val('user_buscador_id', optional($vinculo)->user_buscador_id);
    $workerBuscadorSeleccionado = $val('worker_buscador_id', optional($vinculo)->worker_buscador_id);

    $userCronosSeleccionado   = $val('user_cronos_id', optional($vinculo)->user_cronos_id);
    $userSemillasSeleccionado = $val('user_semillas_id', optional($vinculo)->user_semillas_id);
    $userStoreSeleccionado    = $val('user_store_id', optional($vinculo)->user_store_id);
    $userZeusSeleccionado     = $val('user_zeus_id', optional($vinculo)->user_zeus_id);

    // ✅ NUEVO: Fichajes
    $userFichajeSeleccionado  = $val('user_fichaje_id', optional($vinculo)->user_fichaje_id);

    $emailSugerido = old('email_search')
    ?? request('email')
    ?? session('email_preseleccionado')
    ?? ($emailSugerido ?? null);

    $action = $vinculo
        ? route('usuarios.vincular.update', $vinculo->id)
        : route('usuarios.vincular.store');

    $method = $vinculo ? 'PUT' : 'POST';
@endphp

<main class="relative max-w-6xl mx-auto space-y-5 px-4 pb-10">

    <section class="bg-white/80 backdrop-blur rounded-2xl ring-1 ring-emerald-100 shadow-soft p-5 md:p-7">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-2xl md:text-3xl font-semibold text-emerald-800 tracking-tight">
                    {{ $vinculo ? 'Editar vinculación' : 'Vincular registros manualmente' }}
                </h2>
                <p class="text-sm text-slate-600 mt-2">
                    Selecciona el equivalente en cada app. Puedes buscar dentro de cada selector.
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="clearAll()"
                        class="px-4 py-2 rounded-xl bg-slate-100 text-slate-800 hover:bg-slate-200 transition text-sm font-semibold
                               focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                    Limpiar
                </button>
            </div>
        </div>

        @if($errors->any())
            <div class="mt-5 p-3 rounded-xl bg-red-50 ring-1 ring-red-200 text-red-800 text-sm">
                <div class="font-semibold mb-1">Revisa los errores:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ $action }}" method="POST" class="mt-6 space-y-6">
            @csrf
            @if($vinculo) @method($method) @endif

            <input type="hidden" name="uuid" value="{{ $uuidActual }}">

            {{-- Buscador por email --}}
            <div class="rounded-2xl ring-1 ring-emerald-100 bg-white p-4 md:p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div>
                        <div class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-800 bg-emerald-50 ring-1 ring-emerald-200 rounded-full px-3 py-1">
                            ✉️ Autofill por email
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Si coincide, autoselecciona en todas las listas.</p>
                    </div>

                    @if($emailSugerido)
                        <div class="text-xs text-amber-900 bg-amber-50 ring-1 ring-amber-200 rounded-xl px-3 py-2">
                            Sugerencia: <span class="font-semibold">{{ $emailSugerido }}</span>
                            <button type="button"
                                    class="ml-2 underline font-semibold hover:text-amber-700"
                                    onclick="autofillByEmail(@json($emailSugerido))">
                                Usar
                            </button>
                        </div>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-7">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Buscar por email (opcional)</label>
                        <input id="emailSearch"
                               name="email_search"
                               type="email"
                               inputmode="email"
                               autocomplete="off"
                               value="{{ $emailSugerido ?? '' }}"
                               placeholder="ej: usuario@dominio.com"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl shadow-sm bg-white
                                focus:outline-none focus:ring-4 focus:ring-emerald-200 focus:border-emerald-400">
                    </div>

                    <div class="md:col-span-5 flex gap-2">
                        <button type="button"
                                onclick="autofillByEmail(document.getElementById('emailSearch').value)"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition
                                       focus:outline-none focus:ring-4 focus:ring-emerald-200 shadow">
                            Autoseleccionar
                        </button>

                        <button type="button"
                                onclick="document.getElementById('emailSearch').value='';"
                                class="px-4 py-2.5 rounded-xl bg-white ring-1 ring-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition
                                       focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                            Vaciar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Cards por sección --}}
            <div class="grid grid-cols-1 gap-5">

                {{-- App Base --}}
                <section class="bg-white rounded-2xl ring-1 ring-emerald-100 p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <h3 class="text-sm font-semibold text-slate-900">App Base</h3>
                        <span class="text-xs text-slate-500">Recomendado (principal)</span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select-field name="usuario_id"
                                        label="Usuario Principal (App Base)"
                                        placeholder="-- Selecciona un usuario --"
                                        class="ts">
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}"
                                        {{ (string)$usuarioSeleccionadoId === (string)$u->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($u->email ?? '')) }}">
                                    {{ $u->nombre }} - {{ $u->email }}
                                </option>
                            @endforeach
                        </x-select-field>
                    </div>
                </section>

                {{-- Polifonía --}}
                <section class="bg-white rounded-2xl ring-1 ring-emerald-100 p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Polifonía</h3>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-select-field name="trabajador_id"
                                        label="Trabajador (Polifonía)"
                                        placeholder="-- Selecciona un trabajador --"
                                        class="ts">
                            @foreach($trabajadores as $t)
                                <option value="{{ $t->id }}"
                                        {{ (string)$trabajadorSeleccionadoId === (string)$t->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($t->email ?? '')) }}">
                                    {{ $t->nombre }} - {{ $t->email }}
                                </option>
                            @endforeach
                        </x-select-field>
                    </div>
                </section>

                {{-- Apps satélite --}}
                <section class="bg-white rounded-2xl ring-1 ring-emerald-100 p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <h3 class="text-sm font-semibold text-slate-900">Apps satélite</h3>
                        <span class="text-xs text-slate-500">Opcional</span>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">

                        <x-select-field name="pluton_id" label="Usuario (Plutón)" placeholder="-- Selecciona un usuario plutón --" class="ts">
                            @foreach($usuariosPluton as $p)
                                <option value="{{ $p->id }}"
                                        {{ (string)$plutonSeleccionadoId === (string)$p->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($p->email ?? '')) }}">
                                    {{ $p->nombre }} - {{ $p->email }} - IMEI: {{ $p->imei }}
                                </option>
                            @endforeach
                        </x-select-field>

                        <x-select-field name="user_buscador_id" label="Usuario (Buscador)" placeholder="-- Selecciona un usuario buscador --" class="ts">
                            @foreach($usuariosBuscador as $ub)
                                <option value="{{ $ub->id }}"
                                        {{ (string)$userBuscadorSeleccionado === (string)$ub->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($ub->email ?? '')) }}">
                                    {{ $ub->name }} - {{ $ub->email }}
                                </option>
                            @endforeach
                        </x-select-field>

                        <x-select-field name="worker_buscador_id" label="Trabajador (Buscador)" placeholder="-- Selecciona un trabajador buscador --" class="ts">
                            @foreach($trabajadoresBuscador as $wb)
                                <option value="{{ $wb->id }}"
                                        {{ (string)$workerBuscadorSeleccionado === (string)$wb->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($wb->email ?? '')) }}">
                                    {{ $wb->name }} - {{ $wb->email }}
                                </option>
                            @endforeach
                        </x-select-field>

                        <x-select-field name="user_cronos_id" label="Usuario (Cronos)" placeholder="-- Selecciona un usuario cronos --" class="ts">
                            @foreach($userCronos as $uc)
                                <option value="{{ $uc->id }}"
                                        {{ (string)$userCronosSeleccionado === (string)$uc->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($uc->email ?? '')) }}">
                                    {{ $uc->name }} - {{ $uc->email }}
                                </option>
                            @endforeach
                        </x-select-field>

                        <x-select-field name="user_semillas_id" label="Usuario (Semillas)" placeholder="-- Selecciona un usuario semillas --" class="ts">
                            @foreach($userSemillas as $us)
                                <option value="{{ $us->id }}"
                                        {{ (string)$userSemillasSeleccionado === (string)$us->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($us->email ?? '')) }}">
                                    {{ $us->name }} - {{ $us->email }}
                                </option>
                            @endforeach
                        </x-select-field>

                        <x-select-field name="user_store_id" label="Usuario (Store)" placeholder="-- Selecciona un usuario store --" class="ts">
                            @foreach($userStore as $ust)
                                <option value="{{ $ust->id }}"
                                        {{ (string)$userStoreSeleccionado === (string)$ust->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($ust->email ?? '')) }}">
                                    {{ $ust->name }} - {{ $ust->email }}
                                </option>
                            @endforeach
                        </x-select-field>

                        <x-select-field name="user_zeus_id" label="Usuario (Zeus)" placeholder="-- Selecciona un usuario zeus --" class="ts">
                            @foreach($userZeus as $uz)
                                <option value="{{ $uz->id }}"
                                        {{ (string)$userZeusSeleccionado === (string)$uz->id ? 'selected' : '' }}
                                        data-email="{{ mb_strtolower(trim($uz->email ?? '')) }}">
                                    {{ $uz->name }} - {{ $uz->email }}
                                </option>
                            @endforeach
                        </x-select-field>

                        {{-- ✅ NUEVO: Fichajes --}}
                        @if(!empty($usuariosFichajes))
                            <x-select-field name="user_fichaje_id"
                                            label="Usuario (Fichajes)"
                                            placeholder="-- Selecciona un usuario fichajes --"
                                            class="ts">
                                @foreach($usuariosFichajes as $uf)
                                    <option value="{{ $uf->id }}"
                                            {{ (string)$userFichajeSeleccionado === (string)$uf->id ? 'selected' : '' }}
                                            data-email="{{ mb_strtolower(trim($uf->email ?? '')) }}">
                                        {{ $uf->name }} - {{ $uf->email }} - {{ $uf->work_mode ?? '—' }}
                                    </option>
                                @endforeach
                            </x-select-field>
                        @endif

                    </div>
                </section>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="{{ route('usuarios.vincular') }}"
                   class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-800 hover:bg-slate-200 transition font-semibold
                          focus:outline-none focus:ring-4 focus:ring-slate-300/40">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition font-semibold shadow
                               focus:outline-none focus:ring-4 focus:ring-emerald-200">
                    {{ $vinculo ? 'Actualizar vinculación' : 'Guardar vinculación' }}
                </button>
            </div>
        </form>
    </section>
</main>

<script>
    const TS = new Map();

    function initTomSelect(){
        document.querySelectorAll('select.ts').forEach(sel => {
            // Asegura que tenga id para mapear (por si el componente no lo pone)
            if (!sel.id) sel.id = sel.name;

            if (TS.has(sel.id)) return;

            const t = new TomSelect(sel, {
                plugins: ['clear_button'],
                allowEmptyOption: true,
                create: false,
                maxOptions: 5000,
                searchField: ['text'],
                placeholder: sel.querySelector('option[value=""]')?.textContent || 'Selecciona...',
                render: {
                    no_results: function(){
                        return `<div class="py-2 px-3 text-sm text-slate-500">Sin resultados</div>`;
                    }
                }
            });

            TS.set(sel.id, t);
        });
    }

    function clearAll(){
        const email = document.getElementById('emailSearch');
        if (email) email.value = '';

        TS.forEach(t => t.clear(true));

        // fallback por si algo no inicializó
        document.querySelectorAll('select').forEach(s => s.value = '');
    }

    // ✅ Busca el option por data-email iterando (sin cssEscape)
    function autofillByEmail(email){
        email = (email || '').trim().toLowerCase();
        if (!email) return;

        document.querySelectorAll('select').forEach(sel => {
            let foundValue = null;

            for (const opt of sel.options){
                const de = (opt.getAttribute('data-email') || '').trim().toLowerCase();
                if (de && de === email){
                    foundValue = opt.value;
                    break;
                }
            }

            if (!foundValue) return;

            const key = sel.id || sel.name;
            const ts = TS.get(key);
            if (ts) ts.setValue(foundValue, true);
            else sel.value = foundValue;
        });
    }

    document.addEventListener('DOMContentLoaded', initTomSelect);
</script>

</body>
</html>
