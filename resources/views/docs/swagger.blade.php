<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Docs - Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #0b1020;
            color: #e5e7eb;
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            border-bottom: 1px solid #1f2937;
            background: #111827;
        }
        .topbar a {
            color: #93c5fd;
            text-decoration: none;
            font-size: 14px;
        }
        #swagger-ui {
            min-height: calc(100vh - 48px);
            background: #ffffff;
        }
    </style>
</head>
<body>
<div class="topbar">
    <strong>Documentacion API</strong>
    <a href="{{ route('gestor.gestoria') }}">Volver a gestoria</a>
</div>
<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
    window.ui = SwaggerUIBundle({
        url: '{{ route('api.docs.spec') }}',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset,
        ],
        layout: 'BaseLayout',
        defaultModelsExpandDepth: 1,
    });
});
</script>
</body>
</html>

