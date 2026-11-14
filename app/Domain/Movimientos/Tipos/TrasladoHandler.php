<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/** Traslado entre ambientes de la institución. Sustento: papeleta de desplazamiento. */
class TrasladoHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::TRASLADO;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::ACTIVO, Situacion::PRESTADO];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::PAPELETA_DESPLAZAMIENTO];
    }

    public function reglas(SituacionBien $actual): array
    {
        return ['ambiente_destino_id' => ['required', 'exists:ambientes,id']];
    }

    public function validarContexto(SituacionBien $actual, array $datos): void
    {
        $this->exigir(
            (int) $datos['ambiente_destino_id'] !== $actual->ambienteId,
            'ambiente_destino_id',
            'El ambiente de destino debe ser distinto del ambiente actual.'
        );
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_origen_id' => $actual->ambienteId,
            'ambiente_destino_id' => $datos['ambiente_destino_id'],
            'responsable_anterior_id' => $actual->responsableId,
            'responsable_nuevo_id' => $actual->responsableId,
            'estado_id' => $actual->estadoId,
        ];
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s->con(['ambienteId' => $m->ambiente_destino_id]);
    }
}
