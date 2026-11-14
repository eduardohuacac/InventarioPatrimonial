<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/**
 * Devolución. NO restituye automáticamente al responsable anterior: parte de
 * los datos VIGENTES y registra lo constatado en la papeleta (quién asume la
 * custodia, ambiente y estado al recibir).
 */
class DevolucionHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::DEVOLUCION;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::PRESTADO];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::PAPELETA_DEVOLUCION];
    }

    public function reglas(SituacionBien $actual): array
    {
        return [
            'responsable_nuevo_id' => ['required', 'exists:personal,id'],
            'ambiente_destino_id' => ['nullable', 'exists:ambientes,id'],
            'estado_id' => ['required', 'exists:estados_conservacion,id'],
        ];
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_origen_id' => $actual->ambienteId,
            // Si no se indica, se mantiene el ambiente VIGENTE (no el anterior al préstamo).
            'ambiente_destino_id' => $datos['ambiente_destino_id'] ?? $actual->ambienteId,
            'responsable_anterior_id' => $actual->responsableId,
            'responsable_nuevo_id' => $datos['responsable_nuevo_id'],
            'estado_id' => $datos['estado_id'],
        ];
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s->con([
            'ambienteId' => $m->ambiente_destino_id,
            'responsableId' => $m->responsable_nuevo_id,
            'estadoId' => $m->estado_id,
            'situacion' => Situacion::ACTIVO,
            'fechaUltimaVerificacion' => $this->fecha($m->fecha),
            'fechaPrevistaDevolucion' => null,
        ]);
    }
}
