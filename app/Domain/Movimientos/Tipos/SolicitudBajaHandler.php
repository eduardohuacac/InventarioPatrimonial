<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/**
 * Solicitud de baja: el bien queda «en trámite de baja» (no se retira) y
 * espera la aprobación de la Dirección. Si se rechaza, vuelve a su situación previa.
 */
class SolicitudBajaHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::SOLICITUD_BAJA;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::ACTIVO, Situacion::NO_UBICADO];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::INFORME_TECNICO];
    }

    public function requiereAprobacion(): bool
    {
        return true;
    }

    public function reglas(SituacionBien $actual): array
    {
        return ['motivo' => ['required', 'string', 'min:10', 'max:1000']];
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_origen_id' => $actual->ambienteId,
            'responsable_anterior_id' => $actual->responsableId,
            'estado_id' => $actual->estadoId,
            'motivo' => $datos['motivo'],
        ];
    }

    public function seAplica(string $estadoAprobacion): bool
    {
        return in_array($estadoAprobacion, ['PENDIENTE', 'APROBADO'], true);
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s->con(['situacion' => Situacion::EN_TRAMITE_BAJA]);
    }
}
