@extends('layouts.app')
@section('titulo', 'Registrar bien')
@section('contenido')
<div class="cabecera">
    <div><h1>Registrar bien</h1><small>Solo bienes que ya tienen código patrimonial asignado por la instancia competente.</small></div>
    <a class="btn btn-sec" href="{{ route('bienes.index') }}">Volver</a>
</div>
<form method="post" action="{{ route('bienes.store') }}" class="tarjeta" novalidate>
    @csrf
    <fieldset>
        <legend>Datos obligatorios</legend>
        <div class="form-grid">
            @include('partials.campo', ['nombre' => 'codigo_patrimonial', 'etiqueta' => 'Código patrimonial', 'req' => true, 'maxlength' => 20, 'ayuda' => 'De 4 a 20 letras, números o guiones. No puede repetirse.'])
            @include('partials.campo', ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'req' => true, 'maxlength' => 255])
            @include('partials.campo', ['nombre' => 'ambiente_id', 'etiqueta' => 'Ambiente', 'tipo' => 'select', 'req' => true, 'opciones' => $ambientes->pluck('nombre', 'id')])
            @include('partials.campo', ['nombre' => 'responsable_id', 'etiqueta' => 'Responsable', 'tipo' => 'select', 'req' => true, 'opciones' => $personal->mapWithKeys(fn ($p) => [$p->id => $p->nombres.' – '.$p->cargo])])
            @include('partials.campo', ['nombre' => 'estado_id', 'etiqueta' => 'Estado de conservación', 'tipo' => 'select', 'req' => true, 'opciones' => $estados->mapWithKeys(fn ($e) => [$e->id => ucfirst($e->nombre)])])
            @include('partials.campo', ['nombre' => 'fecha_ultima_verificacion', 'etiqueta' => 'Fecha de última verificación', 'tipo' => 'date', 'req' => true, 'max' => today()->toDateString()])
        </div>
    </fieldset>
    <fieldset>
        <legend>Datos descriptivos (opcionales)</legend>
        <div class="form-grid">
            @include('partials.campo', ['nombre' => 'marca', 'etiqueta' => 'Marca'])
            @include('partials.campo', ['nombre' => 'modelo', 'etiqueta' => 'Modelo'])
            @include('partials.campo', ['nombre' => 'serie', 'etiqueta' => 'N.° de serie'])
            @include('partials.campo', ['nombre' => 'codigo_catalogo_siga', 'etiqueta' => 'Código de catálogo SIGA', 'ayuda' => 'Tipo de artículo en el catálogo nacional (no identifica al bien).'])
            @include('partials.campo', ['nombre' => 'fecha_ingreso', 'etiqueta' => 'Fecha de ingreso a la I.E.', 'tipo' => 'date', 'max' => today()->toDateString()])
        </div>
    </fieldset>
    <fieldset>
        <legend>Documento de ingreso (recomendado)</legend>
        <div class="form-grid">
            @include('partials.campo', ['nombre' => 'documento_tipo', 'etiqueta' => 'Tipo de documento', 'tipo' => 'select', 'vacio' => '— Sin documento —', 'opciones' => [
                \App\Enums\TipoDocumento::COMPROBANTE_INGRESO->value => \App\Enums\TipoDocumento::COMPROBANTE_INGRESO->etiqueta(),
                \App\Enums\TipoDocumento::ACTA_ENTREGA->value => \App\Enums\TipoDocumento::ACTA_ENTREGA->etiqueta(),
            ]])
            @include('partials.campo', ['nombre' => 'documento_numero', 'etiqueta' => 'Número de documento'])
        </div>
        <p class="ayuda tenue" style="margin-top:0"><small>Regla de actualización: toda adquisición o donación se registra dentro de los 5 días hábiles de recibida.</small></p>
    </fieldset>
    <div class="acciones"><button class="btn btn-primario" type="submit">Registrar bien</button><a class="btn btn-sec" href="{{ route('bienes.index') }}">Cancelar</a></div>
</form>
@endsection
