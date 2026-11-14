@extends('layouts.app')
@section('titulo', 'Editar '.$bien->codigo_patrimonial)
@section('contenido')
<div class="cabecera">
    <div><h1>Editar datos descriptivos</h1><small class="codigo">{{ $bien->codigo_patrimonial }}</small></div>
    <a class="btn btn-sec" href="{{ route('bienes.show', $bien) }}">Volver</a>
</div>
<div class="aviso info">El código patrimonial no se edita. El ambiente, el responsable y el estado solo cambian mediante movimientos con documento de sustento, para conservar el historial.</div>
<form method="post" action="{{ route('bienes.update', $bien) }}" class="tarjeta">
    @csrf @method('PUT')
    <div class="form-grid">
        @include('partials.campo', ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'req' => true, 'valor' => $bien->descripcion, 'clase' => 'completo'])
        @include('partials.campo', ['nombre' => 'marca', 'etiqueta' => 'Marca', 'valor' => $bien->marca])
        @include('partials.campo', ['nombre' => 'modelo', 'etiqueta' => 'Modelo', 'valor' => $bien->modelo])
        @include('partials.campo', ['nombre' => 'serie', 'etiqueta' => 'N.° de serie', 'valor' => $bien->serie])
        @include('partials.campo', ['nombre' => 'codigo_catalogo_siga', 'etiqueta' => 'Código de catálogo SIGA', 'valor' => $bien->codigo_catalogo_siga])
        @include('partials.campo', ['nombre' => 'fecha_ingreso', 'etiqueta' => 'Fecha de ingreso', 'tipo' => 'date', 'valor' => $bien->fecha_ingreso?->toDateString()])
    </div>
    <div class="acciones"><button class="btn btn-primario">Guardar</button></div>
</form>
@endsection
