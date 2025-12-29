<form method="POST" action="{{ route('store.user.update', ['id' => $userStore->id]) }}">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <label for="store_user_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" id="store_user_nombre" name="nombre"
               value="{{ old('nombre', $userStore->name) }}"
               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
    </div>

    <div class="mb-4">
        <label for="store_user_email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="store_user_email" name="email"
               value="{{ old('email', $userStore->email) }}"
               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
    </div>

    <div class="mb-4">
        <label for="store_user_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input type="password" id="store_user_password" name="password"
               placeholder="Dejar en blanco para no cambiar"
               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring focus:ring-blue-300">
    </div>

    <div class="text-right">
        <button type="submit" class="bg-blue-300 text-white px-5 py-2 rounded-lg hover:bg-blue-500 transition">
            Guardar cambios
        </button>
    </div>
</form>
