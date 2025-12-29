<?php

namespace App\Http\Controllers;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use App\Models\UserTrabajador; // ✅ modelo en mysql_trabajadores (tabla users)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TrabajadorController extends Controller
{
    public function edit($id)
    {
        $trabajador = TrabajadorPolifonia::on('mysql_polifonia')->findOrFail($id);

        // Si existe vínculo, lo llevamos al editor por UUID
        $vinculo = UsuarioVinculado::where('trabajador_id', $id)->first();
        if ($vinculo) {
            return redirect()->route('usuarios.edit.uuid', ['uuid' => $vinculo->uuid]);
        }

        return view('trabajadores.edit', compact('trabajador'));
    }

    public function update(Request $request, $id)
    {
        // 1) Traemos el trabajador en Polifonía (NO password aquí)
        $trabajador = TrabajadorPolifonia::on('mysql_polifonia')->findOrFail($id);

        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['nullable', 'string', 'min:12'],
            // Si tu form tiene password_confirmation, puedes activar esto:
            // 'password' => ['nullable','string','min:12','confirmed'],
        ]);

        $oldEmail = mb_strtolower(trim((string) $trabajador->email));
        $newEmail = mb_strtolower(trim((string) $request->email));

        // 2) Actualizamos email en Polifonía
        $trabajador->email = $newEmail;
        $trabajador->save();

        // 3) Si hay usuario en la BD de "trabajadores", lo actualizamos también
        //    (match por email viejo o por email nuevo si ya existía)
        $user = UserTrabajador::whereRaw('LOWER(email) = ?', [$oldEmail])->first()
            ?? UserTrabajador::whereRaw('LOWER(email) = ?', [$newEmail])->first();

        if ($user) {
            $user->email = $newEmail;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', $user
                ? 'Trabajador actualizado (Polifonía + Usuario).'
                : 'Trabajador actualizado en Polifonía (no existía usuario en la BD de trabajadores).'
            );
    }

    public function toggleActivo(Request $request, $id)
    {
        $t = TrabajadorPolifonia::on('mysql_polifonia')->findOrFail($id);
        $t->activo = ((int)($t->activo ?? 0) === 1) ? 0 : 1;
        $t->save();

        // Guarda la posición y vuelve a la misma página
        return back()
            ->with('success', 'Estado actualizado.')
            ->with('scroll_y', (int) $request->input('scroll_y', 0));
    }
}
