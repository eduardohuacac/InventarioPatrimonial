@extends('layouts.app')
@section('titulo', 'Personal')
@section('contenido')
<div class="cabecera"><div><h1>{{ $persona->exists ? 'Editar persona' : 'Nueva persona' }}</h1></div><a class="btn btn-sec" href="{{ route('admin.personal.index') }}">Volver</a></div>
<form method="post" action="{{ $persona->exists ? route('admin.personal.update', $persona) : route('admin.personal.store') }}" class="tarjeta" style="max-width:640px">
    @csrf @if($persona->exists) @method('PUT') @endif
    @include('partials.campo', ['nombre' => 'nombres', 'etiqueta' => 'Nombres y apellidos', 'req' => true, 'valor' => $persona->nombres])
    <div class="form-grid">
        @include('partials.campo', ['nombre' => 'cargo', 'etiqueta' => 'Cargo', 'req' => true, 'valor' => $persona->cargo])
        @include('partials.campo', ['nombre' => 'dni', 'etiqueta' => 'DNI (opcional)', 'valor' => $persona->dni, 'maxlength' => 8, 'ayuda' => 'Solo si se necesita para identificar a la persona.'])
    </div>
    <div class="campo @error('activo') error @enderror"><label class="check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $persona->activo))> Labora en la institución</label>
        @error('activo')<div class="msg-error">{{ $message }}</div>@enderror</div>
    <button class="btn btn-primario">Guardar</button>
</form>
@endsection
