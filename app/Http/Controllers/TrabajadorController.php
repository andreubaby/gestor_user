<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Fichar;
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
            'nombre'   => ['nullable', 'string', 'max:255'],
            'email'    => ['required', 'email'],
            'nif'      => ['nullable', 'string', 'max:20'],
            'tfno'     => ['nullable', 'string', 'max:30'],
            'empresa'  => ['nullable', 'string', 'in:Babyplant S.L.,Babyplant Spain S.L.,Perijena,'],
            'password' => ['nullable', 'string', 'min:12'],
        ]);

        $oldEmail = mb_strtolower(trim((string) $trabajador->email));
        $newEmail = mb_strtolower(trim((string) $request->email));

        // 2) Actualizamos campos en Polifonía
        $trabajador->email  = $newEmail;
        $trabajador->nombre = $request->nombre ?? $trabajador->nombre;
        $trabajador->nif    = $request->nif    ?? $trabajador->nif;
        $trabajador->tfno   = $request->tfno   ?? $trabajador->tfno;

        // Empresa: permitimos vaciar si se elige "Sin empresa"
        if ($request->has('empresa')) {
            $trabajador->empresa = $request->empresa ?: null;
        }

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

        $msg = $user
            ? 'Trabajador actualizado (Polifonía + Usuario).'
            : 'Trabajador actualizado en Polifonía.';

        // 4) Si venimos del editor unificado, volvemos a él
        if ($request->filled('redirect_uuid')) {
            return redirect()
                ->route('usuarios.edit.uuid', ['uuid' => $request->redirect_uuid])
                ->with('success', $msg);
        }

        return redirect()
            ->route('usuarios.index')
            ->with('success', $msg);
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

    public function getFichajes(Request $r, int $trabajador){

        $limit = (int) $r->query('limit', 100);
        $limit = max(1, min($limit, 500)); // límite seguro

        // 1) Obtener email del trabajador en Polifonía
        $t = TrabajadorPolifonia::on('mysql_polifonia')->findOrFail($trabajador);
        $email = mb_strtolower(trim($t->email ?? ''));

        if ($email === '') {
            return response()->json(['ok' => true, 'data' => []]);
        }

        // 2) Resolver user remoto del sistema de fichajes por email
        $u = UserTrabajador::query()
            ->select(['id', 'email'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$u) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        // 3) Traer fichajes usando fecha_hora (campo correcto de negocio)
        $rows = Fichar::query()
            ->select(['bienestar', 'fecha_hora'])
            ->where('user_id', $u->id)
            ->whereNotNull('fecha_hora')
            ->orderBy('fecha_hora', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($f) {
                // fecha_hora puede venir como string => parse seguro
                $dt = filled($f->fecha_hora) ? Carbon::parse($f->fecha_hora) : null;

                return [
                    'bienestar' => (int) ($f->bienestar ?? 0),
                    'fecha'     => $dt?->format('Y-m-d'),
                    'hora'      => $dt?->format('H:i'),
                    'ts'        => $dt?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }
}
