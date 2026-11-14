<?php

namespace App\Models\Concerns;

use App\Exceptions\RegistroInmutableException;

/**
 * Hace que un modelo sea de solo inserción: el historial nunca se
 * sobrescribe ni se borra (regla 7.4 de la tesis, RNF03).
 */
trait SoloInsercion
{
    public static function bootSoloInsercion(): void
    {
        static::updating(function ($modelo) {
            throw new RegistroInmutableException(class_basename($modelo).' es de solo inserción: no se puede modificar.');
        });

        static::deleting(function ($modelo) {
            throw new RegistroInmutableException(class_basename($modelo).' es de solo inserción: no se puede eliminar.');
        });
    }
}
