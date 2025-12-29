<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario Plutón</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Instrument Sans"', 'ui-sans-serif']
                    },
                    colors: {
                        primary: '#2563eb',
                        secondary: '#1e40af',
                        pluton: '#7c3aed'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100 font-sans p-6">

<!-- Navbar -->
<header class="bg-white shadow mb-8">
    <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-primary">Gestor Centralizado</h1>
        <nav class="space-x-4 flex items-center">
            <a href="{{ route('usuarios.index') }}" class="text-gray-700 hover:text-primary font-medium">
                Usuarios
            </a>
            <a href="{{ route('gestor.gestoria') }}" class="text-gray-700 hover:text-primary font-medium">
                Perfil
            </a>
            <a href="{{ route('usuarios.vincular') }}" class="text-gray-700 hover:text-purple-600 font-medium">
                Vincular
            </a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-gray-700 hover:text-red-600 font-medium">
                Salir
            </a>
        </nav>
    </div>
</header>

<div class="text-right mb-6">
    @if(isset($vinculo))
        <a href="{{ route('usuarios.vincular.uuid', ['uuid' => $vinculo->uuid]) }}"
           class="inline-block bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
            🔗 Editar Vinculación
        </a>
    @else
        <a href="{{ route('usuarios.vincular', ['trabajador_id' => $userStore->id]) }}"
           class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            ➕ Crear Vinculación
        </a>
    @endif
</div>

<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">
    <h1 class="text-2xl font-bold text-primary mb-6">Editar Usuario Store</h1>

    <form method="POST" action="{{ route('semillas.user.update', $userStore->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" id="name" name="name"
                   value="{{ old('name', $userStore->name) }}"
                   class="w-full border px-4 py-2 rounded-lg focus:ring focus:ring-primary">
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="email" name="email"
                   value="{{ old('email', $userStore->email) }}"
                   class="w-full border px-4 py-2 rounded-lg focus:ring focus:ring-primary">
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="password" id="password" name="password"
                   class="w-full border px-4 py-2 rounded-lg focus:ring focus:ring-primary"
                   placeholder="Dejar en blanco para no cambiar">
        </div>

        <div class="text-right">
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

</body>
</html>
