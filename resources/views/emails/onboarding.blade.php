<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; background:#f9fafb; padding:20px;">

<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;padding:24px;border:1px solid #e5e7eb">

    <h2 style="color:#065f46;margin-top:0">
        👋 Hola {{ $nombre }}
    </h2>

    <p style="color:#374151;white-space:pre-line">
        {!! nl2br(e($intro)) !!}
    </p>

    <hr style="margin:24px 0;border:none;border-top:1px solid #e5e7eb">

    <h3 style="color:#065f46">📚 Contenido</h3>

    <ul style="padding-left:18px">
        @foreach($items as $item)
            <li style="margin-bottom:10px">
                <strong>{{ $item['title'] }}</strong><br>
                <a href="{{ $item['url'] }}"
                   target="_blank"
                   style="color:#2563eb;text-decoration:none">
                    👉 Ver contenido
                </a>
            </li>
        @endforeach
    </ul>

    <hr style="margin:24px 0;border:none;border-top:1px solid #e5e7eb">

    <p style="font-size:13px;color:#6b7280">
        Si tienes cualquier duda, contacta con el equipo de Babyplant.
    </p>

</div>

</body>
</html>
