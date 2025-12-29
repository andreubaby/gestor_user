<?php

namespace App\Http\Controllers;

use App\Models\WorkerBuscador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WorkerBuscadorController extends Controller
{
    public function edit($id)
    {
        $trabajador = WorkerBuscador::on('mysql_buscador')->findOrFail($id);

        return view('buscador.edit_worker', compact('trabajador'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
        ]);

        $trabajador = WorkerBuscador::on('mysql_buscador')->findOrFail($id);

        $trabajador->name  = $request->name;
        $trabajador->email = $request->email;

        if ($request->filled('password')) {
            $trabajador->password = Hash::make($request->password);
        }

        $trabajador->save();

        return redirect()->route('usuarios.index')->with('success', 'Trabajador Buscador actualizado.');
    }
}

