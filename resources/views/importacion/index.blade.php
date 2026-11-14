@extends('layouts.app')
@section('titulo', 'Importación de registros depurados')
@section('contenido')
<div class="cabecera">
    <div><h1>Importación de registros depurados</h1><small>Carga los registros ya depurados y verificados físicamente, conservando su identificador de origen y la bitácora de cambios autorizados.</small></div>
</div>

@if (auth()->user()->esEncargado())
<section class="tarjeta">
    <h2>Nueva importación</h2>
    <ol>
        <li>Descargue la <a href="{{ route('importacion.plantilla') }}">plantilla Excel</a> y complete una fila por cada fila del archivo original (incluidas las excluidas).</li>
        <li>Suba el archivo. El sistema aplica las reglas R1–R5 y muestra el diagnóstico <b>sin guardar nada</b>.</li>
        <li>Si no hay observaciones bloqueantes, confirme la carga. Se verifica la fórmula de reconciliación.</li>
    </ol>
    <form method="post" action="{{ route('importacion.previsualizar') }}" enctype="multipart/form-data" class="acciones" style="align-items:end">
        @csrf
        <div class="campo @error('archivo') error @enderror" style="margin:0;min-width:320px">
            <label for="archivo">Archivo CSV o Excel <span class="req">*</span></label>
            <input type="file" id="archivo" name="archivo" accept=".csv,.xlsx,.xls" required>
        </div>
        <button class="btn btn-primario">Previsualizar diagnóstico</button>
    </form>
</section>
@endif

<section class="tarjeta">
    <h2>Lotes importados</h2>
    <div class="tabla-contenedor"><table>
        <thead><tr><th>#</th><th>Archivo</th><th>Fecha</th><th>Usuario</th><th class="num">Filas</th><th class="num">Cargados</th><th>Reconciliación</th><th></th></tr></thead>
        <tbody>
        @forelse ($lotes as $l)
            <tr><td>{{ $l->id }}</td><td>{{ $l->archivo_nombre }}</td><td>{{ $l->importado_en->format('d/m/Y H:i') }}</td><td>{{ $l->usuario->name }}</td>
                <td class="num">{{ $l->filas_origen }}</td><td class="num">{{ $l->cargados }}</td>
                <td>@if($l->cuadra())<span class="etq ok">Cuadra</span>@else<span class="etq danger">No cuadra</span>@endif</td>
                <td><a href="{{ route('importacion.show', $l) }}">Ver bitácora</a></td></tr>
        @empty
            <tr><td colspan="8" class="tenue">Aún no se han importado registros.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
