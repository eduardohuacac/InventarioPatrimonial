@extends('layouts.app')
@section('titulo', $bien->codigo_patrimonial)
@section('contenido')
@php($u = auth()->user())
<div class="cabecera">
    <div>
        <h1><span class="codigo">{{ $bien->codigo_patrimonial }}</span> · {{ $bien->descripcion }}</h1>
        @include('partials.situacion', ['s' => $bien->situacion])
        @if ($bien->prestamoVencido())<span class="etq danger">Préstamo vencido</span>@endif
    </div>
    <div class="acciones no-imprimir">
        @if ($u->veTodosLosBienes())<a class="btn btn-sec" href="{{ route('bienes.index') }}">Volver</a>@else<a class="btn btn-sec" href="{{ route('mis-bienes') }}">Volver</a>@endif
        @can('update', $bien)<a class="btn btn-sec" href="{{ route('bienes.edit', $bien) }}">Editar datos</a>@endcan
    </div>
</div>

<div class="rejilla r2">
    <section class="tarjeta ficha" aria-labelledby="t-situacion">
        <h2 id="t-situacion">Situación actual</h2>
        <dl>
            <dt>Ambiente</dt><dd>{{ $bien->ambiente->nombre }}</dd>
            <dt>Responsable</dt><dd>{{ $bien->responsable->nombres }} <small class="tenue">({{ $bien->responsable->cargo }})</small></dd>
            <dt>Estado de conservación</dt><dd>@include('partials.estado', ['e' => $bien->estado])</dd>
            <dt>Última verificación</dt><dd>{{ $bien->fecha_ultima_verificacion->format('d/m/Y') }}
                @if ($bien->fecha_ultima_verificacion->lte(\App\Services\Alertas::ultimoCierre()))<span class="etq warn">Sin verificar desde el último cierre</span>@endif</dd>
            @if ($bien->situacion === \App\Enums\Situacion::PRESTADO)
                <dt>Devolución prevista</dt><dd>{{ $bien->fecha_prevista_devolucion?->format('d/m/Y') }}</dd>
                @if ($responsablePrevioPrestamo)<dt>Responsable antes del préstamo</dt><dd>{{ $responsablePrevioPrestamo->nombres }}</dd>@endif
            @endif
        </dl>
    </section>
    <section class="tarjeta ficha" aria-labelledby="t-datos">
        <h2 id="t-datos">Datos del bien</h2>
        <dl>
            <dt>Marca / modelo</dt><dd>{{ $bien->marca ?: '—' }} / {{ $bien->modelo ?: '—' }}</dd>
            <dt>N.° de serie</dt><dd>{{ $bien->serie ?: '—' }}</dd>
            <dt>Código catálogo SIGA</dt><dd>{{ $bien->codigo_catalogo_siga ?: '—' }}</dd>
            <dt>Fecha de ingreso</dt><dd>{{ $bien->fecha_ingreso?->format('d/m/Y') ?? '—' }}</dd>
            <dt>Origen del registro</dt><dd>{{ $bien->id_origen ? 'Registro depurado '.$bien->id_origen : 'Registro directo' }}</dd>
            <dt>Registrado por</dt><dd>{{ $bien->creador->name }} <small class="tenue">{{ $bien->created_at->format('d/m/Y H:i') }}</small></dd>
        </dl>
    </section>
</div>

@if ($u->esEncargado())
    <section class="tarjeta no-imprimir" aria-labelledby="t-mov">
        <h2 id="t-mov">Registrar movimiento</h2>
        @if ($tiposDisponibles)
            <div class="acciones">
                @foreach ($tiposDisponibles as $t)
                    <a class="btn {{ $t === \App\Enums\TipoMovimiento::SOLICITUD_BAJA || $t === \App\Enums\TipoMovimiento::BAJA ? 'btn-sec' : 'btn-primario' }}" href="{{ route('movimientos.create', [$bien, strtolower($t->value)]) }}">{{ $t->etiqueta() }}</a>
                @endforeach
            </div>
        @else
            <p class="tenue">No hay movimientos disponibles para un bien en situación «{{ $bien->situacion->etiqueta() }}».</p>
        @endif
    </section>
@endif

