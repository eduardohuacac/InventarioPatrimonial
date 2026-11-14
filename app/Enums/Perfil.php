<?php

namespace App\Enums;

enum Perfil: string
{
    case ADMIN = 'ADMIN';
    case DIRECTOR = 'DIRECTOR';
    case ENCARGADO = 'ENCARGADO';
    case CONSULTA = 'CONSULTA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador del sistema',
            self::DIRECTOR => 'Director(a)',
            self::ENCARGADO => 'Encargado(a) de patrimonio',
            self::CONSULTA => 'Personal (consulta)',
        };
    }
}
