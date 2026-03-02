<?php

namespace App\Http\Controllers;

use App\Models\UserFichaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;

class UserFichajeController extends Controller
{
    public function create()
    {
        return view('fichajes.create_user');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email:rfc', 'max:255', 'unique:mysql_fichajes.users,email'],
            'work_mode' => ['required', 'in:office,intensive,campaign'],
            'password'  => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'El correo no tiene un formato válido.',
            'email.unique'       => 'Ya existe un usuario con ese correo en la base de datos de fichajes.',
            'work_mode.required' => 'El modo de trabajo es obligatorio.',
            'work_mode.in'       => 'El modo de trabajo seleccionado no es válido.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $user = UserFichaje::create([
            'name'      => $data['name'],
            'email'     => Str::lower($data['email']),
            'work_mode' => $data['work_mode'],
            'password'  => Hash::make($data['password']),
        ]);

        Log::info('Usuario de fichajes creado', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'by'      => auth()->id(),
        ]);

        return redirect()
            ->route('fichajes.users.create')
            ->with('success', "Usuario «{$user->name}» creado correctamente en la base de datos de fichajes.");
    }
}

