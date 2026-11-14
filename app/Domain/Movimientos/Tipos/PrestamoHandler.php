<?php

namespace App\Domain\Movimientos\Tipos;

use App\Domain\Movimientos\SituacionBien;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/** Préstamo interno: responsable temporal y fecha prevista de devolución. */
class PrestamoHandler extends HandlerBase
{
    public function tipo(): TipoMovimiento
    {
        return TipoMovimiento::PRESTAMO;
    }

    public function situacionesPermitidas(): array
    {
        return [Situacion::ACTIVO];
    }

    public function documentosAceptados(): array
    {
        return [TipoDocumento::PAPELETA_PRESTAMO];
    }

    public function reglas(SituacionBien $actual): array
    {
        return [
            'responsable_nuevo_id' => ['required', 'exists:personal,id'],
            'ambiente_destino_id' => ['nullable', 'exists:ambientes,id'],
            'fecha_prevista_devolucion' => ['required', 'date', 'after:fecha'],
        ];
    }

    public function validarContexto(SituacionBien $actual, array $datos): void
    {
        $this->exigir(
            (int) $datos['responsable_nuevo_id'] !== $actual->responsableId,
            'responsable_nuevo_id',
            'El bien no se puede prestar a su propio responsable.'
        );
    }

    public function construir(SituacionBien $actual, array $datos): array
    {
        return [
            'ambiente_origen_id' => $actual->ambienteId,
            'ambiente_destino_id' => $datos['ambiente_destino_id'] ?? $actual->ambienteId,
            'responsable_anterior_id' => $actual->responsableId,
            'responsable_nuevo_id' => $datos['responsable_nuevo_id'],
            'estado_id' => $actual->estadoId,
            'fecha_prevista_devolucion' => $datos['fecha_prevista_devolucion'],
        ];
    }

    public function aplicar(SituacionBien $s, Movimiento $m): SituacionBien
    {
        return $s->con([
            'ambienteId' => $m->ambiente_destino_id,
            'responsableId' => $m->responsable_nuevo_id,
            'situacion' => Situacion::PRESTADO,
            'fechaPrevistaDevolucion' => $this->fecha($m->fecha_prevista_devolucion),
        ]);
    }
}
