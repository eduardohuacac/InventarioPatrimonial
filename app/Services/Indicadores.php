<?php

namespace App\Services;

use App\Enums\Situacion;
use App\Enums\TipoMovimiento;
use App\Models\Bien;
use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores de calidad de la información (Tabla 5 de la tesis) calculados
 * sobre los datos del sistema.
 */
class Indicadores
{
    public function calcular(): array
    {
        $vigentes = Bien::where('situacion', '!=', Situacion::DADO_DE_BAJA->value);
        $total = (clone $vigentes)->count();
        $cierre = Alertas::ultimoCierre()->toDateString();

        $completos = (clone $vigentes)
            ->whereNotNull('codigo_patrimonial')->where('descripcion', '!=', '')
            ->whereNotNull('ambiente_actual_id')->whereNotNull('responsable_actual_id')
            ->whereNotNull('estado_actual_id')->whereNotNull('fecha_ultima_verificacion')
            ->count();

        $actualizados = (clone $vigentes)->where(function ($q) use ($cierre) {
            $q->whereDate('fecha_ultima_verificacion', '>', $cierre)
                ->orWhereExists(function ($s) use ($cierre) {
                    $s->select(DB::raw(1))->from('movimientos')
                        ->whereColumn('movimientos.bien_id', 'bienes.id')
                        ->where('movimientos.tipo', '!=', TipoMovimiento::REGISTRO->value)
                        ->whereDate('movimientos.fecha', '>', $cierre);
                });
        })->count();

        $codigosRepetidos = Bien::select('codigo_patrimonial')->groupBy('codigo_patrimonial')
            ->havingRaw('COUNT(*) > 1')->get()->count();

        $movs = Movimiento::whereNotIn('tipo', [TipoMovimiento::REGISTRO->value, TipoMovimiento::CORRECCION->value]);
        $totalMovs = (clone $movs)->count();
        $conSustento = (clone $movs)->whereNotNull('documento_id')->count();

        $pct = fn (int $a, int $b) => $b > 0 ? round($a * 100 / $b, 1) : null;

        return [
            'total' => $total,
            'completitud' => $pct($completos, $total),
            'unicidad' => $codigosRepetidos,
            'actualidad' => $pct($actualizados, $total),
            'movimientos_con_sustento' => $pct($conSustento, $totalMovs),
            'total_movimientos' => $totalMovs,
            'cierre' => $cierre,
        ];
    }
}
