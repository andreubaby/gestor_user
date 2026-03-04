<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario Fichajes | Gestor de Usuarios Babyplant</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a',
                        secondary: '#15803d',
                        bp: { green: '#16a34a', green2: '#22c55e' }
                    },
                    boxShadow: { soft: '0 10px 25px rgba(2,6,23,.08)' }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-white text-slate-900">

@php
    $active = 'fichajes_create';
    $tabBase   = "inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-200";
    $tabIdle   = "text-slate-700 hover:bg-emerald-50 hover:text-emerald-800";
    $tabActive = "bg-emerald-600 text-white shadow";
@endphp

{{-- Fondo decorativo --}}
<div class="pointer-events-none fixed inset-0 overflow-hidden">
    <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-200/40 blur-3xl"></div>
    <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-green-200/30 blur-3xl"></div>
    <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-lime-200/20 blur-3xl"></div>
</div>

{{-- Header --}}
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
                    Editar usuario · Fichajes
                </h1>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex items-center gap-3">
            <div class="flex items-center gap-1 rounded-full border border-slate-200 bg-white px-1.5 py-1 shadow-sm">

                <a href="{{ route('usuarios.index') }}"
                   class="{{ $tabBase }} {{ $active === 'usuarios' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active === 'usuarios' ? 'bg-white/15' : 'bg-slate-100' }}">👤</span>
                    Listado
                </a>

                <a href="{{ route('fichajes.diarios.index') }}"
                   class="{{ $tabBase }} {{ $active === 'fichajes' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active === 'fichajes' ? 'bg-white/15' : 'bg-slate-100' }}">⏱️</span>
                    Fichajes
                </a>

                <a href="{{ route('usuarios.onboarding.create') }}"
                   class="{{ $tabBase }} {{ $active === 'onboarding' ? $tabActive : $tabIdle }}">
                    <span class="grid h-7 w-7 place-items-center rounded-full {{ $active === 'onboarding' ? 'bg-white/15' : 'bg-slate-100' }}">🧾</span>
                    Onboarding
                </a>

                {{-- Dropdown Más --}}
                <div class="relative group">
                    <button type="button"
                            class="{{ $tabBase }} {{ in_array($active, ['dashboard','rrhh','vincular','asignar','tacografo','fichajes_create']) ? $tabActive : $tabIdle }} inline-flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-full {{ in_array($active, ['dashboard','rrhh','vincular','asignar','tacografo','fichajes_create']) ? 'bg-white/15' : 'bg-slate-100' }}">⋯</span>
                        Más
                        <span class="grid h-5 w-5 place-items-center rounded-full bg-slate-100 text-slate-700 transition group-hover:bg-emerald-100 group-hover:text-emerald-800">
                            <svg class="h-3.5 w-3.5 transition-transform group-hover:rotate-180 group-focus-within:rotate-180"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </span>
                    </button>

                    <div class="absolute right-0 top-full h-2 w-56"></div>

                    <div class="absolute right-0 mt-2 hidden w-72 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5
                        group-hover:block group-focus-within:block">
                        <div class="p-2">
                            <a href="{{ route('gestor.gestoria') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition text-slate-700 hover:bg-emerald-50 hover:text-emerald-900">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100">🏠</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Dashboard</div>
                                    <div class="text-xs text-slate-500">Vista general</div>
                                </div>
                            </a>
                            <div class="my-2 h-px bg-slate-100"></div>
                            <a href="{{ route('rrhh.documentos.index') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition text-slate-700 hover:bg-emerald-50 hover:text-emerald-900">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-sky-50 ring-1 ring-sky-100">📁</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">RRHH</div>
                                    <div class="text-xs text-slate-500">Documentos</div>
                                </div>
                            </a>
                            <a href="{{ route('usuarios.vincular') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition text-slate-700 hover:bg-emerald-50 hover:text-emerald-900">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100">🔗</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Vincular</div>
                                    <div class="text-xs text-slate-500">Unificar cuentas</div>
                                </div>
                            </a>
                            <a href="{{ route('groups.assign.create') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition text-slate-700 hover:bg-emerald-50 hover:text-emerald-900">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-violet-50 ring-1 ring-violet-100">👥</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Asignar grupo</div>
                                    <div class="text-xs text-slate-500">Gestión de grupos</div>
                                </div>
                            </a>
                            <a href="{{ route('tacografo.index') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition text-slate-700 hover:bg-emerald-50 hover:text-emerald-900">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-amber-50 ring-1 ring-amber-100">🚚</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Tacógrafo</div>
                                    <div class="text-xs text-slate-500">Camión / Camionero</div>
                                </div>
                            </a>

                            {{-- Nuevo usuario fichajes --}}
                            <a href="{{ route('fichajes.users.create') }}"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition
                                      {{ $active === 'fichajes_create' ? 'bg-emerald-50 text-emerald-900' : 'text-slate-700 hover:bg-emerald-50 hover:text-emerald-900' }}">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-teal-50 ring-1 ring-teal-100">➕</span>
                                <div class="leading-tight">
                                    <div class="font-semibold">Nuevo usuario</div>
                                    <div class="text-xs text-slate-500">BD de fichajes</div>
                                </div>
                            </a>

                            <div class="my-2 h-px bg-slate-100"></div>
                            <a href="#"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                               class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 transition">
                                <span class="grid h-8 w-8 place-items-center rounded-2xl bg-red-50 ring-1 ring-red-100">⎋</span>
                                Cerrar sesión
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>

