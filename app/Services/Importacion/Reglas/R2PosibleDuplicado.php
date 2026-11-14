<?php

namespace App\Services\Importacion\Reglas;

/**
 * R2 Posible duplicado: coinciden descripción, marca, modelo y serie con
 * códigos distintos. Es informativa: un duplicado no se elimina sin cotejo físico.
 */
class R2PosibleDuplicado implements ReglaCalidad
{
    public function codigo(): string
    {
        return 'R2';
    }

    public function evaluar(int $indice, array $fila, ContextoDiagnostico $ctx): array
    {
        $firma = ContextoDiagnostico::firma($fila);
        if (! $firma) {
            return ['marcar' => false, 'errores' => []];
        }
        $codigos = collect($ctx->porFirma[$firma])
            ->map(fn ($j) => ContextoDiagnostico::codigo($ctx->filas[$j]))
            ->unique();

        return ['marcar' => $codigos->count() > 1, 'errores' => []];
    }
}
