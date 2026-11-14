<?php

namespace App\Enums;

/**
 * Movimientos permitidos (Tabla 4 de la tesis, con dos extensiones:
 * VERIFICACION para la constatación física y BAJA para registrar la baja
 * aprobada por la instancia competente).
 */
enum TipoMovimiento: string
{
    case REGISTRO = 'REGISTRO';
    case ASIGNACION = 'ASIGNACION';
    case PRESTAMO = 'PRESTAMO';
    case DEVOLUCION = 'DEVOLUCION';
    case TRASLADO = 'TRASLADO';
    case CAMBIO_ESTADO = 'CAMBIO_ESTADO';
    case VERIFICACION = 'VERIFICACION';
    case SOLICITUD_BAJA = 'SOLICITUD_BAJA';
    case BAJA = 'BAJA';
    case CORRECCION = 'CORRECCION';

    public function etiqueta(): string
    {
        return match ($this) {
            self::REGISTRO => 'Registro inicial',
            self::ASIGNACION => 'Asignación',
            self::PRESTAMO => 'Préstamo',
            self::DEVOLUCION => 'Devolución',
            self::TRASLADO => 'Traslado',
            self::CAMBIO_ESTADO => 'Cambio de estado',
            self::VERIFICACION => 'Verificación física',
            self::SOLICITUD_BAJA => 'Solicitud de baja',
            self::BAJA => 'Baja aprobada',
            self::CORRECCION => 'Corrección',
        };
    }

    /** Tipos que el encargado registra desde la ficha del bien. */
    public static function registrables(): array
    {
        return [
            self::ASIGNACION, self::PRESTAMO, self::DEVOLUCION, self::TRASLADO,
            self::CAMBIO_ESTADO, self::VERIFICACION, self::SOLICITUD_BAJA, self::BAJA,
        ];
    }
}
