@extends('layouts.app')
@section('titulo', 'Corregir movimiento')
@section('contenido')
<div class="cabecera">
    <div><h1>Corregir movimiento #{{ $movimiento->id }}</h1><small><span class="codigo">{{ $movimiento->bien->codigo_patrimonial }}</span> · {{ $movimiento->bien->descripcion }}</small></div>
    <a class="btn btn-sec" href="{{ route('bienes.show', $movimiento->bien) }}">Volver a la ficha</a>
</div>

<div class="aviso info">Un movimiento erróneo <b>no se edita</b>: se registra una corrección que lo anula, con motivo, responsable y fecha. Ambos quedan visibles en el historial. La corrección se aplica cuando la Dirección la aprueba.</div>

<section class="tarjeta">
    <h2>Movimiento a anular</h2>
    <p><b>{{ $movimiento->tipo->etiqueta() }}</b> del {{ $movimiento->fecha->format('d/m/Y') }}
        @if ($movimiento->ambienteDestino) · ambiente {{ $movimiento->ambienteOrigen?->nombre }} → {{ $movimiento->ambienteDestino->nombre }}@endif
        @if ($movimiento->responsableNuevo) · responsable {{ $movimiento->responsableAnterior?->nombres }} → {{ $movimiento->responsableNuevo->nombres }}@endif
        @if ($movimiento->estado) · estado {{ $movimiento->estado->nombre }}@endif
        @if ($movimiento->documento)<br><small>Sustento: {{ $movimiento->documento->rotulo() }}</small>@endif
    </p>
</section>

@if ($posteriores->isNotEmpty())
    <section class="tarjeta" style="border-left:4px solid #f59e0b">
        <h2>Operaciones posteriores ({{ $posteriores->count() }})</h2>
        <p>Estos movimientos se registraron después del que va a corregir. Revise si también deben corregirse; al anular el movimiento, la situación del bien se recalcula con los que queden vigentes.</p>
        <ul>
            @foreach ($posteriores as $p)
                <li>#{{ $p->id }} · {{ $p->tipo->etiqueta() }} del {{ $p->fecha->format('d/m/Y') }} @if($p->documento)({{ $p->documento->rotulo() }})@endif</li>
            @endforeach
        </ul>
    </section>
@endif

@if ($error)
    <div class="aviso error">{{ $error }}</div>
@else
<form method="post" action="{{ route('correcciones.store', $movimiento) }}" enctype="multipart/form-data" class="tarjeta" data-confirmar="¿Registrar la corrección? Quedará pendiente de aprobación de la Dirección.">
    @csrf
    <div class="form-grid">
        @include('partials.campo', ['nombre' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'req' => true, 'valor' => today()->toDateString(), 'max' => today()->toDateString()])
        <div></div>
        @include('partials.campo', ['nombre' => 'motivo', 'etiqueta' => 'Motivo de la corrección', 'tipo' => 'textarea', 'req' => true, 'clase' => 'completo', 'ayuda' => 'Mínimo 10 caracteres. Explique por qué el movimiento es erróneo.'])
        @include('partials.campo', ['nombre' => 'documento_tipo', 'etiqueta' => 'Documento (opcional)', 'tipo' => 'select', 'vacio' => '— Sin documento —', 'opciones' => [\App\Enums\TipoDocumento::INFORME->value => \App\Enums\TipoDocumento::INFORME->etiqueta()]])
        @include('partials.campo', ['nombre' => 'documento_numero', 'etiqueta' => 'Número'])
    </div>
    <div class="acciones"><button class="btn btn-primario">Registrar corrección</button></div>
</form>
@endif
@endsection
