<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>

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
                        secondary: '#1e40af'
                    }
                }
            }
        };
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

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-primary text-center mb-8">Editar Registros</h1>

    @php
        use App\Models\UsuarioVinculado;

        $tipo = isset($usuario) ? 'usuario' : (isset($trabajador) ? 'trabajador' : (isset($usuarioPluton) ? 'pluton' : null));
        $id = $usuario->id ?? $trabajador->id ?? $usuarioPluton->id ?? null;
        $vinculo = $tipo ? UsuarioVinculado::where("{$tipo}_id", $id)->first() : null;
    @endphp

    <div class="text-right mb-6">
        @if(isset($vinculo))
            <a href="{{ route('usuarios.vincular.uuid', ['uuid' => $vinculo->uuid]) }}"
               class="inline-block bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
                🔗 Editar Vinculación
            </a>
        @else
            <a href="{{ route('usuarios.vincular', ['trabajador_id' => $trabajador->id]) }}"
               class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition">
                ➕ Crear Vinculación
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Aplicación Principal -->
        @if(isset($usuario))
            <section class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold text-primary mb-4">Aplicación Principal</h2>
                @include('partials.form_usuario', ['usuario' => $usuario])
            </section>
        @endif

        <!-- Polifonía -->
        @if(isset($trabajador))
            <section class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold text-green-700 mb-4">Aplicación Polifonía</h2>
                @include('partials.form_trabajador', ['trabajador' => $trabajador])
            </section>
        @endif

        <!-- Plutón -->
        @if(isset($usuarioPluton))
            <section class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold text-purple-700 mb-4">Aplicación Plutón</h2>
                @include('partials.form_pluton', ['usuarioPluton' => $usuarioPluton])
            </section>
        @endif

        <!-- Usuario Buscador -->
        @if(isset($usuarioBuscador))
            <section class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold text-indigo-600 mb-4">Usuario Buscador</h2>
                @include('partials.form_user_buscador', ['usuarioBuscador' => $usuarioBuscador])
            </section>
        @endif

        <!-- Trabajador Buscador -->
        @if(isset($trabajadorBuscador))
            <section class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold text-pink-600 mb-4">Trabajador Buscador</h2>
                @include('partials.form_worker_buscador', ['trabajadorBuscador' => $trabajadorBuscador])
            </section>
        @endif
    </div>

    <div class="text-center mt-6">
        <a href="{{ route('usuarios.index') }}" class="text-sm text-gray-600 hover:underline">← Volver al listado</a>
    </div>
</div>

</body>
</html>