{{-- Contenido principal --}}
<main class="relative z-10 max-w-2xl mx-auto px-4 py-10">

    {{-- Breadcrumb --}}
    <nav class="mb-6 flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('fichajes.diarios.index') }}" class="hover:text-emerald-700 transition">Fichajes</a>
        <span>/</span>
        <a href="{{ route('fichajes.users.create') }}" class="hover:text-emerald-700 transition">Nuevo usuario</a>
        <span>/</span>
        <span class="font-medium text-slate-800">Editar · {{ $user->name }}</span>
    </nav>

    {{-- Alerta de éxito --}}
    @if(session('success'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
            <span class="mt-0.5 text-emerald-600 text-lg">✅</span>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Alerta de errores --}}
    @if($errors->any())
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
            <span class="mt-0.5 text-red-500 text-lg">⚠️</span>
            <ul class="text-sm font-medium text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tarjeta del formulario --}}
    <div class="rounded-3xl border border-slate-200 bg-white shadow-soft overflow-hidden">

        {{-- Cabecera tarjeta --}}
        <div class="bg-gradient-to-r from-emerald-50 to-white border-b border-slate-100 px-8 py-6">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-100 text-2xl ring-1 ring-emerald-200">✏️</span>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Editar usuario de fichajes</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            Modifica los datos de <span class="font-semibold text-slate-700">{{ $user->name }}</span>
                        </p>
                    </div>
                </div>
                {{-- Badge ID --}}
                <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-mono text-slate-500">
                    ID #{{ $user->id }}
                </span>
            </div>
        </div>

        {{-- Formulario --}}
        <form action="{{ route('fichajes.users.update', $user->id) }}" method="POST" class="px-8 py-8 space-y-6">
            @csrf
            @method('PUT')

            {{-- Nombre --}}
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Nombre completo <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $user->name) }}"
                       placeholder="Ej: María García López"
                       autocomplete="name"
                       required
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                              placeholder:text-slate-400 transition
                              focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100
                              @error('name') border-red-400 bg-red-50 focus:ring-red-100 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Correo electrónico <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email', $user->email) }}"
                       placeholder="usuario@ejemplo.com"
                       autocomplete="email"
                       required
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                              placeholder:text-slate-400 transition
                              focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100
                              @error('email') border-red-400 bg-red-50 focus:ring-red-100 @enderror">
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Modo de trabajo --}}
            <div>
                <label for="work_mode" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Modo de trabajo <span class="text-red-500">*</span>
                </label>
                <select id="work_mode"
                        name="work_mode"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition
                               focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100
                               @error('work_mode') border-red-400 bg-red-50 focus:ring-red-100 @enderror">
                    <option value="" disabled>Selecciona un modo…</option>
                    <option value="office"    {{ old('work_mode', $user->work_mode) === 'office'    ? 'selected' : '' }}>🏢 Office (oficina)</option>
                    <option value="intensive" {{ old('work_mode', $user->work_mode) === 'intensive' ? 'selected' : '' }}>⚡ Intensive (jornada intensiva)</option>
                    <option value="campaign"  {{ old('work_mode', $user->work_mode) === 'campaign'  ? 'selected' : '' }}>🌿 Campaign (campaña)</option>
                </select>
                @error('work_mode')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Separador --}}
            <div class="h-px bg-slate-100"></div>

            {{-- Cambio de contraseña (opcional) --}}
            <div>
                <div class="mb-4 flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-700">Cambiar contraseña</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">opcional</span>
                </div>
                <p class="mb-4 text-xs text-slate-500">Deja los campos en blanco si no quieres cambiar la contraseña.</p>

                {{-- Nueva contraseña --}}
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Nueva contraseña
                        </label>
                        <div class="relative">
                            <input type="password"
                                   id="password"
                                   name="password"
                                   placeholder="Mínimo 8 caracteres"
                                   autocomplete="new-password"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 pr-12 text-sm text-slate-900 shadow-sm
                                          placeholder:text-slate-400 transition
                                          focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100
                                          @error('password') border-red-400 bg-red-50 focus:ring-red-100 @enderror">
                            <button type="button"
                                    onclick="togglePassword('password', this)"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                                    title="Mostrar / ocultar contraseña">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                             -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirmar nueva contraseña --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Confirmar nueva contraseña
                        </label>
                        <div class="relative">
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   placeholder="Repite la nueva contraseña"
                                   autocomplete="new-password"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 pr-12 text-sm text-slate-900 shadow-sm
                                          placeholder:text-slate-400 transition
                                          focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                            <button type="button"
                                    onclick="togglePassword('password_confirmation', this)"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                                    title="Mostrar / ocultar contraseña">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                             -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Separador --}}
            <div class="h-px bg-slate-100"></div>

            {{-- Acciones --}}
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('fichajes.users.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm
                          hover:bg-slate-50 transition">
                    ← Volver
                </a>

                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow
                               hover:bg-emerald-700 active:scale-95 transition focus:outline-none focus:ring-4 focus:ring-emerald-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    function togglePassword(fieldId, btn) {
        const input = document.getElementById(fieldId);
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.title = isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña';
        btn.classList.toggle('text-emerald-600', isHidden);
        btn.classList.toggle('text-slate-400', !isHidden);
    }
</script>

</body>
</html>

