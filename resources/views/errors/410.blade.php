@extends('errors.layout')
@section('codigo', '410')
@section('mensaje', $exception?->getMessage() && in_array(410, [403, 410]) ? $exception->getMessage() : 'La operación expiró. Vuelva a intentarlo.')
