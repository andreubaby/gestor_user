{{-- resources/views/partials/form_user_fichajes.blade.php --}}

@php
    // Necesitamos el ID del trabajador (Polifonía) porque la ruta es /fichajes/{trabajador}
    $trabajadorRouteId = $trabajador->id ?? ($trabajadorId ?? null);

    if (!$trabajadorRouteId) {
        $trabajadorRouteId = null;
    }

    $wm = old('work_mode', $userFichaje->work_mode);

    // Cargar los últimos 50 punches del usuario
    $recentPunches = $userFichaje->punches()
        ->orderByDesc('happened_at')
        ->limit(50)
        ->get();
@endphp

@if(!$trabajadorRouteId)
    <div class="p-3 rounded-lg bg-amber-50 text-amber-800 text-sm ring-1 ring-amber-200">
        No se puede actualizar Fichajes porque este vínculo no tiene <strong>trabajador</strong> asociado.
    </div>
@else
    <form method="POST" action="{{ route('fichajes.update', ['trabajador' => $trabajadorRouteId]) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="fichajes_user_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" id="fichajes_user_nombre" name="name"
                   value="{{ old('name', $userFichaje->name) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div class="mb-4">
            <label for="fichajes_user_email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="fichajes_user_email" name="email"
                   value="{{ old('email', $userFichaje->email) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div class="mb-4">
            <label for="fichajes_user_work_mode" class="block text-sm font-medium text-gray-700">Modo de trabajo</label>
            <select id="fichajes_user_work_mode" name="work_mode"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:outline-none focus:ring focus:ring-blue-300">
                <option value="office"    {{ $wm === 'office' ? 'selected' : '' }}>office</option>
                <option value="intensive" {{ $wm === 'intensive' ? 'selected' : '' }}>intensive</option>
                <option value="campaign"  {{ $wm === 'campaign' ? 'selected' : '' }}>campaign</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Selecciona una de las 3 opciones disponibles.</p>
        </div>

        <div class="mb-4">
            <label for="fichajes_user_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="password" id="fichajes_user_password" name="password"
                   placeholder="Dejar en blanco para no cambiar"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
        </div>

        <div class="text-right">
            <button type="submit" class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-800 transition">
                Guardar cambios
            </button>
        </div>
    </form>

    {{-- ── Lista de fichajes recientes con opción de borrar ────────────────── --}}
    <div class="mt-6">
        <h4 class="text-sm font-semibold text-slate-700 mb-2">
            Fichajes recientes
            <span class="ml-1 text-xs font-normal text-slate-400">(últimos 50)</span>
        </h4>

        @if($recentPunches->isEmpty())
            <p class="text-xs text-slate-400 italic">No hay fichajes registrados.</p>
        @else
            <div class="space-y-1 max-h-64 overflow-y-auto pr-1">
                @foreach($recentPunches as $punch)
                    @php
                        $dt       = $punch->happened_at ? \Carbon\Carbon::parse($punch->happened_at) : null;
                        $typeIcon = match(strtolower((string)($punch->type ?? ''))) {
                            'in'  => '🟢',
                            'out' => '🔴',
                            default => '⚪',
                        };
                        $typeLabel = match(strtolower((string)($punch->type ?? ''))) {
                            'in'  => 'Entrada',
                            'out' => 'Salida',
                            default => ucfirst($punch->type ?? '—'),
                        };
                    @endphp
                    <div class="flex items-center justify-between gap-2 rounded-lg px-3 py-1.5 bg-slate-50 ring-1 ring-slate-100 text-xs">
                        <span class="font-mono text-slate-600 shrink-0">
                            {{ $typeIcon }}
                            {{ $dt ? $dt->format('d/m/Y H:i') : '—' }}
                        </span>
                        <span class="text-slate-500 shrink-0">{{ $typeLabel }}</span>
                        @if($punch->note)
                            <span class="text-slate-400 truncate max-w-[80px]" title="{{ $punch->note }}">{{ $punch->note }}</span>
                        @endif
                        <form method="POST"
                              action="{{ route('fichajes.punches.destroy', ['punch' => $punch->id]) }}"
                              onsubmit="return confirm('¿Seguro que quieres borrar este fichaje del {{ $dt ? $dt->format('d/m/Y H:i') : '' }}?');"
                              class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-500 hover:text-red-700 font-semibold leading-none px-1 py-0.5 rounded hover:bg-red-50 transition"
                                    title="Eliminar fichaje">
                                ✕
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
