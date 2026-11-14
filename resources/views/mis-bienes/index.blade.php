@extends('layouts.app')
@section('titulo', 'Mis bienes')
@section('contenido')
<div class="cabecera">
    <div><h1>Mis bienes</h1><small>Bienes que están actualmente bajo su responsabilidad.</small></div>
</div>
@if (! $personal)
    <div class="aviso warn">Su usuario no está vinculado a una persona del personal. Pida al administrador que lo vincule.</div>
@else
    <div class="tarjeta">
        <p style="margin-top:0"><b>{{ $personal->nombres }}</b> · {{ $personal->cargo }}</p>
        <form method="get" class="acciones" style="margin-bottom:1rem">
            <label for="q" class="salto">Buscar</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Buscar por código o descripción" style="max-width:360px">
            <button class="btn btn-sec">Buscar</button>
        </form>
        <div class="tabla-contenedor">
        <table>
            <thead><tr><th>Código</th><th>Descripción</th><th>Ambiente</th><th>Estado</th><th>Situación</th><th>Últ. verificación</th></tr></thead>
            <tbody>
            @forelse ($bienes as $b)
                <tr>
                    <td><a class="codigo" href="{{ route('bienes.show', $b) }}">{{ $b->codigo_patrimonial }}</a></td>
                    <td>{{ $b->descripcion }} <small>{{ $b->marca }} {{ $b->modelo }}</small></td>
                    <td>{{ $b->ambiente->nombre }}</td>
                    <td>@include('partials.estado', ['e' => $b->estado])</td>
                    <td>@include('partials.situacion', ['s' => $b->situacion])</td>
                    <td>{{ $b->fecha_ultima_verificacion->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="tenue">No tiene bienes a su cargo.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        {{ $bienes->links() }}
        <p class="tenue" style="margin-bottom:0"><small>Si algún dato no coincide con la realidad, comuníquelo al encargado de patrimonio.</small></p>
    </div>
@endif
@endsection
