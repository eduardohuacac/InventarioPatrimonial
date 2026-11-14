<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/** Registro inicial del bien en el sistema (alta interna o importación). */
class RegistroHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::REGISTRO;
    }

    public function situacionesPermitidas(): array
    {
        return [];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::COMPROBANTE_INGRESO, TipoDocumento::ACTA_ENTREGA];
    }

    public function documentoObligatorio(): bool
    {
        return false;
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_destino_id' => $datos['ambiente_id'],
            'responsable_nuevo_id' => $datos['responsable_id'],
            'estado_id' => $datos['estado_id'],
            'observacion' => $datos['observacion'] ?? null,
            'encontrado' => ($datos['situacion_inicial'] ?? 'activo') !== Situacion::NO_UBICADO->value,
        ];
    }

    public function aplicar(SituacionBien $situacion, Movimiento $m): SituacionBien
    {
        return new SituacionBien(
            $m->ambiente_destino_id,
            $m->responsable_nuevo_id,
            $m->estado_id,
            $m->encontrado === false ? Situacion::NO_UBICADO : Situacion::ACTIVO,
            $this->fecha($m->fecha),
        );
    }
}
