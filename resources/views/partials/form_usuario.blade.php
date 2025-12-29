@if(isset($usuario))
    <form method="POST" action="{{ route('usuarios.update', ['usuario' => $usuario->id]) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="usuario_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" id="usuario_nombre" name="nombre"
                   value="{{ old('nombre', $usuario->nombre) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none">
        </div>

        <div class="mb-4">
            <label for="usuario_email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="usuario_email" name="email"
                   value="{{ old('email', $usuario->email) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none">
        </div>

        <div class="mb-4">
            <label for="usuario_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="password" id="usuario_password" name="password"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary outline-none"
                   placeholder="Dejar en blanco para no cambiar">
        </div>

        <div class="text-right">
            <button type="submit" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-secondary transition">
                Guardar cambios
            </button>
        </div>
    </form>
@endif
