<?php

namespace App\Http\Controllers;

use App\Models\Tacografo;
use Illuminate\Http\Request;

class TacografoController extends Controller
{
    public function index(Request $request)
    {
        $q     = $request->get('q');
        $tipo  = $request->get('tipo');
        $activo = $request->get('activo'); // '1'|'0'|null

        $tacografos = Tacografo::query()
            ->when($q, fn($query) =>
            $query->where(function ($q2) use ($q) {
                $q2->where('valor', 'like', "%{$q}%")
                    ->orWhere('observaciones', 'like', "%{$q}%");
            })
            )
            ->when($tipo, fn($query) => $query->where('tipo', $tipo))
            ->when($activo !== null && $activo !== '', fn($query) => $query->where('activo', (int)$activo))

            // 👇 ORDEN CLAVE
            ->orderByRaw("FIELD(tipo, 'camion', 'camionero')")

            // secundarios
            ->orderByDesc('fecha')
            ->orderByDesc('id')

            ->paginate(25)
            ->withQueryString();

        return view('tacografo.index', compact('tacografos', 'q', 'tipo', 'activo'));
    }

    public function create()
    {
        return view('tacografo.create', [
            'today' => now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo' => 'required|in:camion,camionero',
            'valor' => 'required|string|max:255',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string|max:2000',
            'activo' => 'nullable|boolean',
        ]);

        Tacografo::create([
            'tipo' => $data['tipo'],
            'valor' => $data['valor'],
            'fecha' => $data['fecha'],
            'observaciones' => $data['observaciones'] ?? null,
            'activo' => isset($data['activo']) ? (int)$data['activo'] : 0,
        ]);

        return redirect()
            ->route('tacografo.index')
            ->with('success', 'Registro creado correctamente.');
    }

    public function edit(Tacografo $tacografo)
    {
        return view('tacografo.edit', compact('tacografo'));
    }

    public function update(Request $request, Tacografo $tacografo)
    {
        $data = $request->validate([
            'tipo' => 'required|in:camion,camionero',
            'valor' => 'required|string|max:255',
            'fecha' => 'required|date',
            'observaciones' => 'nullable|string|max:255',
        ]);

        $data['activo'] = $request->has('activo');

        $tacografo->update($data);

        return redirect()->route('tacografo.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(Tacografo $tacografo)
    {
        $tacografo->delete();
        return back()->with('success', 'Registro eliminado.');
    }

    public function toggle(Tacografo $tacografo)
    {
        $tacografo->update(['activo' => !$tacografo->activo]);
        return back();
    }

    public function updateFecha(Request $request, Tacografo $tacografo)
    {
        if (!in_array($tacografo->tipo, ['camion', 'camionero'], true)) {
            return response()->json(['ok' => false, 'message' => 'Tipo no permitido.'], 403);
        }

        $data = $request->validate([
            'fecha' => 'required|date',
        ]);

        $tacografo->update([
            'fecha' => $data['fecha'],
        ]);

        return response()->json([
            'ok' => true,
            'fecha' => $tacografo->fecha?->format('d/m/Y'),
            'fecha_iso' => $tacografo->fecha?->format('Y-m-d'),
        ]);
    }
}
