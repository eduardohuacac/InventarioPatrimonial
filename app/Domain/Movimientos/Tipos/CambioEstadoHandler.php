<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/** Cambio del estado de conservación. Sustento: informe o ficha de verificación. */
class CambioEstadoHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::CAMBIO_ESTADO;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::ACTIVO, Situacion::PRESTADO, Situacion::EN_TRAMITE_BAJA];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::INFORME, TipoDocumento::FICHA_VERIFICACION];
    }

    public function reglas(SituacionBien $actual): array
    {
        return ['estado_id' => ['required', 'exists:estados_conservacion,id']];
    }

    public function validarContexto(SituacionBien $actual, array $datos): void
    {
        $this->exigir(
            (int) $datos['estado_id'] !== $actual->estadoId,
            'estado_id',
            'El nuevo estado debe ser distinto del estado actual. Para confirmar el mismo estado registre una verificación física.'
        );
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_origen_id' => $actual->ambienteId,
            'ambiente_destino_id' => $actual->ambienteId,
            'responsable_anterior_id' => $actual->responsableId,
            'responsable_nuevo_id' => $actual->responsableId,
            'estado_id' => $datos['estado_id'],
        ];
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s->con(['estadoId' => $m->estado_id, 'fechaUltimaVerificacion' => $this->fecha($m->fecha)]);
    }
}
