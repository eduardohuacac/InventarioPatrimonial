<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/**
 * Corrección: anula un movimiento erróneo (no lo edita). Requiere motivo y la
 * aprobación de la Dirección. No modifica la situación por sí misma: el
 * proyector excluye el movimiento anulado al recalcular.
 */
class CorreccionHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::CORRECCION;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::ACTIVO, Situacion::PRESTADO, Situacion::EN_TRAMITE_BAJA, Situacion::NO_UBICADO];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::INFORME];
    }

    public function documentoObligatorio(): bool
    {
        return false;
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
            'movimiento_anulado_id' => $datos['movimiento_anulado_id'],
            'motivo' => $datos['motivo'],
        ];
    }

    public function seAplica(string $estadoAprobacion): bool
    {
        return false;
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s;
    }
}
