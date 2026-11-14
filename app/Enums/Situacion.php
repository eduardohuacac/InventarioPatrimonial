<?php

namespace App\Enums;

enum Situacion: string
{
    case ACTIVO = 'activo';
    case PRESTADO = 'prestado';
    case EN_TRAMITE_BAJA = 'en_tramite_baja';
    case NO_UBICADO = 'no_ubicado';
    case DADO_DE_BAJA = 'dado_de_baja';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::PRESTADO => 'Prestado',
            self::EN_TRAMITE_BAJA => 'En trámite de baja',
            self::NO_UBICADO => 'No ubicado',
            self::DADO_DE_BAJA => 'Dado de baja',
        };
    }

    public function claseCss(): string
    {
        return match ($this) {
            self::ACTIVO => 'ok',
            self::PRESTADO => 'info',
            self::EN_TRAMITE_BAJA => 'warn',
            self::NO_UBICADO => 'danger',
            self::DADO_DE_BAJA => 'muted',
        };
    }
}
