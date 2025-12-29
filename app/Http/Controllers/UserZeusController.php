<?php

namespace App\Http\Controllers;

use App\Models\UserZeus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserZeusController extends Controller
{
    public function edit($id)
    {
        $userZeus = UserZeus::on('mysql_zeus')->findOrFail($id);

        return view('zeus.edit_user', compact('userZeus'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
            'nombre'   => 'nullable|string|max:255',
        ]);

        $userZeus = UserZeus::findOrFail($id);
        $userZeus->email = $request->email;
        $userZeus->name = $request->nombre;

        if ($request->filled('password')) {
            $userZeus->password = Hash::make($request->password);
        }

        $userZeus->save();

        Log::info('Usuario actualizado', [
            'usuario_id' => $userZeus->id,
            'email'      => $userZeus->email,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }
}
