<?php

namespace App\Services\Importacion\Reglas;

/**
 * Regla de calidad de datos (patrón Strategy). Cada regla de la tesis
 * (R1–R5) es una clase independiente, fácil de probar y de extender.
 */
interface ReglaCalidad
{
    public function codigo(): string;

    /**
     * @return array{marcar: bool, errores: string[]} errores = hallazgos que bloquean la carga de la fila
     */
    public function evaluar(int $indice, array $fila, ContextoDiagnostico $ctx): array;
}
