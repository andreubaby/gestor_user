<?php

namespace App\Http\Controllers;

use App\Models\UserCronos;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserCronosController extends Controller
{
    public function edit($id)
    {
        $userCronos = UserCronos::on('mysql_cronos')->findOrFail($id);

        return view('cronos.edit_user', compact('userCronos'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
            'nombre'   => 'nullable|string|max:255',
        ]);

        $userCronos = UserCronos::findOrFail($id);
        $userCronos->email = $request->email;
        $userCronos->name = $request->nombre;

        if ($request->filled('password')) {
            $userCronos->password = Hash::make($request->password);
        }

        $userCronos->save();

        Log::info('Usuario actualizado', [
            'usuario_id' => $userCronos->id,
            'email'      => $userCronos->email,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }
}
