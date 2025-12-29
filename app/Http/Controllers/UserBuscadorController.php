<?php

namespace App\Http\Controllers;

use App\Models\UserBuscador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserBuscadorController extends Controller
{
    public function edit($id)
    {
        $usuario = UserBuscador::on('mysql_buscador')->findOrFail($id);

        return view('buscador.edit_user', compact('usuario'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
        ]);

        $usuario = UserBuscador::on('mysql_buscador')->findOrFail($id);

        $usuario->name  = $request->name;
        $usuario->email = $request->email;

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario Buscador actualizado.');
    }
}

