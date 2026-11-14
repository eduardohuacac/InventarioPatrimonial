@extends('layouts.app')
@section('titulo', 'Cambiar contraseña')
@section('contenido')
<div class="cabecera"><div><h1>Cambiar contraseña</h1><small>Mínimo 8 caracteres, con letras y números.</small></div></div>
<form method="post" action="{{ route('perfil.clave.actualizar') }}" class="tarjeta" style="max-width:480px">
    @csrf @method('PUT')
    @include('partials.campo', ['nombre' => 'actual', 'etiqueta' => 'Contraseña actual', 'tipo' => 'password', 'req' => true])
    @include('partials.campo', ['nombre' => 'password', 'etiqueta' => 'Nueva contraseña', 'tipo' => 'password', 'req' => true])
    @include('partials.campo', ['nombre' => 'password_confirmation', 'etiqueta' => 'Repita la nueva contraseña', 'tipo' => 'password', 'req' => true])
    <button class="btn btn-primario">Guardar</button>
</form>
@endsection
