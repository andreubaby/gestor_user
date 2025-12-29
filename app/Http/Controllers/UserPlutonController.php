<?php

namespace App\Http\Controllers;

use App\Models\UserPluton;
use App\Models\UsuarioVinculado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserPlutonController extends Controller
{
    public function update(Request $request, UserPluton $pluton)
    {
        $request->validate([
            'nombre'   => 'required|string|max:150',
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
            'imei'     => 'nullable|string|max:255',
        ]);

        $pluton->fill([
            'nombre' => $request->nombre,
            'email'  => $request->email,
            'imei'   => $request->imei,
        ]);

        if ($request->filled('password')) {
            $pluton->password = Hash::make($request->password);
        }

        $pluton->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario Plutón actualizado.');
    }

    public function edit($id)
    {
        $pluton = UserPluton::on('mysql_pluton')->findOrFail($id);
        $vinculo = UsuarioVinculado::where('pluton_id', $id)->first();

        if ($vinculo) {
            return redirect()->route('usuarios.edit.uuid', ['uuid' => $vinculo->uuid]);
        }

        return view('pluton.edit', compact('pluton'));
    }
}


