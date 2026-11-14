@extends('layouts.app')
@section('titulo', 'Lote '.$lote->id)
@section('contenido')
<div class="cabecera">
    <div><h1>Lote #{{ $lote->id }} · {{ $lote->archivo_nombre }}</h1><small>Importado por {{ $lote->usuario->name }} el {{ $lote->importado_en->format('d/m/Y H:i') }} · SHA-256 {{ substr($lote->archivo_hash, 0, 16) }}…</small></div>
    <a class="btn btn-sec" href="{{ route('importacion.index') }}">Volver</a>
</div>

<section class="tarjeta">
    <h2>Reporte de reconciliación</h2>
    <div class="tabla-contenedor"><table style="max-width:560px">
        <tbody>
            <tr><td>Filas de origen</td><td class="num">{{ $lote->filas_origen }}</td></tr>
            <tr><td>Conservadas</td><td class="num">{{ $lote->conservadas }}</td></tr>
            <tr><td>Corregidas</td><td class="num">{{ $lote->corregidas }}</td></tr>
            <tr><td>Fusionadas (en {{ $lote->grupos_fusion }} grupos)</td><td class="num">{{ $lote->fusionadas }}</td></tr>
            <tr><td>Pendientes (cargadas como «no ubicado»)</td><td class="num">{{ $lote->pendientes }}</td></tr>
            <tr><td>Excluidas</td><td class="num">{{ $lote->excluidas }}</td></tr>
            <tr><th>Esperados: {{ $lote->filas_origen }} − {{ $lote->excluidas }} − ({{ $lote->fusionadas }} − {{ $lote->grupos_fusion }})</th><th class="num">{{ $lote->cargadosEsperados() }}</th></tr>
            <tr><th>Registros cargados</th><th class="num">{{ $lote->cargados }}</th></tr>
        </tbody>
    </table></div>
    <p>@if($lote->cuadra())<span class="etq ok">La reconciliación cuadra</span>@else<span class="etq danger">La reconciliación no cuadra</span>@endif
        <small class="tenue">Complemente con una muestra aleatoria cotejada campo por campo.</small></p>
</section>

<section class="tarjeta">
    <h2>Bitácora de migración ({{ $lote->bitacora->count() }})</h2>
    <div class="tabla-contenedor"><table>
        <thead><tr><th>Id origen</th><th>Reglas</th><th>Resultado</th><th>Grupo</th><th>Dato anterior</th><th>Dato nuevo</th><th>Evidencia</th><th>Autorizó</th><th>Fecha</th><th>Bien</th></tr></thead>
        <tbody>
        @foreach ($lote->bitacora as $b)
            <tr><td>{{ $b->id_origen }}</td><td>{{ $b->reglas }}</td><td>{{ $b->resultado }}</td><td>{{ $b->grupo_fusion }}</td>
                <td>{{ $b->dato_anterior }}</td><td>{{ $b->dato_nuevo }}</td><td>{{ $b->evidencia }}</td><td>{{ $b->autorizado_por }}</td>
                <td>{{ $b->fecha?->format('d/m/Y') }}</td>
                <td>@if($b->bien)<a class="codigo" href="{{ route('bienes.show', $b->bien) }}">{{ $b->bien->codigo_patrimonial }}</a>@else — @endif</td></tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endsection
