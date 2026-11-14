<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/**
 * Registro de la baja aprobada por la instancia competente (MEF, 2021, art. 47).
 * El sistema solo la registra con la resolución; no la tramita.
 */
class BajaHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::BAJA;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::EN_TRAMITE_BAJA];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::RESOLUCION_BAJA];
    }

    public function validarContexto(SituacionBien $actual, array $datos): void
    {
        $this->exigir(
            ($datos['solicitud_aprobada'] ?? false) === true,
            'documento_numero',
            'La solicitud de baja aún no ha sido aprobada por la Dirección.'
        );
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_origen_id' => $actual->ambienteId,
            'responsable_anterior_id' => $actual->responsableId,
            'estado_id' => $actual->estadoId,
        ];
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s->con(['situacion' => Situacion::DADO_DE_BAJA]);
    }
}
