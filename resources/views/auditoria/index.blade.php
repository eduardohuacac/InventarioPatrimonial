@extends('layouts.app')
@section('titulo', 'Auditoría')
@section('contenido')
<div class="cabecera"><div><h1>Auditoría</h1><small>Quién hizo qué y cuándo. Registro de solo inserción.</small></div></div>
<form method="get" class="tarjeta">
    <div class="filtros" style="grid-template-columns: repeat(4, 1fr) auto">
        <div><label for="usuario_id">Usuario</label><select id="usuario_id" name="usuario_id"><option value="">Todos</option>@foreach($usuarios as $us)<option value="{{ $us->id }}" @selected(($filtros['usuario_id'] ?? '') == $us->id)>{{ $us->name }}</option>@endforeach</select></div>
        <div><label for="accion">Acción</label><select id="accion" name="accion"><option value="">Todas</option>@foreach($acciones as $a)<option value="{{ $a }}" @selected(($filtros['accion'] ?? '') === $a)>{{ str_replace('_', ' ', $a) }}</option>@endforeach</select></div>
        <div><label for="desde">Desde</label><input type="date" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}"></div>
        <div><label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"></div>
        <div class="acciones"><button class="btn btn-primario">Filtrar</button></div>
    </div>
</form>
<div class="tarjeta">
    <div class="tabla-contenedor"><table>
        <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Detalle</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($registros as $r)
            <tr>
                <td style="white-space:nowrap">{{ $r->fecha_hora->format('d/m/Y H:i:s') }}</td>
                <td>{{ $r->usuario?->name ?? '—' }}</td>
                <td>{{ str_replace('_', ' ', $r->accion) }}</td>
                <td>{{ $r->entidad }} @if($r->entidad_id)#{{ $r->entidad_id }}@endif</td>
                <td><small style="word-break:break-word">{{ $r->detalle ? \Illuminate\Support\Str::limit(json_encode($r->detalle, JSON_UNESCAPED_UNICODE), 160) : '' }}</small></td>
                <td><small>{{ $r->ip }}</small></td>
            </tr>
        @empty
            <tr><td colspan="6" class="tenue">Sin registros.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $registros->links() }}
</div>
@endsection
