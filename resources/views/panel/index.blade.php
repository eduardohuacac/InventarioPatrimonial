@extends('layouts.app')
@section('titulo', 'Panel')
@section('contenido')
<div class="cabecera">
    <div><h1>Panel</h1><small>Situación general de los bienes al {{ now()->format('d/m/Y') }}</small></div>
    @if (auth()->user()->esEncargado())
        <div class="acciones"><a class="btn btn-primario" href="{{ route('bienes.create') }}">+ Registrar bien</a></div>
    @endif
</div>

<div class="rejilla r4" style="margin-bottom:1.25rem">
    <a class="kpi" href="{{ route('bienes.index') }}"><div class="valor">{{ $total }}</div><div class="etiqueta">Bienes registrados</div></a>
    <a class="kpi {{ $totalVencidos ? 'k-alerta' : 'k-bien' }}" href="{{ route('alertas') }}"><div class="valor">{{ $totalVencidos }}</div><div class="etiqueta">Préstamos vencidos</div></a>
    <a class="kpi {{ $sinVerificar ? 'k-aviso' : 'k-bien' }}" href="{{ route('alertas') }}#sin-verificacion"><div class="valor">{{ $sinVerificar }}</div><div class="etiqueta">Sin verificación posterior al {{ $cierre->format('d/m/Y') }}</div></a>
    <a class="kpi {{ $pendientes ? 'k-aviso' : 'k-bien' }}" href="{{ route('aprobaciones.index') }}"><div class="valor">{{ $pendientes }}</div><div class="etiqueta">Pendientes de aprobación</div></a>
</div>

<div class="rejilla r2">
    <section class="tarjeta">
        <h2>Bienes por situación</h2>
        <table>
            <tbody>
            @foreach ($porSituacion as $valor => $s)
                <tr><td><a href="{{ route('bienes.index', ['situacion' => $valor]) }}"><span class="etq {{ $s['css'] }}">{{ $s['etiqueta'] }}</span></a></td><td class="num"><b>{{ $s['total'] }}</b></td></tr>
            @endforeach
            </tbody>
        </table>
        <h3 style="margin-top:1rem">Estado de conservación (bienes vigentes)</h3>
        @php($vig = max(1, $porEstado->sum()))
        @foreach ($porEstado as $nombre => $n)
            <div style="margin-bottom:.5rem">@include('partials.estado', ['e' => $nombre]) <b>{{ $n }}</b> <small>({{ round($n * 100 / $vig) }} %)</small>
                <div class="barra-progreso"><span style="width: {{ round($n * 100 / $vig) }}%"></span></div></div>
        @endforeach
    </section>

    <section class="tarjeta">
        <h2>Calidad de la información</h2>
        <p class="tenue" style="margin-top:-.4rem">Indicadores de la Tabla 5 calculados sobre los datos actuales.</p>
        <table>
            <tbody>
                <tr><td>Completitud (6 campos obligatorios)</td><td class="num"><b>{{ $indicadores['completitud'] ?? '—' }} %</b></td></tr>
                <tr><td>Unicidad (códigos repetidos)</td><td class="num"><b>{{ $indicadores['unicidad'] }}</b></td></tr>
                <tr><td>Actualidad (verificación o movimiento posterior al {{ \Carbon\Carbon::parse($indicadores['cierre'])->format('d/m/Y') }})</td><td class="num"><b>{{ $indicadores['actualidad'] ?? '—' }} %</b></td></tr>
                <tr><td>Movimientos con documento de sustento</td><td class="num"><b>{{ $indicadores['movimientos_con_sustento'] ?? '—' }} %</b> <small>({{ $indicadores['total_movimientos'] }})</small></td></tr>
            </tbody>
        </table>
    </section>
</div>

<div class="rejilla r2">
    <section class="tarjeta">
        <h2>Préstamos vencidos</h2>
        @forelse ($vencidos as $b)
            <p style="margin:.3rem 0"><a class="codigo" href="{{ route('bienes.show', $b) }}">{{ $b->codigo_patrimonial }}</a> {{ $b->descripcion }}<br>
                <small>{{ $b->responsable->nombres }} · debía devolverse el {{ $b->fecha_prevista_devolucion->format('d/m/Y') }}</small></p>
        @empty
            <p class="tenue">No hay préstamos vencidos.</p>
        @endforelse
    </section>
    <section class="tarjeta">
        <h2>Últimos movimientos</h2>
        <table>
            <tbody>
            @forelse ($recientes as $m)
                <tr>
                    <td><small>{{ $m->fecha->format('d/m/Y') }}</small></td>
                    <td><a class="codigo" href="{{ route('bienes.show', $m->bien) }}">{{ $m->bien->codigo_patrimonial }}</a></td>
                    <td>{{ $m->tipo->etiqueta() }}</td>
                    <td><small>{{ $m->registrador->name }}</small></td>
                </tr>
            @empty
                <tr><td class="tenue">Sin movimientos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
