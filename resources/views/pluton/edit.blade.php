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
            <a href="{{ route('usuarios.index') }}" class="text-gray-700 hover:text-primary font-medium">Usuarios</a>
            <a href="{{ route('gestor.gestoria') }}" class="text-gray-700 hover:text-primary font-medium">Perfil</a>
            <a href="{{ route('usuarios.vincular') }}" class="text-gray-700 hover:text-purple-600 font-medium">Vincular</a>
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
               class="text-gray-700 hover:text-red-600 font-medium">Salir</a>
        </nav>
    </div>
</header>

<form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

<!-- Acción de vinculación -->
<div class="text-right mb-6">
    @php $vinculo ??= null; @endphp

    @if($vinculo?->uuid)
        <a href="{{ route('usuarios.vincular.uuid', ['uuid' => $vinculo->uuid]) }}"
           class="inline-block bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
            🔗 Editar Vinculación
        </a>
    @else
        <a href="{{ route('usuarios.vincular', ['pluton_id' => $pluton->id]) }}"
           class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
            ➕ Crear Vinculación
        </a>
    @endif
</div>

<!-- Formulario de edición -->
<div class="max-w-3xl mx-auto bg-white p-6 rounded-xl shadow">
    <h1 class="text-2xl font-bold text-purple-700 mb-6">Editar Usuario Plutón</h1>

    <form method="POST" action="{{ route('pluton.update', ['pluton' => $pluton->id]) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="pluton_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" id="pluton_nombre" name="nombre"
                   value="{{ old('nombre', $pluton->nombre) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pluton focus:outline-none">
        </div>

        <div class="mb-4">
            <label for="pluton_email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="pluton_email" name="email"
                   value="{{ old('email', $pluton->email) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pluton focus:outline-none">
        </div>

        <div class="mb-4">
            <label for="pluton_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="password" id="pluton_password" name="password"
                   placeholder="Dejar en blanco para no cambiar"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pluton focus:outline-none">
        </div>

        <div class="mb-4">
            <label for="pluton_imei" class="block text-sm font-medium text-gray-700">IMEI</label>
            <input type="text" id="pluton_imei" name="imei"
                   value="{{ old('imei', $pluton->imei) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-pluton focus:outline-none">
        </div>

        <div class="text-right">
            <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-800 transition">
                Guardar cambios
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('usuarios.index') }}" class="text-sm text-gray-600 hover:underline">
            ← Volver al listado
        </a>
    </div>
</div>

</body>
</html>
