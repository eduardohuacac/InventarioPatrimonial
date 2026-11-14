@extends('layouts.app')
@section('titulo', 'Ambiente')
@section('contenido')
<div class="cabecera"><div><h1>{{ $ambiente->exists ? 'Editar ambiente' : 'Nuevo ambiente' }}</h1></div><a class="btn btn-sec" href="{{ route('admin.ambientes.index') }}">Volver</a></div>
<form method="post" action="{{ $ambiente->exists ? route('admin.ambientes.update', $ambiente) : route('admin.ambientes.store') }}" class="tarjeta" style="max-width:640px">
    @csrf @if($ambiente->exists) @method('PUT') @endif
    @include('partials.campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'req' => true, 'valor' => $ambiente->nombre])
    <div class="form-grid">
        @include('partials.campo', ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'select', 'req' => true, 'valor' => $ambiente->tipo, 'opciones' => \App\Models\Ambiente::TIPOS])
        @include('partials.campo', ['nombre' => 'piso', 'etiqueta' => 'Piso', 'valor' => $ambiente->piso])
    </div>
    <div class="campo"><label class="check"><input type="checkbox" name="activo" value="1" @checked(old('activo', $ambiente->activo))> Activo</label></div>
    <button class="btn btn-primario">Guardar</button>
</form>
@endsection
