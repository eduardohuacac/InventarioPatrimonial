<?php

namespace Tests\Unit;

use App\Domain\Movimientos\FabricaHandlers;
use App\Domain\Movimientos\ProyectorSituacion;
use App\Enums\Situacion;
use App\Enums\TipoMovimiento;
use App\Models\Movimiento;
use App\Services\Alertas;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/** Pruebas unitarias puras (sin base de datos) del motor de movimientos. */
class ProyectorSituacionTest extends TestCase
{
    private function mov(int $id, TipoMovimiento $tipo, array $attr = []): Movimiento
    {
        $m = new Movimiento;
        $m->setRawAttributes(array_merge(['id' => $id, 'tipo' => $tipo->value, 'fecha' => '2026-11-0'.min($id, 9), 'requiere_aprobacion' => 0], $attr));
        $m->setRelation('aprobacion', null);

        return $m;
    }

    public function test_prestamo_y_devolucion(): void
    {
        $p = new ProyectorSituacion(new FabricaHandlers);
        $s = $p->proyectar(collect([
            $this->mov(1, TipoMovimiento::REGISTRO, ['ambiente_destino_id' => 1, 'responsable_nuevo_id' => 10, 'estado_id' => 1, 'encontrado' => 1]),
            $this->mov(2, TipoMovimiento::PRESTAMO, ['ambiente_destino_id' => 2, 'responsable_nuevo_id' => 20, 'fecha_prevista_devolucion' => '2026-11-20']),
        ]));
        $this->assertSame(Situacion::PRESTADO, $s->situacion);
        $this->assertSame(20, $s->responsableId);
        $this->assertSame('2026-11-20', $s->fechaPrevistaDevolucion->toDateString());

        $s2 = $p->proyectar(collect([
            $this->mov(1, TipoMovimiento::REGISTRO, ['ambiente_destino_id' => 1, 'responsable_nuevo_id' => 10, 'estado_id' => 1, 'encontrado' => 1]),
            $this->mov(2, TipoMovimiento::PRESTAMO, ['ambiente_destino_id' => 2, 'responsable_nuevo_id' => 20, 'fecha_prevista_devolucion' => '2026-11-20']),
            $this->mov(3, TipoMovimiento::DEVOLUCION, ['ambiente_destino_id' => 2, 'responsable_nuevo_id' => 30, 'estado_id' => 2]),
        ]));
        $this->assertSame(Situacion::ACTIVO, $s2->situacion);
        $this->assertSame(30, $s2->responsableId);
        $this->assertNull($s2->fechaPrevistaDevolucion);
        $this->assertSame('2026-11-03', $s2->fechaUltimaVerificacion->toDateString());
    }

    public function test_correccion_pendiente_no_anula(): void
    {
        $p = new ProyectorSituacion(new FabricaHandlers);
        $s = $p->proyectar(collect([
            $this->mov(1, TipoMovimiento::REGISTRO, ['ambiente_destino_id' => 1, 'responsable_nuevo_id' => 10, 'estado_id' => 1, 'encontrado' => 1]),
            $this->mov(2, TipoMovimiento::TRASLADO, ['ambiente_destino_id' => 5]),
            $this->mov(3, TipoMovimiento::CORRECCION, ['movimiento_anulado_id' => 2, 'requiere_aprobacion' => 1]),
        ]));
        $this->assertSame(5, $s->ambienteId);
    }

    public function test_ultimo_cierre_de_inventario(): void
    {
        $this->assertSame('2025-12-31', Alertas::ultimoCierre(Carbon::parse('2026-11-14'))->toDateString());
        $this->assertSame('2026-12-31', Alertas::ultimoCierre(Carbon::parse('2026-12-31'))->toDateString());
        $this->assertSame('2025-12-31', Alertas::ultimoCierre(Carbon::parse('2026-01-01'))->toDateString());
    }
}
