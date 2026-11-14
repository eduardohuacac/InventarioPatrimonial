@extends('layouts.app')
@section('titulo', 'Diagnóstico de importación')
@section('contenido')
<div class="cabecera">
    <div><h1>Diagnóstico: {{ $nombre }}</h1><small>Nada se ha guardado todavía.</small></div>
    <a class="btn btn-sec" href="{{ route('importacion.index') }}">Cancelar</a>
</div>

<div class="rejilla r4" style="margin-bottom:1.25rem">
    <div class="kpi"><div class="valor">{{ $resumen['filas_origen'] }}</div><div class="etiqueta">Filas de origen</div></div>
    <div class="kpi"><div class="valor">{{ $resumen['excluidas'] }}</div><div class="etiqueta">Excluidas</div></div>
    <div class="kpi"><div class="valor">{{ $resumen['fusionadas'] }} / {{ $resumen['grupos_fusion'] }}</div><div class="etiqueta">Filas fusionadas / grupos</div></div>
    <div class="kpi k-bien"><div class="valor">{{ $resumen['cargados_esperados'] }}</div><div class="etiqueta">Registros que se cargarán</div></div>
</div>

<div class="tarjeta">
    <p style="margin-top:0"><b>Fórmula de reconciliación:</b> {{ $resumen['filas_origen'] }} − {{ $resumen['excluidas'] }} − ({{ $resumen['fusionadas'] }} − {{ $resumen['grupos_fusion'] }}) = <b>{{ $resumen['cargados_esperados'] }}</b></p>
    <p style="margin-bottom:0"><b>Marcas detectadas por el sistema:</b>
        @foreach ($resumen['por_regla'] as $r => $n) <span class="etq {{ $n ? 'warn' : 'muted' }}">{{ $r }}: {{ $n }}</span> @endforeach
        <br><small class="tenue">R1 código repetido · R2 posible duplicado · R3 incompleto · R4 inconsistente · R5 sin código individual</small></p>
</div>

@if ($resumen['con_errores'] > 0)
    <div class="aviso error"><b>{{ $resumen['con_errores'] }} fila(s) con observaciones bloqueantes.</b> Corrija el archivo (o registre los catálogos faltantes) y vuelva a subirlo. Un aparente duplicado no se elimina sin cotejo físico.</div>
@else
    <form method="post" action="{{ route('importacion.confirmar') }}" class="tarjeta" data-confirmar="¿Confirmar la carga de {{ $resumen['cargados_esperados'] }} registros?">
        @csrf
        <div class="aviso ok" style="margin-bottom:.75rem">El archivo no tiene observaciones bloqueantes.</div>
        <button class="btn btn-primario">Confirmar importación</button>
    </form>
@endif

<section class="tarjeta">
    <h2>Detalle por fila</h2>
    <div class="tabla-contenedor"><table>
        <thead><tr><th>Id origen</th><th>Código</th><th>Descripción</th><th>Resultado</th><th>Grupo</th><th>Marcas</th><th>Se carga</th><th>Observaciones</th></tr></thead>
        <tbody>
        @foreach ($filas as $f)
            <tr @if($f['errores']) style="background:var(--danger-bg)" @endif>
                <td>{{ $f['id_origen'] }}</td><td class="codigo">{{ $f['codigo_patrimonial'] }}</td><td>{{ $f['descripcion'] }}</td>
                <td>{{ $f['resultado'] }}</td><td>{{ $f['grupo_fusion'] }}</td>
                <td>@foreach($f['marcas'] as $mk)<span class="etq warn">{{ $mk }}</span> @endforeach</td>
                <td>{{ $f['carga'] ? 'Sí' : 'No' }}</td>
                <td>@foreach($f['errores'] as $e)<div>{{ $e }}</div>@endforeach</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endsection
