@extends('layouts.app')
@section('titulo', 'Bienes')
@section('contenido')
<div class="cabecera">
    <div><h1>Bienes</h1><small>Busque por código patrimonial, descripción o serie. Un código exacto abre directamente la ficha.</small></div>
    @if (auth()->user()->esEncargado())
        <div class="acciones"><a class="btn btn-primario" href="{{ route('bienes.create') }}">+ Registrar bien</a></div>
    @endif
</div>
<div class="tarjeta">
    <form method="get" class="filtros" style="margin-bottom:1rem">
        <div><label for="q">Buscar</label><input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Código o descripción"></div>
        <div><label for="ambiente_id">Ambiente</label>
            <select id="ambiente_id" name="ambiente_id"><option value="">Todos</option>@foreach($ambientes as $a)<option value="{{ $a->id }}" @selected(($filtros['ambiente_id'] ?? '') == $a->id)>{{ $a->nombre }}</option>@endforeach</select></div>
        <div><label for="responsable_id">Responsable</label>
            <select id="responsable_id" name="responsable_id"><option value="">Todos</option>@foreach($personal as $p)<option value="{{ $p->id }}" @selected(($filtros['responsable_id'] ?? '') == $p->id)>{{ $p->nombres }}</option>@endforeach</select></div>
        <div><label for="estado_id">Estado</label>
            <select id="estado_id" name="estado_id"><option value="">Todos</option>@foreach($estados as $e)<option value="{{ $e->id }}" @selected(($filtros['estado_id'] ?? '') == $e->id)>{{ ucfirst($e->nombre) }}</option>@endforeach</select></div>
        <div><label for="situacion">Situación</label>
            <select id="situacion" name="situacion"><option value="">Todas</option>@foreach(\App\Enums\Situacion::cases() as $s)<option value="{{ $s->value }}" @selected(($filtros['situacion'] ?? '') === $s->value)>{{ $s->etiqueta() }}</option>@endforeach</select></div>
        <div class="acciones"><button class="btn btn-primario">Filtrar</button><a class="btn btn-sec" href="{{ route('bienes.index') }}">Limpiar</a></div>
    </form>
    <div class="tabla-contenedor">
    <table>
        <thead><tr><th>Código</th><th>Descripción</th><th>Ambiente</th><th>Responsable</th><th>Estado</th><th>Situación</th><th>Últ. verif.</th></tr></thead>
        <tbody>
        @forelse ($bienes as $b)
            <tr>
                <td><a class="codigo" href="{{ route('bienes.show', $b) }}">{{ $b->codigo_patrimonial }}</a></td>
                <td>{{ $b->descripcion }} <small>{{ $b->marca }} {{ $b->modelo }}</small></td>
                <td>{{ $b->ambiente->nombre }}</td>
                <td>{{ $b->responsable->nombres }}</td>
                <td>@include('partials.estado', ['e' => $b->estado])</td>
                <td>@include('partials.situacion', ['s' => $b->situacion]) @if($b->prestamoVencido())<span class="etq danger">Vencido</span>@endif</td>
                <td>{{ $b->fecha_ultima_verificacion->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="tenue">No se encontraron bienes con esos criterios.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $bienes->links() }}
</div>
@endsection
