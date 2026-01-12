<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupAssignmentController extends Controller
{
    public function create(Request $request)
    {
        $groupId = $request->query('group_id');

        $groups = Group::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // ⚠️ Trabajador probablemente está en otra conexión (polifonía)
        $trabajadores = Trabajador::query()
            ->orderBy('nombre')
            ->limit(5000) // evita reventar la página
            ->get();

        $selectedGroup = null;
        $members = collect();

        if ($groupId) {
            $selectedGroup = Group::find($groupId);

            // miembros actuales (pivot en mysql)
            $memberIds = DB::table('group_trabajador')
                ->where('group_id', $groupId)
                ->pluck('trabajador_id')
                ->toArray();

            // cargar datos del trabajador desde conexión polifonía
            $members = Trabajador::whereIn('id', $memberIds)
                ->orderBy('nombre')
                ->get();
        }

        return view('groups.assign', compact('groups', 'trabajadores', 'selectedGroup', 'members'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'trabajador_ids' => ['required', 'array', 'min:1'],
            'trabajador_ids.*' => ['integer'],
        ]);

        $groupId = (int) $data['group_id'];
        $ids = array_values(array_unique(array_map('intval', $data['trabajador_ids'])));

        // (opcional) valida existencia en polifonía para evitar ids inventados
        $existingIds = Trabajador::whereIn('id', $ids)->pluck('id')->map(fn($v)=>(int)$v)->toArray();
        $existingIds = array_values(array_unique($existingIds));

        if (!count($existingIds)) {
            return back()->withErrors(['trabajador_ids' => 'No se encontraron trabajadores válidos.'])->withInput();
        }

        // Inserta en pivot sin duplicar
        // ✅ MySQL: insertOrIgnore
        $rows = array_map(fn($tid) => [
            'group_id' => $groupId,
            'trabajador_id' => $tid,
            'created_at' => now(),
            'updated_at' => now(),
        ], $existingIds);

        DB::table('group_trabajador')->insertOrIgnore($rows);

        return redirect()
            ->route('groups.assign.create', ['group_id' => $groupId])
            ->with('ok', 'Usuarios añadidos al grupo correctamente.');
    }

    public function detach(Request $request)
    {
        $data = $request->validate([
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'trabajador_id' => ['required', 'integer'],
        ]);

        DB::table('group_trabajador')
            ->where('group_id', (int)$data['group_id'])
            ->where('trabajador_id', (int)$data['trabajador_id'])
            ->delete();

        return redirect()
            ->route('groups.assign.create', ['group_id' => (int)$data['group_id']])
            ->with('ok', 'Usuario quitado del grupo.');
    }
}
