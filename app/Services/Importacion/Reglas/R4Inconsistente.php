<?php

namespace App\Services\Importacion\Reglas;

use Carbon\Carbon;

/**
 * R4 Inconsistente: ambiente inexistente, estado fuera de catálogo,
 * fecha imposible o responsable que ya no labora.
 */
class R4Inconsistente implements ReglaCalidad
{
    public function codigo(): string
    {
        return 'R4';
    }

    public function evaluar(int $indice, array $fila, ContextoDiagnostico $ctx): array
    {
        $errores = [];
        if ($fila['ambiente'] && ! $ctx->ambienteId($fila['ambiente'])) {
            $errores[] = "Ambiente «{$fila['ambiente']}» no existe en el catálogo.";
        }
        if ($fila['estado'] && ! $ctx->estadoId($fila['estado'])) {
            $errores[] = "Estado «{$fila['estado']}» fuera de catálogo (bueno/regular/malo).";
        }
        if ($fila['responsable'] && ! $ctx->responsableId($fila['responsable'])) {
            $errores[] = "Responsable «{$fila['responsable']}» no existe o ya no labora.";
        }
        if ($fila['fecha_verificacion'] && ! self::fechaPosible($fila['fecha_verificacion'])) {
            $errores[] = "Fecha imposible: «{$fila['fecha_verificacion']}».";
        }

        return ['marcar' => $errores !== [], 'errores' => $errores];
    }

    public static function fechaPosible(string $fecha): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        $f = Carbon::createFromFormat('Y-m-d', $fecha);

        return $f && $f->year >= 1990 && $f->lte(today());
    }
}
