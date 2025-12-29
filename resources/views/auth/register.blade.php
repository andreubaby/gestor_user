<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Crear cuenta | Gestor de Usuarios Babyplant</title>

    {{-- Si ya usas Vite, puedes dejarlo, pero como estás usando CDN, con esto te basta --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine (si ya lo tienes en app.js, quita este CDN) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bp: {
                            green: '#16a34a',
                            green2: '#22c55e',
                            dark: '#07120b'
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

<main class="relative mx-auto flex min-h-screen max-w-3xl items-center justify-center px-4 py-12">

    <section class="w-full rounded-3xl border border-emerald-200 bg-white/80 p-6 sm:p-10 shadow-xl backdrop-blur"
             x-data="{ showPass:false, showPass2:false, submitting:false }">

        {{-- Header + branding (todo dentro de la misma sección) --}}
        <header class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 shadow"></div>
                <div>
                    <p class="text-sm text-emerald-700 font-medium">Gestor de Usuarios</p>
                    <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900">Babyplant</h1>
                </div>
            </div>

        </header>

        <p class="mt-5 text-sm text-slate-600">
            Crea tu cuenta para acceder al panel.
        </p>

        {{-- Errores --}}
        @if ($errors->any())
            <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm" role="alert" aria-live="assertive">
                <p class="font-semibold text-red-800">Revisa lo siguiente:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success --}}
        @if (session('success'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status" aria-live="polite">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}"
              class="mt-8 space-y-5"
              autocomplete="on"
              novalidate
              @submit="submitting=true">
            @csrf

            {{-- Honeypot anti-bot (backend: si viene lleno => abort(422)) --}}
            <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off">

            {{-- Nombre --}}
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-slate-800">Nombre</label>
                <input id="name" name="name" type="text"
                       value="{{ old('name') }}"
                       required autofocus autocomplete="name"
                       class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400
                              outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-200"
                       placeholder="Tu nombre completo" />
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email</label>
                <input id="email" name="email" type="email"
                       inputmode="email" autocapitalize="none" spellcheck="false"
                       value="{{ old('email') }}"
                       required autocomplete="username"
                       class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400
                              outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-200"
                       placeholder="correo@dominio.com" />
                @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Contraseña</label>

                <div class="relative">
                    <input id="password" name="password"
                           :type="showPass ? 'text' : 'password'"
                           required autocomplete="new-password"
                           minlength="12"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-900 placeholder:text-slate-400
                                  outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-200"
                           placeholder="••••••••••••" />

                    <button type="button"
                            class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-emerald-700 transition"
                            @click="showPass=!showPass"
                            :aria-label="showPass ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                        <span class="text-lg" x-text="showPass ? '🙈' : '👁️'"></span>
                    </button>
                </div>

                <p class="mt-2 text-xs text-slate-500">
                    Mínimo 12 caracteres. Evita contraseñas comunes (el sistema las bloqueará).
                </p>

                @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Confirmar Password --}}
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-800">Repite la contraseña</label>

                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation"
                           :type="showPass2 ? 'text' : 'password'"
                           required autocomplete="new-password"
                           minlength="12"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-900 placeholder:text-slate-400
                                  outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-200"
                           placeholder="••••••••••••" />

                    <button type="button"
                            class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-emerald-700 transition"
                            @click="showPass2=!showPass2"
                            :aria-label="showPass2 ? 'Ocultar confirmación' : 'Mostrar confirmación'">
                        <span class="text-lg" x-text="showPass2 ? '🙈' : '👁️'"></span>
                    </button>
                </div>

                @error('password_confirmation') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Botón --}}
            <button type="submit"
                    :disabled="submitting"
                    class="w-full rounded-2xl bg-emerald-600 px-4 py-3 font-semibold text-white shadow
                           transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200
                           disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-text="submitting ? 'Creando cuenta...' : 'Crear cuenta'"></span>
            </button>

            <div class="pt-2 text-center text-sm text-slate-600">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-emerald-700 underline underline-offset-4 hover:text-emerald-800">
                    Inicia sesión
                </a>
            </div>

        </form>
    </section>
</main>

</body>
</html>
