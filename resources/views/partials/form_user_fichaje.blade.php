{{-- resources/views/partials/form_user_fichajes.blade.php --}}

@php
    // Necesitamos el ID del trabajador (Polifonía) porque la ruta es /fichajes/{trabajador}
    $trabajadorRouteId = $trabajador->id ?? ($trabajadorId ?? null);

    if (!$trabajadorRouteId) {
        // si no hay trabajador, no podemos generar la URL de update
        // (así evitas el "Missing required parameter")
        $trabajadorRouteId = null;
    }

    $wm = old('work_mode', $userFichaje->work_mode);
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
@endif
