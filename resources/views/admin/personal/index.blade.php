@extends('layouts.app')
@section('titulo', 'Personal')
@section('contenido')
<div class="cabecera"><div><h1>Personal</h1><small>Personas que pueden ser responsables de bienes. Datos mínimos (Ley N.° 29733).</small></div>
    <a class="btn btn-primario" href="{{ route('admin.personal.create') }}">+ Nueva persona</a></div>
<div class="tarjeta"><div class="tabla-contenedor"><table>
    <thead><tr><th>Nombres</th><th>Cargo</th><th>DNI</th><th class="num">Bienes a cargo</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @foreach ($personal as $p)
        <tr><td>{{ $p->nombres }}</td><td>{{ $p->cargo }}</td><td>{{ $p->dniEnmascarado() ?? '—' }}</td><td class="num">{{ $p->bienes_count }}</td>
            <td><span class="etq {{ $p->activo ? 'ok' : 'muted' }}">{{ $p->activo ? 'Labora' : 'Ya no labora' }}</span></td><td><a href="{{ route('admin.personal.edit', $p) }}">Editar</a></td></tr>
    @endforeach
    </tbody>
</table></div></div>
@endsection
