<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · Bienes Patrimoniales I.E. 50023</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
<main class="login">
    <div class="caja">
        <div class="marca" style="border:0;justify-content:center;color:var(--azul)">
            <div class="logo" style="background:var(--azul);color:#fff" aria-hidden="true">BP</div>
        </div>
        <h1>Control de Bienes Patrimoniales</h1>
        <p class="tenue" style="text-align:center;margin-top:0">I.E. Pública N.° 50023 «República de México» – Cusco</p>
        @include('partials.flash')
        <form method="post" action="{{ route('login.ingresar') }}" novalidate>
            @csrf
            @include('partials.campo', ['nombre' => 'email', 'etiqueta' => 'Correo', 'tipo' => 'email', 'req' => true])
            <div class="campo">
                <label for="f-password">Contraseña <span class="req" aria-hidden="true">*</span></label>
                <input id="f-password" type="password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primario" style="width:100%;justify-content:center" type="submit">Ingresar</button>
        </form>
        <p class="privacidad">Los datos personales se tratan conforme a la Ley N.° 29733 y solo para el control interno de bienes de la institución.</p>
    </div>
</main>
</body>
</html>
