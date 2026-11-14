<?php

namespace App\Services\Importacion\Reglas;

/** R3 Incompleto: falta alguno de los 6 campos obligatorios. */
class R3Incompleto implements ReglaCalidad
{
    public const OBLIGATORIOS = ['codigo_patrimonial', 'descripcion', 'ambiente', 'responsable', 'estado', 'fecha_verificacion'];

    public function codigo(): string
    {
        return 'R3';
    }

    public function evaluar(int $indice, array $fila, ContextoDiagnostico $ctx): array
    {
        $faltan = array_filter(self::OBLIGATORIOS, fn ($c) => empty($fila[$c]));

        return [
            'marcar' => $faltan !== [],
            'errores' => $faltan ? ['Faltan: '.implode(', ', $faltan).'.'] : [],
        ];
    }
}
