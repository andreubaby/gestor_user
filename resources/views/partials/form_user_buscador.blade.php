<form method="POST" action="{{ route('buscador.user.update', ['id' => $userBuscador->id]) }}">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <label for="buscador_worker_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" id="buscador_worker_nombre" name="nombre"
               value="{{ old('nombre', $userBuscador->name) }}"
               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
    </div>

    <div class="mb-4">
        <label for="buscador_worker_email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="buscador_worker_email" name="email"
               value="{{ old('email', $userBuscador->email) }}"
               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
    </div>

    <div class="mb-4">
        <label for="buscador_worker_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input type="password" id="buscador_worker_password" name="password"
               placeholder="Dejar en blanco para no cambiar"
               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
    </div>

    <div class="text-right">
        <button type="submit" class="bg-blue-400 text-white px-5 py-2 rounded-lg hover:bg-blue-600 transition">
            Guardar cambios
        </button>
    </div>
</form>
