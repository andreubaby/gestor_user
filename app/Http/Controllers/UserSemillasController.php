<?php

namespace App\Http\Controllers;

use App\Models\UserSemillas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSemillasController extends Controller
{
    public function edit($id)
    {
        $userSemilla = UserSemillas::on('mysql_semillas')->findOrFail($id);

        return view('semillas.edit_user', compact('userSemilla'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
            'nombre'   => 'nullable|string|max:255',
        ]);

        $userSemilla = UserSemillas::findOrFail($id);
        $userSemilla->email = $request->email;
        $userSemilla->name = $request->nombre;

        if ($request->filled('password')) {
            $userSemilla->password = Hash::make($request->password);
        }

        $userSemilla->save();

        Log::info('Usuario actualizado', [
            'usuario_id' => $userSemilla->id,
            'email'      => $userSemilla->email,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }
}
