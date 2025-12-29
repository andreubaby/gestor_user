<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Mostrar formulario de login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        if ($request->filled('website')) {
            abort(422);
        }

        $email = Str::lower($request->input('email'));
        $key = $this->throttleKey($request, $email);

        // 5 intentos / 1 min (ajústalo)
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('Login bloqueado por rate limit', [
                'email' => $email,
                'ip' => $request->ip(),
                'seconds' => $seconds,
            ]);

            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Prueba en {$seconds} segundos.",
            ]);
        }

        // Importante: mensajes genéricos, no confirmar si el email existe
        $credentials = ['email' => $email, 'password' => $request->input('password')];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            Log::warning('Login fallido', [
                'email' => $email,
                'ip' => $request->ip(),
                'ua' => substr((string) $request->userAgent(), 0, 200),
            ]);

            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas.',
            ]);
        }

        // Éxito
        RateLimiter::clear($key);
        $request->session()->regenerate();

        Log::info('Login exitoso', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('gestor.gestoria'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(Request $request, string $email): string
    {
        // email + ip = evita bloquear a todos por un email
        return 'login:'.Str::transliterate($email).'|'.$request->ip();
    }

    // Mostrar formulario de registro
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // Procesar registro
    public function register(Request $request)
    {
        if ($request->filled('website')) {
            abort(422);
        }

        $data = $request->validate([
            'name'  => ['required','string','max:255'],
            'email' => ['required','string','email:rfc,dns','max:255','unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::defaults()->uncompromised(), // fuerte + comprueba filtraciones (HaveIBeenPwned)
            ],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => Str::lower($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        Log::info('Usuario registrado', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Registro exitoso. Ahora puedes iniciar sesión.');
    }
}
