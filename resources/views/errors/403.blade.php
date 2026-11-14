@extends('errors.layout')
@section('codigo', '403')
@section('mensaje', $exception?->getMessage() && in_array(403, [403, 410]) ? $exception->getMessage() : 'No tiene permiso para ver esta página.')
