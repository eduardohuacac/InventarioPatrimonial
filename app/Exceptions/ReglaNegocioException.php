<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/** Violación de una regla del negocio; se muestra al usuario como error de formulario. */
class ReglaNegocioException extends ValidationException
{
    public static function en(string $campo, string $mensaje): static
    {
        return static::withMessages([$campo => $mensaje]);
    }
}
