@extends('errors.layout')
@section('codigo', '419')
@section('mensaje', $exception?->getMessage() && in_array(419, [403, 410]) ? $exception->getMessage() : 'La sesión expiró. Vuelva a ingresar.')
