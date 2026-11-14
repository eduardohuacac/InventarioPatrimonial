<?php

namespace App\Enums;

enum TipoDocumento: string
{
    case COMPROBANTE_INGRESO = 'comprobante_ingreso';
    case ACTA_ENTREGA = 'acta_entrega';
    case PAPELETA_PRESTAMO = 'papeleta_prestamo';
    case PAPELETA_DEVOLUCION = 'papeleta_devolucion';
    case PAPELETA_DESPLAZAMIENTO = 'papeleta_desplazamiento';
    case INFORME = 'informe';
    case FICHA_VERIFICACION = 'ficha_verificacion';
    case INFORME_TECNICO = 'informe_tecnico';
    case RESOLUCION_BAJA = 'resolucion_baja';

    public function etiqueta(): string
    {
        return match ($this) {
            self::COMPROBANTE_INGRESO => 'Comprobante de ingreso (PECOSA, guía, acta de donación)',
            self::ACTA_ENTREGA => 'Acta de entrega',
            self::PAPELETA_PRESTAMO => 'Papeleta de préstamo',
            self::PAPELETA_DEVOLUCION => 'Papeleta de devolución',
            self::PAPELETA_DESPLAZAMIENTO => 'Papeleta de desplazamiento',
            self::INFORME => 'Informe',
            self::FICHA_VERIFICACION => 'Ficha de verificación',
            self::INFORME_TECNICO => 'Informe técnico',
            self::RESOLUCION_BAJA => 'Resolución de baja (instancia competente)',
        };
    }
}
