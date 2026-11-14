@extends('layouts.app')
@section('titulo', 'Reportes')
@section('contenido')
<div class="cabecera"><div><h1>Reportes</h1><small>Consulte y exporte a PDF o Excel.</small></div></div>
<form method="get" class="tarjeta">
    <div class="filtros">
        <div><label for="tipo">Reporte</label>
            <select id="tipo" name="tipo">@foreach($tipos as $k => $t)<option value="{{ $k }}" @selected($filtros['tipo'] === $k)>{{ $t }}</option>@endforeach</select></div>
        <div><label for="ambiente_id">Ambiente</label><select id="ambiente_id" name="ambiente_id"><option value="">Todos</option>@foreach($ambientes as $a)<option value="{{ $a->id }}" @selected(($filtros['ambiente_id'] ?? '') == $a->id)>{{ $a->nombre }}</option>@endforeach</select></div>
        <div><label for="responsable_id">Responsable</label><select id="responsable_id" name="responsable_id"><option value="">Todos</option>@foreach($personal as $p)<option value="{{ $p->id }}" @selected(($filtros['responsable_id'] ?? '') == $p->id)>{{ $p->nombres }}</option>@endforeach</select></div>
        <div><label for="estado_id">Estado</label><select id="estado_id" name="estado_id"><option value="">Todos</option>@foreach($estados as $e)<option value="{{ $e->id }}" @selected(($filtros['estado_id'] ?? '') == $e->id)>{{ ucfirst($e->nombre) }}</option>@endforeach</select></div>
        <div><label for="situacion">Situación</label><select id="situacion" name="situacion"><option value="">Vigentes</option>@foreach($situaciones as $s)<option value="{{ $s->value }}" @selected(($filtros['situacion'] ?? '') === $s->value)>{{ $s->etiqueta() }}</option>@endforeach</select></div>
        <div class="acciones"><button class="btn btn-primario">Ver</button></div>
    </div>
</form>

@if ($reporte)
    <section class="tarjeta">
        <div class="cabecera">
            <div><h2 style="margin:0">{{ $reporte['titulo'] }}</h2><small>{{ $reporte['subtitulo'] }} · {{ $reporte['total'] }} bienes</small></div>
            <div class="acciones">
                <a class="btn btn-sec" href="{{ route('reportes.exportar', array_merge($filtros, ['formato' => 'pdf'])) }}">Exportar PDF</a>
                <a class="btn btn-sec" href="{{ route('reportes.exportar', array_merge($filtros, ['formato' => 'xlsx'])) }}">Exportar Excel</a>
            </div>
        </div>
        @forelse ($reporte['grupos'] as $grupo => $filas)
            <h3>{{ $grupo }} <small class="tenue">({{ count($filas) }})</small></h3>
            <div class="tabla-contenedor" style="margin-bottom:1rem"><table>
                <thead><tr>@foreach($reporte['columnas'] as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
                <tbody>@foreach($filas as $f)<tr>@foreach($f as $i => $v)<td @class(['codigo' => $i === 0])>{{ $v }}</td>@endforeach</tr>@endforeach</tbody>
            </table></div>
        @empty
            <p class="tenue">No hay bienes que cumplan los criterios.</p>
        @endforelse
    </section>
@endif
@endsection
