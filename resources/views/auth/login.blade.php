<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Gestor de Usuarios Babyplant</title>

    <meta name="referrer" content="strict-origin-when-cross-origin">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bp: {
                            green: '#16a34a',
                            green2: '#22c55e',
                            mint: '#dcfce7'
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

<main class="relative mx-auto flex min-h-screen max-w-5xl items-center justify-center px-4 py-12">

    <div class="grid w-full grid-cols-1 overflow-hidden rounded-3xl border border-emerald-200 bg-white/80 shadow-xl backdrop-blur lg:grid-cols-2">

        {{-- Panel izquierdo (branding) --}}
        <section class="hidden lg:flex flex-col justify-between p-10 bg-gradient-to-br from-emerald-50 to-white">
            <div>
                <div class="flex items-center gap-5">
                    <img src="{{ asset('img/babyplant.svg') }}"
                         alt="Babyplant"
                         class="h-24 w-24 rounded-3xl bg-white p-4 shadow-xl ring-2 ring-emerald-300">

                    <div>
                        <p class="text-sm font-semibold text-emerald-700">Gestor de Usuarios</p>
                        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Babyplant</h1>
                    </div>
                </div>

                <p class="mt-6 text-slate-700 leading-relaxed">
                    Accede al panel de administración de usuarios.
                </p>
            </div>
        </section>

        {{-- Formulario --}}
        <section class="p-6 sm:p-10">
            <header class="mb-6">
                <h2 class="text-2xl font-semibold tracking-tight">Iniciar sesión</h2>
                <p class="mt-2 text-sm text-slate-600">Introduce tus credenciales para acceder.</p>
            </header>

            {{-- Success --}}
            @if(session('success'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status" aria-live="polite">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error genérico --}}
            @if($errors->any())
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" aria-live="assertive">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="loginForm"
                  method="POST"
                  action="{{ route('login') }}"
                  class="space-y-5"
                  autocomplete="on"
                  novalidate>
                @csrf

                {{-- Honeypot --}}
                <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off">

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email</label>
                    <input id="email"
                           name="email"
                           type="email"
                           inputmode="email"
                           autocomplete="username"
                           autocapitalize="none"
                           spellcheck="false"
                           required
                           value="{{ old('email') }}"
                           placeholder="correo@dominio.com"
                           class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400
                                  outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-200">
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Contraseña</label>

                    <div class="relative">
                        <input id="password"
                               name="password"
                               type="password"
                               autocomplete="current-password"
                               required
                               placeholder="••••••••••••"
                               class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 pr-12 text-slate-900 placeholder:text-slate-400
                                      outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-200">

                        <button type="button"
                                id="togglePassword"
                                class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-emerald-700 transition"
                                aria-label="Mostrar contraseña">
                            <span class="text-lg">👁️</span>
                        </button>
                    </div>
                </div>

                {{-- Remember --}}
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2">
                        {{-- ✅ Siempre manda remember: 0 o 1 --}}
                        <input type="hidden" name="remember" value="0">

                        <input
                            id="remember"
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-200"
                            {{-- ✅ Persistencia en validaciones fallidas (POST) --}}
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <span class="text-sm text-slate-700">Recuérdame</span>
                    </label>
                </div>

                {{-- Botón --}}
                <button id="submitBtn" type="submit"
                        class="w-full rounded-2xl bg-emerald-600 px-4 py-3 font-semibold text-white shadow
                               transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200
                               flex items-center justify-center gap-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Entrar
                </button>

                <p class="pt-1 text-center text-sm text-slate-600">
                    ¿No tienes cuenta?
                    <a href="{{ route('register') }}" class="font-semibold text-emerald-700 underline underline-offset-4 hover:text-emerald-800">
                        Regístrate
                    </a>
                </p>
            </form>

        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 1) Mostrar/ocultar password
        const btn = document.getElementById('togglePassword');
        const pwd = document.getElementById('password');

        btn?.addEventListener('click', () => {
            const isPwd = pwd.type === 'password';
            pwd.type = isPwd ? 'text' : 'password';
            btn.setAttribute('aria-label', isPwd ? 'Ocultar contraseña' : 'Mostrar contraseña');
            const icon = btn.querySelector('span');
            if (icon) icon.textContent = isPwd ? '🙈' : '👁️';
        });

        // 2) Evita doble submit accidental
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        form?.addEventListener('submit', () => {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-60','cursor-not-allowed');
        });

        // 3) Persistencia del checkbox "remember" (localStorage)
        const KEY = 'bp_remember';
        const remember = document.getElementById('remember');
        if (!remember) return;

        // Si el backend ya marcó old('remember'), lo respetamos.
        const hasOldRememberChecked = remember.checked;

        if (!hasOldRememberChecked) {
            const saved = localStorage.getItem(KEY);
            if (saved === '1') remember.checked = true;
            if (saved === '0') remember.checked = false;
        }

        remember.addEventListener('change', () => {
            localStorage.setItem(KEY, remember.checked ? '1' : '0');
        });
    });
</script>

</body>
</html>
