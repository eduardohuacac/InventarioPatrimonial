<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · Bienes Patrimoniales I.E. 50023</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</head>
<body>
<a href="#contenido" class="salto">Saltar al contenido</a>
@php($u = auth()->user())
@php($pendientesMenu = $u->tienePerfil('DIRECTOR', 'ENCARGADO') ? \App\Models\Movimiento::pendientesDeAprobacion()->count() : 0)
<div class="app">
    <nav class="lateral" aria-label="Menú principal">
        <div class="marca">
            <div class="logo" aria-hidden="true">BP</div>
            <div><b>Bienes Patrimoniales</b><span>I.E. N.° 50023 «República de México»</span></div>
        </div>
        <ul class="menu">
            @if ($u->tienePerfil('DIRECTOR', 'ENCARGADO', 'ADMIN'))
                <li><a href="{{ route('panel') }}" @class(['activo' => request()->routeIs('panel')])>Panel</a></li>
            @endif
            @if ($u->tienePerfil('DIRECTOR', 'ENCARGADO'))
                <li class="seccion">Bienes</li>
                <li><a href="{{ route('bienes.index') }}" @class(['activo' => request()->routeIs('bienes.*', 'movimientos.*', 'correcciones.*')])>Bienes</a></li>
                @if ($u->esEncargado())
                    <li><a href="{{ route('bienes.create') }}" @class(['activo' => request()->routeIs('bienes.create')])>Registrar bien</a></li>
                @endif
                <li><a href="{{ route('aprobaciones.index') }}" @class(['activo' => request()->routeIs('aprobaciones.*')])>Aprobaciones @if($pendientesMenu)<span class="contador">{{ $pendientesMenu }}</span>@endif</a></li>
                <li><a href="{{ route('alertas') }}" @class(['activo' => request()->routeIs('alertas')])>Alertas</a></li>
                <li class="seccion">Información</li>
                <li><a href="{{ route('reportes.index') }}" @class(['activo' => request()->routeIs('reportes.*')])>Reportes</a></li>
                <li><a href="{{ route('importacion.index') }}" @class(['activo' => request()->routeIs('importacion.*')])>Importación depurada</a></li>
            @endif
            @if ($u->personal_id)
                <li class="seccion">Personal</li>
                <li><a href="{{ route('mis-bienes') }}" @class(['activo' => request()->routeIs('mis-bienes')])>Mis bienes</a></li>
            @endif
            @if ($u->tienePerfil('ADMIN', 'DIRECTOR'))
                <li class="seccion">Control</li>
                <li><a href="{{ route('auditoria') }}" @class(['activo' => request()->routeIs('auditoria')])>Auditoría</a></li>
            @endif
            @if ($u->esAdmin())
                <li class="seccion">Administración</li>
                <li><a href="{{ route('admin.usuarios.index') }}" @class(['activo' => request()->routeIs('admin.usuarios.*')])>Usuarios</a></li>
                <li><a href="{{ route('admin.personal.index') }}" @class(['activo' => request()->routeIs('admin.personal.*')])>Personal</a></li>
                <li><a href="{{ route('admin.ambientes.index') }}" @class(['activo' => request()->routeIs('admin.ambientes.*')])>Ambientes</a></li>
            @endif
        </ul>
    </nav>

    <div class="principal">
        <header class="barra">
            <button type="button" class="btn btn-sec boton-menu" data-menu aria-expanded="false">☰ Menú</button>
            @if ($u->tienePerfil('DIRECTOR', 'ENCARGADO'))
                <form class="buscar" action="{{ route('bienes.index') }}" method="get" role="search">
                    <label for="busqueda-global" class="salto">Buscar bien</label>
                    <input type="search" id="busqueda-global" name="q" placeholder="Código patrimonial o descripción…" value="{{ request()->routeIs('bienes.index') ? request('q') : '' }}">
                    <button class="btn btn-primario" type="submit">Buscar</button>
                </form>
            @else
                <span></span>
            @endif
            <div class="usuario">
                <div><b>{{ $u->name }}</b><br><small>{{ $u->perfil->etiqueta() }}</small></div>
                <a href="{{ route('perfil.clave') }}" class="btn btn-sec btn-chico">Contraseña</a>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sec btn-chico">Salir</button></form>
            </div>
        </header>

        <main id="contenido" class="contenido">
            @include('partials.flash')
            @yield('contenido')
        </main>

        <footer class="pie">
            Herramienta auxiliar de control interno. El registro oficial de bienes patrimoniales se mantiene en el SIGA – Módulo de Patrimonio (UGEL Cusco).
        </footer>
    </div>
</div>
</body>
</html>
