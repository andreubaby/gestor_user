<?php

namespace App\Http\Controllers;

use App\Models\UserStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserStoreController extends Controller
{
    public function edit($id)
    {
        $userStore = UserStore::on('mysql_store')->findOrFail($id);

        return view('store.edit_user', compact('userStore'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'nullable|min:4',
            'nombre'   => 'nullable|string|max:255',
        ]);

        $userStore = UserStore::findOrFail($id);
        $userStore->email = $request->email;
        $userStore->name = $request->nombre;

        if ($request->filled('password')) {
            $userStore->password = Hash::make($request->password);
        }

        $userStore->save();

        Log::info('Usuario actualizado', [
            'usuario_id' => $userStore->id,
            'email'      => $userStore->email,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }
}
