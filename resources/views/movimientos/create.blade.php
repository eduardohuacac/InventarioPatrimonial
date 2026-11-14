@extends('layouts.app')
@section('titulo', $tipoMov->etiqueta())
@section('contenido')
@php($T = \App\Enums\TipoMovimiento::class)
<div class="cabecera">
    <div><h1>{{ $tipoMov->etiqueta() }}</h1><small><span class="codigo">{{ $bien->codigo_patrimonial }}</span> · {{ $bien->descripcion }}</small></div>
    <a class="btn btn-sec" href="{{ route('bienes.show', $bien) }}">Volver a la ficha</a>
</div>

<div class="aviso info">
    <b>Situación vigente:</b> {{ $bien->ambiente->nombre }} · {{ $bien->responsable->nombres }} · estado {{ $bien->estado->nombre }} · {{ $bien->situacion->etiqueta() }}
</div>

@unless ($permitido)
    <div class="aviso error">No se puede registrar «{{ $tipoMov->etiqueta() }}» porque el bien está «{{ $bien->situacion->etiqueta() }}».</div>
@else
<form method="post" action="{{ route('movimientos.store', [$bien, strtolower($tipoMov->value)]) }}" enctype="multipart/form-data" class="tarjeta" novalidate>
    @csrf
    <fieldset>
        <legend>Datos del movimiento</legend>
        <div class="form-grid">
            @include('partials.campo', ['nombre' => 'fecha', 'etiqueta' => 'Fecha del movimiento', 'tipo' => 'date', 'req' => true, 'valor' => today()->toDateString(), 'max' => today()->toDateString()])

            @if (in_array($tipoMov, [$T::ASIGNACION, $T::TRASLADO]))
                @include('partials.campo', ['nombre' => 'ambiente_destino_id', 'etiqueta' => 'Ambiente de destino', 'tipo' => 'select', 'req' => true, 'opciones' => $ambientes->pluck('nombre', 'id')])
            @endif
            @if ($tipoMov === $T::ASIGNACION)
                @include('partials.campo', ['nombre' => 'responsable_nuevo_id', 'etiqueta' => 'Nuevo responsable', 'tipo' => 'select', 'req' => true, 'valor' => $bien->responsable_actual_id, 'opciones' => $personal->pluck('nombres', 'id')])
            @endif

            @if ($tipoMov === $T::PRESTAMO)
                @include('partials.campo', ['nombre' => 'responsable_nuevo_id', 'etiqueta' => 'Prestado a (responsable temporal)', 'tipo' => 'select', 'req' => true, 'opciones' => $personal->where('id', '!=', $bien->responsable_actual_id)->pluck('nombres', 'id')])
                @include('partials.campo', ['nombre' => 'fecha_prevista_devolucion', 'etiqueta' => 'Fecha prevista de devolución', 'tipo' => 'date', 'req' => true])
                @include('partials.campo', ['nombre' => 'ambiente_destino_id', 'etiqueta' => 'Ambiente donde se usará', 'tipo' => 'select', 'vacio' => '— El mismo: '.$bien->ambiente->nombre.' —', 'opciones' => $ambientes->pluck('nombre', 'id')])
            @endif

            @if ($tipoMov === $T::DEVOLUCION)
                <div class="completo aviso warn" style="margin-bottom:1rem">La devolución <b>no</b> restituye automáticamente al responsable anterior. Confirme con la papeleta quién asume la custodia, el ambiente y el estado al recibir.
                    @if ($previoPrestamo)<br><small>Responsable antes del préstamo: {{ $previoPrestamo->nombres }}.</small>@endif</div>
                @include('partials.campo', ['nombre' => 'responsable_nuevo_id', 'etiqueta' => 'Quién asume la custodia', 'tipo' => 'select', 'req' => true, 'opciones' => $personal->pluck('nombres', 'id')])
                @include('partials.campo', ['nombre' => 'ambiente_destino_id', 'etiqueta' => 'Ambiente', 'tipo' => 'select', 'req' => true, 'valor' => $bien->ambiente_actual_id, 'opciones' => $ambientes->pluck('nombre', 'id'), 'ayuda' => 'Por defecto, el ambiente vigente.'])
                @include('partials.campo', ['nombre' => 'estado_id', 'etiqueta' => 'Estado constatado al recibir', 'tipo' => 'select', 'req' => true, 'valor' => $bien->estado_actual_id, 'opciones' => $estados->mapWithKeys(fn ($e) => [$e->id => ucfirst($e->nombre)])])
            @endif

            @if ($tipoMov === $T::CAMBIO_ESTADO)
                @include('partials.campo', ['nombre' => 'estado_id', 'etiqueta' => 'Nuevo estado de conservación', 'tipo' => 'select', 'req' => true, 'opciones' => $estados->where('id', '!=', $bien->estado_actual_id)->mapWithKeys(fn ($e) => [$e->id => ucfirst($e->nombre)])])
            @endif

            @if ($tipoMov === $T::VERIFICACION)
                <div class="campo completo">
                    <span style="font-weight:600;font-size:.88rem">¿Se encontró el bien? <span class="req">*</span></span>
                    <label class="check"><input type="radio" name="encontrado" value="1" @checked(old('encontrado', '1') === '1')> Sí, se constató físicamente</label>
                    <label class="check"><input type="radio" name="encontrado" value="0" @checked(old('encontrado') === '0')> No se encontró (quedará «no ubicado», no se borra)</label>
                </div>
                <div id="bloque-encontrado" class="completo form-grid">
                    @include('partials.campo', ['nombre' => 'estado_id', 'etiqueta' => 'Estado constatado', 'tipo' => 'select', 'valor' => $bien->estado_actual_id, 'opciones' => $estados->mapWithKeys(fn ($e) => [$e->id => ucfirst($e->nombre)])])
                    @include('partials.campo', ['nombre' => 'ambiente_destino_id', 'etiqueta' => 'Ambiente donde se encontró', 'tipo' => 'select', 'valor' => $bien->ambiente_actual_id, 'opciones' => $ambientes->pluck('nombre', 'id')])
                </div>
            @endif

            @if ($tipoMov === $T::SOLICITUD_BAJA)
                <div class="completo aviso warn" style="margin-bottom:1rem">El bien quedará «en trámite de baja» (no se retira) hasta que la Dirección apruebe o rechace la solicitud. La baja definitiva la aprueba la instancia competente.</div>
                @include('partials.campo', ['nombre' => 'motivo', 'etiqueta' => 'Causal / motivo de la solicitud', 'tipo' => 'textarea', 'req' => true, 'clase' => 'completo'])
            @endif

            @if ($tipoMov === $T::BAJA)
                <div class="completo aviso warn" style="margin-bottom:1rem">Registre la baja solo con la resolución de la instancia competente (MEF, 2021, art. 47). El bien quedará «dado de baja» y conservará todo su historial.</div>
            @endif

            @include('partials.campo', ['nombre' => 'observacion', 'etiqueta' => 'Observación', 'clase' => 'completo', 'maxlength' => 255])
        </div>
    </fieldset>

    <fieldset>
        <legend>Documento de sustento @if($handler->documentoObligatorio())<span class="req">*</span>@endif</legend>
        <div class="form-grid">
            @include('partials.campo', ['nombre' => 'documento_tipo', 'etiqueta' => 'Tipo de documento', 'tipo' => 'select', 'req' => $handler->documentoObligatorio(),
                'valor' => count($handler->documentosAceptados()) === 1 ? $handler->documentosAceptados()[0]->value : null,
                'opciones' => collect($handler->documentosAceptados())->mapWithKeys(fn ($d) => [$d->value => $d->etiqueta()])])
            @include('partials.campo', ['nombre' => 'documento_numero', 'etiqueta' => 'Número', 'req' => $handler->documentoObligatorio(), 'maxlength' => 40, 'ayuda' => 'Ej.: 015-2026'])
            @include('partials.campo', ['nombre' => 'documento_fecha', 'etiqueta' => 'Fecha del documento', 'tipo' => 'date', 'max' => today()->toDateString()])
            <div class="campo @error('archivo') error @enderror">
                <label for="f-archivo">Archivo escaneado (opcional)</label>
                <input type="file" id="f-archivo" name="archivo" accept=".pdf,.jpg,.jpeg,.png">
                <div class="ayuda">PDF, JPG o PNG, máximo 5 MB.</div>
                @error('archivo')<div class="msg-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </fieldset>

    <div class="acciones">
        <button class="btn btn-primario" type="submit">Registrar {{ strtolower($tipoMov->etiqueta()) }}</button>
        <a class="btn btn-sec" href="{{ route('bienes.show', $bien) }}">Cancelar</a>
    </div>
</form>
@endunless
@endsection
