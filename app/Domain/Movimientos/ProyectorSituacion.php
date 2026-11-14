<?php

namespace App\Domain\Movimientos;

use App\Models\Bien;
use App\Models\Movimiento;
use Illuminate\Support\Collection;

/**
 * Reconstruye la situación vigente de un bien recorriendo su historial en
 * orden (event sourcing simplificado). Así una corrección aprobada se refleja
 * sin editar ningún movimiento: el anulado simplemente se omite.
 */
class ProyectorSituacion
{
    public function __construct(private readonly FabricaHandlers $fabrica) {}

    public function proyectar(Collection $movimientos): SituacionBien
    {
        $anulados = $movimientos
            ->filter(fn (Movimiento $m) => $m->movimiento_anulado_id !== null && $m->estadoAprobacion() === 'APROBADO')
            ->pluck('movimiento_anulado_id')
            ->all();

        $situacion = SituacionBien::vacia();

        foreach ($movimientos->sortBy('id') as $movimiento) {
            if (in_array($movimiento->id, $anulados, true)) {
                continue;
            }
            $handler = $this->fabrica->para($movimiento->tipo);
            if ($handler->seAplica($movimiento->estadoAprobacion())) {
                $situacion = $handler->aplicar($situacion, $movimiento);
            }
        }

        return $situacion;
    }

    /** Recalcula y guarda la situación vigente del bien. */
    public function sincronizar(Bien $bien): Bien
    {
        $movimientos = Movimiento::with('aprobacion')->where('bien_id', $bien->id)->orderBy('id')->get();
        $bien->forceFill($this->proyectar($movimientos)->comoAtributosDeBien())->save();

        return $bien->refresh();
    }
}
