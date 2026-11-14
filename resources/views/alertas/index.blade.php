@extends('layouts.app')
@section('titulo', 'Alertas')
@section('contenido')
<div class="cabecera">
    <div><h1>Alertas</h1><small>Apoyo para la revisión mensual de préstamos y verificaciones (regla de actualización de la tesis).</small></div>
    <div class="acciones"><a class="btn btn-sec" href="{{ route('reportes.index', ['tipo' => 'inventario']) }}">Preparar inventario anual</a></div>
</div>

<section class="tarjeta">
    <h2>Préstamos vencidos ({{ $vencidos->count() }})</h2>
    <div class="tabla-contenedor"><table>
        <thead><tr><th>Código</th><th>Descripción</th><th>Prestado a</th><th>Ambiente</th><th>Devolución prevista</th><th class="num">Días de atraso</th></tr></thead>
        <tbody>
        @forelse ($vencidos as $b)
            <tr><td><a class="codigo" href="{{ route('bienes.show', $b) }}">{{ $b->codigo_patrimonial }}</a></td><td>{{ $b->descripcion }}</td><td>{{ $b->responsable->nombres }}</td><td>{{ $b->ambiente->nombre }}</td>
                <td>{{ $b->fecha_prevista_devolucion->format('d/m/Y') }}</td><td class="num"><span class="etq danger">{{ (int) $b->fecha_prevista_devolucion->diffInDays(today()) }}</span></td></tr>
        @empty
            <tr><td colspan="6" class="tenue">No hay préstamos vencidos.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>

<section class="tarjeta" id="sin-verificacion">
    <h2>Sin verificación posterior al {{ $cierre->format('d/m/Y') }} ({{ $sinVerificar->total() }})</h2>
    <p class="tenue" style="margin-top:-.4rem">El inventario anual se cierra al 31 de diciembre (RD N.° 0004-2026-EF/54.01). Estos bienes deben verificarse físicamente.</p>
    <div class="tabla-contenedor"><table>
        <thead><tr><th>Código</th><th>Descripción</th><th>Ambiente</th><th>Responsable</th><th>Estado</th><th>Últ. verificación</th></tr></thead>
        <tbody>
        @forelse ($sinVerificar as $b)
            <tr><td><a class="codigo" href="{{ route('bienes.show', $b) }}">{{ $b->codigo_patrimonial }}</a></td><td>{{ $b->descripcion }}</td><td>{{ $b->ambiente->nombre }}</td><td>{{ $b->responsable->nombres }}</td>
                <td>@include('partials.estado', ['e' => $b->estado])</td><td>{{ $b->fecha_ultima_verificacion->format('d/m/Y') }}</td></tr>
        @empty
            <tr><td colspan="6" class="tenue">Todos los bienes tienen verificación reciente.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $sinVerificar->fragment('sin-verificacion')->links() }}
</section>

<section class="tarjeta">
    <h2>Bienes no ubicados ({{ $noUbicados->count() }})</h2>
    <div class="tabla-contenedor"><table>
        <thead><tr><th>Código</th><th>Descripción</th><th>Último ambiente conocido</th><th>Responsable</th></tr></thead>
        <tbody>
        @forelse ($noUbicados as $b)
            <tr><td><a class="codigo" href="{{ route('bienes.show', $b) }}">{{ $b->codigo_patrimonial }}</a></td><td>{{ $b->descripcion }}</td><td>{{ $b->ambiente->nombre }}</td><td>{{ $b->responsable->nombres }}</td></tr>
        @empty
            <tr><td colspan="4" class="tenue">No hay bienes no ubicados.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
