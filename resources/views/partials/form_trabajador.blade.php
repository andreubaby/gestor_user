<form method="POST" action="{{ route('trabajadores.update', ['id' => $trabajador->id]) }}">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <label for="trabajador_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" id="trabajador_nombre" name="nombre"
               value="{{ old('nombre', $trabajador->nombre) }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none">
    </div>

    <div class="mb-4">
        <label for="trabajador_email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="trabajador_email" name="email"
               value="{{ old('email', $trabajador->email) }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none">
    </div>

    <div class="mb-4">
        <label for="trabajador_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input type="password" id="trabajador_password" name="password"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none"
               placeholder="Dejar en blanco para no cambiar">
    </div>

    <div class="text-right">
        <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-800 transition">
            Guardar cambios
        </button>
    </div>
</form>
