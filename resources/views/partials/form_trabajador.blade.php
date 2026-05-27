<form method="POST" action="{{ route('trabajadores.update', ['id' => $trabajador->id]) }}">
    @csrf
    @method('PUT')

    {{-- UUID para redirección de vuelta --}}
    @if(isset($vinculo))
        <input type="hidden" name="redirect_uuid" value="{{ $vinculo->uuid }}">
    @endif

    <div class="mb-3">
        <label for="trabajador_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" id="trabajador_nombre" name="nombre"
               value="{{ old('nombre', $trabajador->nombre) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none text-sm">
    </div>

    <div class="mb-3">
        <label for="trabajador_email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="trabajador_email" name="email"
               value="{{ old('email', $trabajador->email) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none text-sm">
    </div>

    <div class="mb-3">
        <label for="trabajador_nif" class="block text-sm font-medium text-gray-700">NIF / DNI</label>
        <input type="text" id="trabajador_nif" name="nif"
               value="{{ old('nif', $trabajador->nif) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none text-sm"
               placeholder="12345678A">
    </div>

    <div class="mb-3">
        <label for="trabajador_tfno" class="block text-sm font-medium text-gray-700">Teléfono</label>
        <input type="text" id="trabajador_tfno" name="tfno"
               value="{{ old('tfno', $trabajador->tfno) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none text-sm"
               placeholder="600000000">
    </div>

    <div class="mb-3">
        <label for="trabajador_empresa" class="block text-sm font-medium text-gray-700">Empresa</label>
        <select id="trabajador_empresa" name="empresa"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none text-sm bg-white">
            @php
                $empresas = ['Babyplant S.L.', 'Babyplant Spain S.L.', 'Perijena'];
                $empresaActual = old('empresa', $trabajador->empresa ?? '');
            @endphp
            <option value="">— Sin empresa —</option>
            @foreach($empresas as $e)
                <option value="{{ $e }}" {{ $empresaActual === $e ? 'selected' : '' }}>{{ $e }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="trabajador_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input type="password" id="trabajador_password" name="password"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none text-sm"
               placeholder="Dejar en blanco para no cambiar">
    </div>

    <div class="text-right">
        <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-800 transition text-sm font-semibold">
            Guardar cambios
        </button>
    </div>
</form>
