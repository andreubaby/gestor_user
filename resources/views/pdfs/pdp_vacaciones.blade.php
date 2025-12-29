<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificación al Trabajador</title>
    <style>
        @page { margin: 1.7cm 2cm; }

        body{
            font-family: Georgia, serif;
            font-size: 15px;
            line-height: 1.55;
            color:#000;
            margin:0;
        }

        .logo{
            display:block;
            margin: .4cm auto .6cm;
            width: 12.5cm;
            height:auto;
        }

        .empresa-info{
            font-weight:700;
            margin: 0 0 22px;
        }

        .titulo{
            font-size: 19px;
            font-weight: 700;
            text-align:center;
            border-top: 2px solid #000;
            padding-top: 10px;
            margin: 22px 0 18px;
            letter-spacing: .2px;
        }

        .contenido{
            text-align: justify;
            margin-bottom: 34px;
        }

        ul{
            margin: 14px 0 0;
            padding-left: 22px;
        }
        li{ margin: 5px 0; }

        .firmas{
            display:flex;
            justify-content: space-between;
            width:100%;
            gap: 24px;
            margin-top: 70px; /* deja aire para “llenar” hoja */
        }

        .firma{
            width: 45%;
            text-align: left;
        }

        .linea-firma{
            border-top: 1px solid #000;
            margin-top: 85px;
            padding-top: 8px;
            font-weight:700;
        }

        .linea-firma2{
            border-top: 1px solid #000;
            margin-top: 105px;
            padding-top: 8px;
            font-weight:700;
        }

        .fecha{
            text-align:right;
            margin-top: 38px;
        }
    </style>
</head>
<body>

@php
    switch ($empresa) {
      case 'Babyplant S.L.':
        $logo = public_path('img/babyplantsin.svg');
        $empresaNombre = 'BABYPLANT, S.L.';
        $empresaCIF = 'B30126650';
        $empresaDir = 'Ctra. Santomera - Alquerías, Km. 1';
        break;

      case 'Babyplant Spain S.L.':
        $logo = public_path('img/babyplantspainb.svg');
        $empresaNombre = 'BABYPLANT SPAIN, S.L.';
        $empresaCIF = 'B13715131';
        $empresaDir = 'Ctra. Santomera - Alquerías, Km. 1';
        break;

      case 'Perijena':
        $logo = public_path('img/perijena.svg');
        $empresaNombre = 'PERIJENA, S.L.U.';
        $empresaCIF = 'B44875920';
        $empresaDir = 'Acequia de Zaraiche, 21 - Santomera';
        break;

      default:
        $logo = null;
        $empresaNombre = $empresa;
        $empresaCIF = '';
        $empresaDir = '';
        break;
    }
@endphp

@if($logo)
    <img class="logo" src="{{ $logo }}" alt="">
@endif

<div class="empresa-info">
    {{ $empresaNombre }}<br>
    CIF: {{ $empresaCIF }}<br>
    {{ $empresaDir }}
</div>

<div class="titulo">NOTIFICACIÓN AL TRABAJADOR</div>

<div class="contenido">
    <strong>{{ $trabajador }}</strong>, con DNI <strong>{{ $dni }}</strong>, tiene registrado(s) el/los siguiente(s)
    periodo(s) de <strong>{{ $tipo }}</strong> correspondiente(s) al año <strong>{{ $anyo }}</strong>:

    <ul>
        @foreach($rangos as $r)
            <li>Del {{ $r['inicio'] }} al {{ $r['fin'] }}</li>
        @endforeach
    </ul>

    <p style="margin-top:16px;">
        Quedando informado/a en la fecha de hoy.
    </p>
</div>

<div class="firmas">
    <div class="firma">
        <div class="linea-firma">{{ $trabajador }}</div>
    </div>

    <div class="firma">
        <div class="linea-firma2">Carmen Antón Cayuelas</div>
    </div>
</div>

<div class="fecha">
    Santomera, {{ $fecha }}
</div>

</body>
</html>
