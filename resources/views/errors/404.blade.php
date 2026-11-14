@extends('errors.layout')
@section('codigo', '404')
@section('mensaje', $exception?->getMessage() && in_array(404, [403, 410]) ? $exception->getMessage() : 'La página o el registro no existe.')
