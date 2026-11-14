<?php

namespace App\Domain\Movimientos;

use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;

/**
 * Estrategia (patrón Strategy) que encapsula las reglas de un tipo de movimiento.
 */
interface TipoMovimientoHandler
{
    public function tipo(): TipoMovimiento;

    /** Situaciones del bien en las que se permite este movimiento. */
    public function situacionesPermitidas(): array;

    /** @return TipoDocumento[] Tipos de documento de sustento aceptados. */
    public function documentosAceptados(): array;

    public function documentoObligatorio(): bool;

    public function requiereAprobacion(): bool;

    /** Reglas de validación de Laravel para los datos específicos del tipo. */
    public function reglas(SituacionBien $actual): array;

    /** Reglas que dependen de la situación vigente; lanza ReglaNegocioException. */
    public function validarContexto(SituacionBien $actual, array $datos): void;

    /** Atributos del movimiento a insertar (origen/destino, anterior/nuevo...). */
    public function construir(SituacionBien $actual, array $datos): array;

    /** Función pura: situación resultante al aplicar el movimiento. */
    public function aplicar(SituacionBien $situacion, Movimiento $movimiento): SituacionBien;

    /** ¿Debe aplicarse según su estado de aprobación (NO_REQUIERE|PENDIENTE|APROBADO|RECHAZADO)? */
    public function seAplica(string $estadoAprobacion): bool;
}
