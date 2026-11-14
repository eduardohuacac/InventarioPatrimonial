@extends('errors.layout')
@section('codigo', '429')
@section('mensaje', $exception?->getMessage() && in_array(429, [403, 410]) ? $exception->getMessage() : 'Demasiadas solicitudes. Espere un momento.')
