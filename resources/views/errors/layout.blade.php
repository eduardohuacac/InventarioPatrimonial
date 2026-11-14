<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('codigo') · Bienes Patrimoniales</title><link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}"></head>
<body><main class="login"><div class="caja" style="text-align:center">
<h1 style="font-size:2.5rem;margin:0">@yield('codigo')</h1><p>@yield('mensaje')</p>
<a class="btn btn-primario" href="{{ url('/') }}">Ir al inicio</a></div></main></body></html>
