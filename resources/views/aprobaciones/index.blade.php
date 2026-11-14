@extends('layouts.app')
@section('titulo', 'Aprobaciones')
@section('contenido')
@php($esDirector = auth()->user()->esDirector())
<div class="cabecera">
    <div><h1>Aprobaciones</h1><small>Solicitudes de baja y correcciones del historial que requieren decisión de la Dirección.</small></div>
</div>
@unless ($esDirector)
    <div class="aviso info">Solo la Dirección puede aprobar o rechazar. Aquí puede ver el estado de sus solicitudes.</div>
@endunless

@forelse ($pendientes as $m)
    <article class="tarjeta" style="border-left:4px solid #f59e0b">
        <div class="cabecera" style="margin-bottom:.5rem">
            <div>
                <h2 style="margin:0">{{ $m->tipo->etiqueta() }} · <a class="codigo" href="{{ route('bienes.show', $m->bien) }}">{{ $m->bien->codigo_patrimonial }}</a></h2>
                <small>{{ $m->bien->descripcion }} · {{ $m->bien->ambiente->nombre }} · {{ $m->bien->responsable->nombres }}</small>
            </div>
            <span class="etq warn">Pendiente desde {{ $m->registrado_en->format('d/m/Y') }}</span>
        </div>
        <p style="margin:.3rem 0"><b>Motivo:</b> {{ $m->motivo }}</p>
        @if ($m->documento)<p style="margin:.3rem 0"><b>Sustento:</b> {{ $m->documento->rotulo() }} @if($m->documento->archivo_path)· <a href="{{ route('documentos.archivo', $m->documento) }}">ver archivo</a>@endif</p>@endif
        @if ($m->anulado)
            <p style="margin:.3rem 0"><b>Movimiento que se anularía:</b> #{{ $m->anulado->id }} {{ $m->anulado->tipo->etiqueta() }} del {{ $m->anulado->fecha->format('d/m/Y') }}
                @if($m->anulado->ambienteDestino) · destino {{ $m->anulado->ambienteDestino->nombre }}@endif
                @if($m->anulado->responsableNuevo) · responsable {{ $m->anulado->responsableNuevo->nombres }}@endif</p>
            @if ($m->posteriores->isNotEmpty())
                <div class="aviso warn" style="margin:.5rem 0">Tiene {{ $m->posteriores->count() }} operación(es) posterior(es):
                    @foreach ($m->posteriores as $p) #{{ $p->id }} {{ $p->tipo->etiqueta() }} ({{ $p->fecha->format('d/m/Y') }})@if(! $loop->last), @endif @endforeach</div>
            @endif
        @endif
        <p style="margin:.3rem 0"><small>Registró {{ $m->registrador->name }} el {{ $m->registrado_en->format('d/m/Y H:i') }}</small></p>

        @if ($esDirector)
            <form method="post" action="{{ route('aprobaciones.decidir', $m) }}" class="form-grid" style="margin-top:.75rem">
                @csrf
                <div class="campo completo">
                    <label for="obs-{{ $m->id }}">Observación (obligatoria si rechaza)</label>
                    <input type="text" id="obs-{{ $m->id }}" name="observacion" maxlength="255">
                </div>
                <div class="acciones completo">
                    <button class="btn btn-ok" name="decision" value="APROBADO">Aprobar</button>
                    <button class="btn btn-peligro" name="decision" value="RECHAZADO">Rechazar</button>
                </div>
            </form>
        @endif
    </article>
@empty
    <div class="tarjeta"><p class="tenue" style="margin:0">No hay solicitudes pendientes.</p></div>
@endforelse

<section class="tarjeta">
    <h2>Últimas decisiones</h2>
    <div class="tabla-contenedor">
    <table>
        <thead><tr><th>Fecha</th><th>Bien</th><th>Tipo</th><th>Decisión</th><th>Director(a)</th><th>Observación</th></tr></thead>
        <tbody>
        @forelse ($resueltas as $a)
            <tr>
                <td>{{ $a->decidido_en->format('d/m/Y H:i') }}</td>
                <td><a class="codigo" href="{{ route('bienes.show', $a->movimiento->bien) }}">{{ $a->movimiento->bien->codigo_patrimonial }}</a></td>
                <td>{{ $a->movimiento->tipo->etiqueta() }}</td>
                <td><span class="etq {{ $a->decision === 'APROBADO' ? 'ok' : 'danger' }}">{{ ucfirst(strtolower($a->decision)) }}</span></td>
                <td>{{ $a->director->name }}</td>
                <td>{{ $a->observacion }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="tenue">Sin decisiones registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</section>
@endsection
