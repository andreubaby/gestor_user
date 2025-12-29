<form method="POST" action="{{ route('pluton.update', ['pluton' => $usuarioPluton->id]) }}">
    @csrf
    @method('PUT')

    <div class="mb-4">
        <label for="pluton_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" id="pluton_nombre" name="nombre"
               value="{{ old('nombre', $usuarioPluton->nombre) }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none">
    </div>

    <div class="mb-4">
        <label for="pluton_email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="pluton_email" name="email"
               value="{{ old('email', $usuarioPluton->email) }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none">
    </div>

    <div class="mb-4">
        <label for="pluton_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input type="password" id="pluton_password" name="password"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none"
               placeholder="Dejar en blanco para no cambiar">
    </div>

    <div class="mb-4">
        <label for="pluton_imei" class="block text-sm font-medium text-gray-700">IMEI</label>
        <input type="text" id="pluton_imei" name="imei"
               value="{{ old('imei', $usuarioPluton->imei) }}"
               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none">
    </div>

    <div class="text-right">
        <button type="submit" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-800 transition">
            Guardar cambios
        </button>
    </div>
</form>
