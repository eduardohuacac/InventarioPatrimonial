<?php

namespace App\Enums;

enum ResultadoMigracion: string
{
    case CONSERVADA = 'conservada';
    case CORREGIDA = 'corregida';
    case FUSIONADA = 'fusionada';
    case PENDIENTE = 'pendiente';
    case EXCLUIDA = 'excluida';
}
