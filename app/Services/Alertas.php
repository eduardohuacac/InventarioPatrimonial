<?php

namespace App\Services;

use App\Enums\Situacion;
use App\Models\Bien;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alertas informativas (RF11, HU09): préstamos vencidos y bienes sin
 * verificación posterior al último cierre de inventario (31/12).
 */
class Alertas
{
    /** Último 31 de diciembre ya cumplido (cierre del inventario anual, RD 0004-2026-EF/54.01). */
    public static function ultimoCierre(?Carbon $hoy = null): Carbon
    {
        $hoy = ($hoy ?? today())->copy();
        $cierre = Carbon::create($hoy->year, 12, 31);

        return $hoy->gte($cierre) ? $cierre : $cierre->subYear();
    }

    public function prestamosVencidos(): Builder
    {
        return Bien::with(['ambiente', 'responsable'])
            ->where('situacion', Situacion::PRESTADO->value)
            ->whereDate('fecha_prevista_devolucion', '<', today())
            ->orderBy('fecha_prevista_devolucion');
    }

    public function sinVerificacionReciente(): Builder
    {
        return Bien::with(['ambiente', 'responsable', 'estado'])
            ->whereNotIn('situacion', [Situacion::DADO_DE_BAJA->value])
            ->whereDate('fecha_ultima_verificacion', '<=', self::ultimoCierre())
            ->orderBy('fecha_ultima_verificacion');
    }

    public function noUbicados(): Builder
    {
        return Bien::with(['ambiente', 'responsable'])->where('situacion', Situacion::NO_UBICADO->value);
    }
}
