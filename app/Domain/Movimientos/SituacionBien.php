<?php

namespace App\Domain\Movimientos;

use App\Enums\Situacion;
use App\Models\Bien;
use Carbon\CarbonImmutable;

/**
 * Objeto de valor inmutable con la situación vigente de un bien.
 * Cada tipo de movimiento produce una nueva SituacionBien a partir de la anterior.
 */
final class SituacionBien
{
    public function __construct(
        public readonly ?int $ambienteId,
        public readonly ?int $responsableId,
        public readonly ?int $estadoId,
        public readonly Situacion $situacion,
        public readonly ?CarbonImmutable $fechaUltimaVerificacion,
        public readonly ?CarbonImmutable $fechaPrevistaDevolucion = null,
    ) {}

    public static function vacia(): self
    {
        return new self(null, null, null, Situacion::ACTIVO, null);
    }

    public static function desdeBien(Bien $bien): self
    {
        return new self(
            $bien->ambiente_actual_id,
            $bien->responsable_actual_id,
            $bien->estado_actual_id,
            $bien->situacion,
            $bien->fecha_ultima_verificacion?->toImmutable(),
            $bien->fecha_prevista_devolucion?->toImmutable(),
        );
    }

    public function con(array $cambios): self
    {
        return new self(
            array_key_exists('ambienteId', $cambios) ? $cambios['ambienteId'] : $this->ambienteId,
            array_key_exists('responsableId', $cambios) ? $cambios['responsableId'] : $this->responsableId,
            array_key_exists('estadoId', $cambios) ? $cambios['estadoId'] : $this->estadoId,
            $cambios['situacion'] ?? $this->situacion,
            array_key_exists('fechaUltimaVerificacion', $cambios) ? $cambios['fechaUltimaVerificacion'] : $this->fechaUltimaVerificacion,
            array_key_exists('fechaPrevistaDevolucion', $cambios) ? $cambios['fechaPrevistaDevolucion'] : $this->fechaPrevistaDevolucion,
        );
    }

    /** Atributos para guardar en la tabla bienes. */
    public function comoAtributosDeBien(): array
    {
        return [
            'ambiente_actual_id' => $this->ambienteId,
            'responsable_actual_id' => $this->responsableId,
            'estado_actual_id' => $this->estadoId,
            'situacion' => $this->situacion,
            'fecha_ultima_verificacion' => $this->fechaUltimaVerificacion?->toDateString(),
            'fecha_prevista_devolucion' => $this->fechaPrevistaDevolucion?->toDateString(),
        ];
    }
}
