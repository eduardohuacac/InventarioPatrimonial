<?php

namespace App\Services\Importacion\Reglas;

/** R5 Sin código individual: el código es de un tipo de artículo del catálogo SIGA. */
class R5SinCodigoIndividual implements ReglaCalidad
{
    public function codigo(): string
    {
        return 'R5';
    }

    public function evaluar(int $indice, array $fila, ContextoDiagnostico $ctx): array
    {
        $codigo = ContextoDiagnostico::codigo($fila);
        $catalogo = $fila['codigo_catalogo_siga'] ? mb_strtoupper($fila['codigo_catalogo_siga']) : null;
        $marcar = $codigo !== null && $codigo === $catalogo;

        return [
            'marcar' => $marcar,
            'errores' => $marcar ? ['El código corresponde al catálogo SIGA, no a un bien concreto.'] : [],
        ];
    }
}
