<?php

namespace App\Domain\Movimientos;

use App\Enums\TipoMovimiento;

/** Fábrica (patrón Factory) que devuelve la estrategia de cada tipo de movimiento. */
class FabricaHandlers
{
    /** @var array<string, TipoMovimientoHandler> */
    private array $cache = [];

    public function para(TipoMovimiento $tipo): TipoMovimientoHandler
    {
        return $this->cache[$tipo->value] ??= match ($tipo) {
            TipoMovimiento::REGISTRO => new Tipos\RegistroHandler,
            TipoMovimiento::ASIGNACION => new Tipos\AsignacionHandler,
            TipoMovimiento::PRESTAMO => new Tipos\PrestamoHandler,
            TipoMovimiento::DEVOLUCION => new Tipos\DevolucionHandler,
            TipoMovimiento::TRASLADO => new Tipos\TrasladoHandler,
            TipoMovimiento::CAMBIO_ESTADO => new Tipos\CambioEstadoHandler,
            TipoMovimiento::VERIFICACION => new Tipos\VerificacionHandler,
            TipoMovimiento::SOLICITUD_BAJA => new Tipos\SolicitudBajaHandler,
            TipoMovimiento::BAJA => new Tipos\BajaHandler,
            TipoMovimiento::CORRECCION => new Tipos\CorreccionHandler,
        };
    }
}
