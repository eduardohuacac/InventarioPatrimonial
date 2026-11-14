@extends('layouts.app')
@section('titulo', 'Ambientes')
@section('contenido')
<div class="cabecera"><div><h1>Ambientes</h1></div><a class="btn btn-primario" href="{{ route('admin.ambientes.create') }}">+ Nuevo ambiente</a></div>
<div class="tarjeta"><div class="tabla-contenedor"><table>
    <thead><tr><th>Nombre</th><th>Tipo</th><th>Piso</th><th class="num">Bienes</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @foreach ($ambientes as $a)
        <tr><td>{{ $a->nombre }}</td><td>{{ \App\Models\Ambiente::TIPOS[$a->tipo] ?? $a->tipo }}</td><td>{{ $a->piso }}</td><td class="num">{{ $a->bienes_count }}</td>
            <td><span class="etq {{ $a->activo ? 'ok' : 'muted' }}">{{ $a->activo ? 'Activo' : 'Inactivo' }}</span></td><td><a href="{{ route('admin.ambientes.edit', $a) }}">Editar</a></td></tr>
    @endforeach
    </tbody>
</table></div></div>
@endsection
