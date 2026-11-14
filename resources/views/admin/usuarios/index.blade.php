@extends('layouts.app')
@section('titulo', 'Usuarios')
@section('contenido')
<div class="cabecera"><div><h1>Usuarios</h1><small>Perfiles: Director, Encargado de patrimonio, Personal (consulta) y Administrador.</small></div>
    <a class="btn btn-primario" href="{{ route('admin.usuarios.create') }}">+ Nuevo usuario</a></div>
<div class="tarjeta"><div class="tabla-contenedor"><table>
    <thead><tr><th>Nombre</th><th>Correo</th><th>Perfil</th><th>Vinculado a</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @foreach ($usuarios as $us)
        <tr><td>{{ $us->name }}</td><td>{{ $us->email }}</td><td>{{ $us->perfil->etiqueta() }}</td><td>{{ $us->personal?->nombres ?? '—' }}</td>
            <td>{{ $us->ultimo_acceso?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
            <td><span class="etq {{ $us->activo ? 'ok' : 'muted' }}">{{ $us->activo ? 'Activo' : 'Inactivo' }}</span></td>
            <td><a href="{{ route('admin.usuarios.edit', $us) }}">Editar</a></td></tr>
    @endforeach
    </tbody>
</table></div></div>
@endsection
