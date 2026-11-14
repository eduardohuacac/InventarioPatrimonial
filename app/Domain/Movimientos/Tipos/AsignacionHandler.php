<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/** Asignación: cambia ambiente y responsable. Sustento: acta de entrega. */
class AsignacionHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::ASIGNACION;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::ACTIVO];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::ACTA_ENTREGA];
    }

    public function reglas(SituacionBien $actual): array
    {
        return [
            'ambiente_destino_id' => ['required', 'exists:ambientes,id'],
            'responsable_nuevo_id' => ['required', 'exists:personal,id'],
        ];
    }

    public function validarContexto(SituacionBien $actual, array $datos): void
    {
        $this->exigir(
            (int) $datos['ambiente_destino_id'] !== $actual->ambienteId || (int) $datos['responsable_nuevo_id'] !== $actual->responsableId,
            'responsable_nuevo_id',
            'La asignación debe cambiar el ambiente o el responsable.'
        );
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_origen_id' => $actual->ambienteId,
            'ambiente_destino_id' => $datos['ambiente_destino_id'],
            'responsable_anterior_id' => $actual->responsableId,
            'responsable_nuevo_id' => $datos['responsable_nuevo_id'],
            'estado_id' => $actual->estadoId,
        ];
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s->con(['ambienteId' => $m->ambiente_destino_id, 'responsableId' => $m->responsable_nuevo_id]);
    }
}
