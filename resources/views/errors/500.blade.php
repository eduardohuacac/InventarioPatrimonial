@extends('errors.layout')
@section('codigo', '500')
@section('mensaje', $exception?->getMessage() && in_array(500, [403, 410]) ? $exception->getMessage() : 'Ocurrió un error inesperado. Si persiste, avise al administrador.')
