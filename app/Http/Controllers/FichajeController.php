<?php

namespace App\Http\Controllers;

use App\Models\TrabajadorPolifonia;
use App\Models\UsuarioVinculado;
use App\Models\UserFichaje;     // ✅ nuevo: mysql_fichajes.users
use App\Models\Fichar;          // si quieres historial (ajusta conexión/modelo si cambia)
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FichajeController extends Controller
{
    /**
     * Editar el usuario de fichajes asociado a un Trabajador de Polifonía.
     * La “llave” de enlace sigue siendo el email del trabajador.
     */
    public function edit(int $trabajadorId)
    {
        $trabajador = TrabajadorPolifonia::on('mysql_polifonia')->findOrFail($trabajadorId);

        $email = mb_strtolower(trim((string)($trabajador->email ?? '')));

        // Si no hay email, no podemos enlazar
        if ($email === '') {
            abort(404, 'El trabajador no tiene email, no se puede localizar el usuario de fichajes.');
        }

        // Buscar user fichajes por email
        $userFichaje = UserFichaje::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        // Si no existe, puedes:
        // A) abortar
        // B) crear automáticamente (yo te dejo B comentado por si lo quieres)
        if (!$userFichaje) {
            abort(404, 'No existe usuario en el sistema de fichajes para este email.');
            /*
            $userFichaje = UserFichaje::create([
                'name'      => $trabajador->nombre ?? '—',
                'email'     => $email,
                'work_mode' => 'office',
                'password'  => Hash::make(str()->random(16)),
            ]);
            */
        }

        // vínculo (si lo usas para el botón)
        $vinculo = UsuarioVinculado::where('trabajador_id', $trabajadorId)->first();

        // OJO: tu blade usa $userFichaje
        return view('fichajes.edit', compact('trabajador', 'userFichaje', 'vinculo'));
    }

    /**
     * Actualiza datos del usuario de fichajes y mantiene coherencia con Polifonía.
     */
    public function update(Request $request, int $trabajadorId)
    {
        $trabajador = TrabajadorPolifonia::on('mysql_polifonia')->findOrFail($trabajadorId);

        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255'],
            'work_mode' => ['required', 'in:office,intensive,campaign'],
            'password'  => ['nullable', 'string', 'min:12'],
            // si usas confirm:
            // 'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        $oldEmail = mb_strtolower(trim((string)($trabajador->email ?? '')));
        $newEmail = mb_strtolower(trim((string)$request->email));

        // 1) Actualizar Polifonía (mínimo email)
        $trabajador->email = $newEmail;
        $trabajador->save();

        // 2) Buscar user en BD fichajes por oldEmail o newEmail
        $userFichaje = UserFichaje::query()
            ->whereRaw('LOWER(email) = ?', [$oldEmail])
            ->first()
            ?? UserFichaje::query()
                ->whereRaw('LOWER(email) = ?', [$newEmail])
                ->first();

        if (!$userFichaje) {
            // Si quieres, aquí puedes crear el user automáticamente.
            // Si prefieres no crear, devolvemos warning.
            return redirect()
                ->route('usuarios.index')
                ->with('success', 'Trabajador actualizado en Polifonía, pero no existe usuario en Fichajes para actualizar.');
        }

        // 3) Actualizar UserFichaje
        $userFichaje->name      = $request->name;
        $userFichaje->email     = $newEmail;
        $userFichaje->work_mode = $request->work_mode;

        if ($request->filled('password')) {
            $userFichaje->password = Hash::make($request->password);
        }

        $userFichaje->save();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario de fichajes actualizado (Polifonía + Fichajes).');
    }

    /**
     * (Opcional) Historial de fichajes:
     * Si tu tabla fichar sigue en mysql_trabajadores y se relaciona por user_id
     * tendrás que resolver el user_id real del sistema que genera fichajes.
     *
     * Si ahora fichajes usa mysql_fichajes.users pero fichar sigue apuntando a otra BD,
     * dime dónde está la tabla "fichar" y qué FK usa (user_id contra qué tabla),
     * y te lo dejo perfecto.
     */
    public function getFichajes(Request $r, int $trabajadorId)
    {
        $limit = (int) $r->query('limit', 100);
        $limit = max(1, min($limit, 500));

        $t = TrabajadorPolifonia::on('mysql_polifonia')->findOrFail($trabajadorId);
        $email = mb_strtolower(trim((string)($t->email ?? '')));

        if ($email === '') {
            return response()->json(['ok' => true, 'data' => []]);
        }

        // 🔁 Si tu tabla fichar referencia a mysql_trabajadores.users, usa UserTrabajador.
        // Si referencia a mysql_fichajes.users, entonces aquí sería UserFichaje.
        // Por ahora lo dejo con UserFichaje (ajústalo según tu esquema real).
        $u = UserFichaje::query()
            ->select(['id', 'email'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$u) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $rows = Fichar::query()
            ->select(['bienestar', 'fecha_hora'])
            ->where('user_id', $u->id)
            ->whereNotNull('fecha_hora')
            ->orderBy('fecha_hora', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($f) {
                $dt = filled($f->fecha_hora) ? Carbon::parse($f->fecha_hora) : null;

                return [
                    'bienestar' => (int)($f->bienestar ?? 0),
                    'fecha'     => $dt?->format('Y-m-d'),
                    'hora'      => $dt?->format('H:i'),
                    'ts'        => $dt?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json(['ok' => true, 'data' => $rows]);
    }
}
