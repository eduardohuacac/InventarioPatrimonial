<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/**
 * Verificación física (constatación). Si el bien se encuentra, actualiza la
 * fecha de última verificación y el estado constatado; si no, queda «no ubicado»
 * (nunca se borra).
 */
class VerificacionHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::VERIFICACION;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::ACTIVO, Situacion::PRESTADO, Situacion::NO_UBICADO, Situacion::EN_TRAMITE_BAJA];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::FICHA_VERIFICACION];
    }

    public function reglas(SituacionBien $actual): array
    {
        return [
            'encontrado' => ['required', 'boolean'],
            'estado_id' => ['required_if:encontrado,1', 'nullable', 'exists:estados_conservacion,id'],
            'ambiente_destino_id' => ['nullable', 'exists:ambientes,id'],
        ];
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        $encontrado = (bool) $datos['encontrado'];

        return [
            'encontrado' => $encontrado,
            'ambiente_origen_id' => $actual->ambienteId,
            'ambiente_destino_id' => $encontrado ? ($datos['ambiente_destino_id'] ?? $actual->ambienteId) : $actual->ambienteId,
            'responsable_anterior_id' => $actual->responsableId,
            'responsable_nuevo_id' => $actual->responsableId,
            'estado_id' => $encontrado ? $datos['estado_id'] : $actual->estadoId,
        ];
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        if ($m->encontrado === false) {
            return $s->con(['situacion' => Situacion::NO_UBICADO]);
        }

        return $s->con([
            'ambienteId' => $m->ambiente_destino_id,
            'estadoId' => $m->estado_id,
            'situacion' => $s->situacion === Situacion::NO_UBICADO ? Situacion::ACTIVO : $s->situacion,
            'fechaUltimaVerificacion' => $this->fecha($m->fecha),
        ]);
    }
}
