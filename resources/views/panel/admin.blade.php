@extends('layouts.app')
@section('titulo', 'Panel')
@section('contenido')
<div class="cabecera"><div><h1>Administración del sistema</h1><small>Gestione usuarios, perfiles y catálogos. El administrador no registra movimientos.</small></div></div>
<div class="rejilla r3">
    <a class="kpi" href="{{ route('admin.usuarios.index') }}"><div class="valor">{{ $usuarios }}</div><div class="etiqueta">Usuarios</div></a>
    <a class="kpi" href="{{ route('admin.personal.index') }}"><div class="valor">{{ $personal }}</div><div class="etiqueta">Personal (responsables)</div></a>
    <a class="kpi" href="{{ route('admin.ambientes.index') }}"><div class="valor">{{ $ambientes }}</div><div class="etiqueta">Ambientes</div></a>
</div>
@endsection
