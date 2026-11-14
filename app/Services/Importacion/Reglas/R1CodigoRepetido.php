<?php

namespace App\Services\Importacion\Reglas;

/** R1 Código repetido: mismo código en 2+ filas o ya registrado en el sistema. */
class R1CodigoRepetido implements ReglaCalidad
{
    public function codigo(): string
    {
        return 'R1';
    }

    public function evaluar(int $indice, array $fila, ContextoDiagnostico $ctx): array
    {
        $codigo = ContextoDiagnostico::codigo($fila);
        if (! $codigo) {
            return ['marcar' => false, 'errores' => []];
        }
        $enBd = $ctx->existeEnBd($codigo);

        return [
            'marcar' => $enBd || count($ctx->porCodigo[$codigo]) > 1,
            'errores' => $enBd ? ['El código ya existe en el sistema.'] : [],
        ];
    }
}