<section class="tarjeta" aria-labelledby="t-historial">
    <h2 id="t-historial">Historial ({{ $movimientos->count() }})</h2>
    <p class="tenue" style="margin-top:-.4rem"><small>El historial no se edita ni se borra. Los movimientos anulados por una corrección aprobada se muestran tachados.</small></p>
    <ol class="linea-tiempo">
        @foreach ($movimientos as $m)
            @php($anulado = $m->estaAnulado())
            <li @class(['anulado' => $anulado, 'correccion' => $m->tipo === \App\Enums\TipoMovimiento::CORRECCION])>
                <div class="tit">{{ $m->tipo->etiqueta() }} <small class="tenue">· {{ $m->fecha->format('d/m/Y') }}</small>
                    @if ($anulado)<span class="etq danger">Anulado</span>@endif
                    @if ($m->requiere_aprobacion)
                        @php($ea = $m->estadoAprobacion())
                        <span class="etq {{ ['PENDIENTE' => 'warn', 'APROBADO' => 'ok', 'RECHAZADO' => 'danger'][$ea] }}">{{ ucfirst(strtolower($ea)) }}</span>
                    @endif
                    @if ($m->correcciones->contains(fn ($c) => $c->estaPendiente()))<span class="etq warn">Corrección pendiente</span>@endif
                </div>
                <div class="det">
                    @switch($m->tipo)
                        @case(\App\Enums\TipoMovimiento::REGISTRO)
                            {{ $m->ambienteDestino?->nombre }} · {{ $m->responsableNuevo?->nombres }} · estado {{ $m->estado?->nombre }}@if($m->encontrado === false) · no ubicado @endif
                            @break
                        @case(\App\Enums\TipoMovimiento::ASIGNACION)
                        @case(\App\Enums\TipoMovimiento::PRESTAMO)
                        @case(\App\Enums\TipoMovimiento::DEVOLUCION)
                            Responsable: {{ $m->responsableAnterior?->nombres }} → <b>{{ $m->responsableNuevo?->nombres }}</b>
                            · Ambiente: {{ $m->ambienteOrigen?->nombre }} → <b>{{ $m->ambienteDestino?->nombre }}</b>
                            @if($m->fecha_prevista_devolucion) · devolución prevista {{ $m->fecha_prevista_devolucion->format('d/m/Y') }}@endif
                            @if($m->tipo === \App\Enums\TipoMovimiento::DEVOLUCION) · estado al recibir: {{ $m->estado?->nombre }}@endif
                            @break
                        @case(\App\Enums\TipoMovimiento::TRASLADO)
                            {{ $m->ambienteOrigen?->nombre }} → <b>{{ $m->ambienteDestino?->nombre }}</b>
                            @break
                        @case(\App\Enums\TipoMovimiento::CAMBIO_ESTADO)
                            Nuevo estado: <b>{{ $m->estado?->nombre }}</b>
                            @break
                        @case(\App\Enums\TipoMovimiento::VERIFICACION)
                            @if($m->encontrado) Encontrado en <b>{{ $m->ambienteDestino?->nombre }}</b>, estado {{ $m->estado?->nombre }} @else <b>No ubicado</b> @endif
                            @break
                        @case(\App\Enums\TipoMovimiento::CORRECCION)
                            Anula el movimiento #{{ $m->movimiento_anulado_id }}
                            @break
                    @endswitch
                    @if ($m->motivo)<br>Motivo: {{ $m->motivo }}@endif
                    @if ($m->observacion)<br>Obs.: {{ $m->observacion }}@endif
                    <br>
                    @if ($m->documento)
                        Sustento: {{ $m->documento->rotulo() }}
                        @if ($m->documento->archivo_path) · <a href="{{ route('documentos.archivo', $m->documento) }}">ver archivo</a>@endif ·
                    @endif
                    Registró {{ $m->registrador->name }} el {{ $m->registrado_en->format('d/m/Y H:i') }} · #{{ $m->id }}
                    @if ($m->aprobacion)<br>{{ $m->aprobacion->decision === 'APROBADO' ? 'Aprobó' : 'Rechazó' }} {{ $m->aprobacion->director->name }} el {{ $m->aprobacion->decidido_en->format('d/m/Y H:i') }}@if($m->aprobacion->observacion): «{{ $m->aprobacion->observacion }}»@endif @endif
                    @if ($u->esEncargado() && ! $anulado && ! in_array($m->tipo, [\App\Enums\TipoMovimiento::REGISTRO, \App\Enums\TipoMovimiento::CORRECCION]) && ! $m->estaPendiente())
                        · <a href="{{ route('correcciones.create', $m) }}" class="no-imprimir">Corregir</a>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</section>
@endsection
