@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('contenido')
<div class="cabecera"><div><h1>{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h1></div><a class="btn btn-sec" href="{{ route('admin.usuarios.index') }}">Volver</a></div>
<form method="post" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" class="tarjeta" style="max-width:760px">
    @csrf @if($usuario->exists) @method('PUT') @endif
    <div class="form-grid">
        @include('partials.campo', ['nombre' => 'name', 'etiqueta' => 'Nombre', 'req' => true, 'valor' => $usuario->name])
        @include('partials.campo', ['nombre' => 'email', 'etiqueta' => 'Correo', 'tipo' => 'email', 'req' => true, 'valor' => $usuario->email])
        @include('partials.campo', ['nombre' => 'perfil', 'etiqueta' => 'Perfil', 'tipo' => 'select', 'req' => true, 'valor' => $usuario->perfil?->value,
            'opciones' => collect(\App\Enums\Perfil::cases())->mapWithKeys(fn ($p) => [$p->value => $p->etiqueta()])])
        @include('partials.campo', ['nombre' => 'personal_id', 'etiqueta' => 'Persona del personal', 'tipo' => 'select', 'vacio' => '— Ninguna —', 'valor' => $usuario->personal_id,
            'opciones' => $personal->pluck('nombres', 'id'), 'ayuda' => 'Obligatorio para el perfil Consulta: determina «Mis bienes».'])
        @include('partials.campo', ['nombre' => 'password', 'etiqueta' => $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña', 'tipo' => 'password', 'req' => ! $usuario->exists, 'ayuda' => 'Mínimo 8 caracteres, con letras y números.'])
        @include('partials.campo', ['nombre' => 'password_confirmation', 'etiqueta' => 'Repita la contraseña', 'tipo' => 'password'])
        <div class="campo completo"><label class="check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo))> Usuario activo</label></div>
    </div>
    <button class="btn btn-primario">Guardar</button>
</form>
@endsection
