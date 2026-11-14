<?php

namespace Tests\Feature;

use App\Enums\Decision;
use App\Enums\Situacion;
use App\Enums\TipoMovimiento;
use App\Exceptions\RegistroInmutableException;
use App\Models\Ambiente;
use App\Models\Movimiento;
use App\Models\Personal;
use App\Services\RegistroBienService;
use App\Services\RegistroMovimientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreaEscenario;
use Tests\TestCase;

/** Casos de prueba CP01–CP07 de la tesis sobre las reglas del negocio. */
class ReglasNegocioTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararCatalogos();
    }

    private function servicio(): RegistroMovimientoService
    {
        return app(RegistroMovimientoService::class);
    }

    /** CP01: registrar un bien sin responsable es rechazado. */
    public function test_cp01_bien_sin_responsable_es_rechazado(): void
    {
        $datos = [
            'codigo_patrimonial' => '740880370001',
            'descripcion' => 'Mesa de madera',
            'ambiente_id' => Ambiente::factory()->create()->id,
            'estado_id' => $this->estado('bueno')->id,
            'fecha_ultima_verificacion' => now()->toDateString(),
        ];

        $v = Validator::make($datos, RegistroBienService::reglas());

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('responsable_id', $v->errors()->toArray());
    }

    /** CP02: registrar un bien con un código existente es rechazado. */
    public function test_cp02_codigo_repetido_es_rechazado(): void
    {
        $this->crearBien(['codigo_patrimonial' => '740880370001']);
        $datos = [
            'codigo_patrimonial' => '740880370001',
            'descripcion' => 'Otra mesa',
            'ambiente_id' => Ambiente::factory()->create()->id,
            'responsable_id' => Personal::factory()->create()->id,
            'estado_id' => $this->estado('bueno')->id,
            'fecha_ultima_verificacion' => now()->toDateString(),
        ];

        $v = Validator::make($datos, RegistroBienService::reglas());

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('codigo_patrimonial', $v->errors()->toArray());
    }

    /** CP03: un préstamo sin papeleta es rechazado. */
    public function test_cp03_prestamo_sin_papeleta_es_rechazado(): void
    {
        $bien = $this->crearBien();

        try {
            $this->servicio()->registrar($bien, TipoMovimiento::PRESTAMO, [
                'fecha' => now()->toDateString(),
                'responsable_nuevo_id' => Personal::factory()->create()->id,
                'fecha_prevista_devolucion' => now()->addDays(7)->toDateString(),
            ], $this->encargado);
            $this->fail('Se esperaba un rechazo por falta de papeleta.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('documento_tipo', $e->errors());
        }

        $this->assertSame(Situacion::ACTIVO, $bien->fresh()->situacion);
        $this->assertSame(1, Movimiento::where('bien_id', $bien->id)->count());
    }

    /** CP04: devolución tras un traslado autorizado durante el préstamo conserva el ambiente vigente. */
    public function test_cp04_devolucion_tras_traslado_mantiene_ambiente_vigente(): void
    {
        $bien = $this->crearBien();
        $ambienteOriginal = $bien->ambiente_actual_id;
        $responsableOriginal = $bien->responsable_actual_id;
        $docente = Personal::factory()->create();
        $laboratorio = Ambiente::factory()->create(['nombre' => 'Laboratorio de Ciencias']);

        $this->movimiento($bien, TipoMovimiento::PRESTAMO, [
            'responsable_nuevo_id' => $docente->id,
            'fecha_prevista_devolucion' => now()->addDays(5)->toDateString(),
        ]);
        $this->movimiento($bien->fresh(), TipoMovimiento::TRASLADO, ['ambiente_destino_id' => $laboratorio->id]);
        $nuevoCustodio = Personal::factory()->create();
        $this->movimiento($bien->fresh(), TipoMovimiento::DEVOLUCION, [
            'responsable_nuevo_id' => $nuevoCustodio->id,
            'estado_id' => $this->estado('regular')->id,
        ]);

        $bien->refresh();
        $this->assertSame($laboratorio->id, $bien->ambiente_actual_id);
        $this->assertNotSame($ambienteOriginal, $bien->ambiente_actual_id);
        $this->assertSame($nuevoCustodio->id, $bien->responsable_actual_id);
        $this->assertNotSame($responsableOriginal, $bien->responsable_actual_id);
        $this->assertSame(Situacion::ACTIVO, $bien->situacion);
        $this->assertSame($this->estado('regular')->id, $bien->estado_actual_id);
    }

    /** CP05: un movimiento no se puede editar ni borrar; solo corregir. */
    public function test_cp05_movimiento_no_se_puede_editar_ni_borrar(): void
    {
        $bien = $this->crearBien();
        $mov = $this->movimiento($bien, TipoMovimiento::TRASLADO, ['ambiente_destino_id' => Ambiente::factory()->create()->id]);

        try {
            $mov->update(['observacion' => 'cambiado']);
            $this->fail('Se esperaba que la edición fuera bloqueada.');
        } catch (RegistroInmutableException) {
        }

        $this->expectException(RegistroInmutableException::class);
        $mov->fresh()->delete();
    }

    /** CP05 (b): la corrección aprobada anula el movimiento y ambos quedan visibles. */
    public function test_cp05_correccion_aprobada_restituye_situacion_y_conserva_historial(): void
    {
        $bien = $this->crearBien();
        $ambienteOriginal = $bien->ambiente_actual_id;
        $erroneo = $this->movimiento($bien, TipoMovimiento::TRASLADO, ['ambiente_destino_id' => Ambiente::factory()->create()->id]);

        $correccion = $this->servicio()->registrarCorreccion($erroneo, [
            'fecha' => now()->toDateString(),
            'motivo' => 'Se registró el traslado en el bien equivocado.',
        ], $this->encargado);

        // Pendiente: aún no cambia la situación.
        $this->assertNotSame($ambienteOriginal, $bien->fresh()->ambiente_actual_id);

        $this->servicio()->decidir($correccion, Decision::APROBADO, null, $this->director);

        $bien->refresh();
        $this->assertSame($ambienteOriginal, $bien->ambiente_actual_id);
        $this->assertSame(3, Movimiento::where('bien_id', $bien->id)->count());
        $this->assertTrue($erroneo->fresh()->estaAnulado());
    }

    /** CP05 (c): al corregir se muestran las operaciones posteriores. */
    public function test_cp05_correccion_muestra_operaciones_posteriores(): void
    {
        $bien = $this->crearBien();
        $erroneo = $this->movimiento($bien, TipoMovimiento::TRASLADO, ['ambiente_destino_id' => Ambiente::factory()->create()->id]);
        $posterior = $this->movimiento($bien->fresh(), TipoMovimiento::CAMBIO_ESTADO, ['estado_id' => $this->estado('malo')->id]);

        $lista = $this->servicio()->posteriores($erroneo);

        $this->assertCount(1, $lista);
        $this->assertSame($posterior->id, $lista->first()->id);
    }

    /** CP07: la solicitud de baja deja el bien «en trámite de baja» y no desaparece. */
    public function test_cp07_solicitud_de_baja_no_retira_el_bien(): void
    {
        $bien = $this->crearBien();

        $solicitud = $this->movimiento($bien, TipoMovimiento::SOLICITUD_BAJA, ['motivo' => 'Daño irreparable según informe técnico.']);

        $bien->refresh();
        $this->assertSame(Situacion::EN_TRAMITE_BAJA, $bien->situacion);
        $this->assertDatabaseHas('bienes', ['id' => $bien->id]);
        $this->assertTrue($solicitud->estaPendiente());

        // Si la Dirección la rechaza, el bien vuelve a estar activo.
        $this->servicio()->decidir($solicitud, Decision::RECHAZADO, 'Reparable', $this->director);
        $this->assertSame(Situacion::ACTIVO, $bien->fresh()->situacion);
    }

    public function test_baja_solo_con_solicitud_aprobada(): void
    {
        $bien = $this->crearBien();
        $solicitud = $this->movimiento($bien, TipoMovimiento::SOLICITUD_BAJA, ['motivo' => 'Daño irreparable según informe técnico.']);

        try {
            $this->movimiento($bien->fresh(), TipoMovimiento::BAJA, []);
            $this->fail('La baja no debe registrarse sin aprobación.');
        } catch (ValidationException) {
        }

        $this->servicio()->decidir($solicitud, Decision::APROBADO, null, $this->director);
        $this->assertSame(Situacion::EN_TRAMITE_BAJA, $bien->fresh()->situacion);

        $this->movimiento($bien->fresh(), TipoMovimiento::BAJA, []);
        $this->assertSame(Situacion::DADO_DE_BAJA, $bien->fresh()->situacion);
    }

    public function test_no_se_presta_un_bien_ya_prestado(): void
    {
        $bien = $this->crearBien();
        $this->movimiento($bien, TipoMovimiento::PRESTAMO, [
            'responsable_nuevo_id' => Personal::factory()->create()->id,
            'fecha_prevista_devolucion' => now()->addDays(3)->toDateString(),
        ]);

        $this->expectException(ValidationException::class);
        $this->movimiento($bien->fresh(), TipoMovimiento::PRESTAMO, [
            'responsable_nuevo_id' => Personal::factory()->create()->id,
            'fecha_prevista_devolucion' => now()->addDays(3)->toDateString(),
        ]);
    }

    public function test_verificacion_no_encontrado_marca_no_ubicado_y_luego_reaparece(): void
    {
        $bien = $this->crearBien();
        $this->movimiento($bien, TipoMovimiento::VERIFICACION, ['encontrado' => 0]);
        $this->assertSame(Situacion::NO_UBICADO, $bien->fresh()->situacion);

        $this->movimiento($bien->fresh(), TipoMovimiento::VERIFICACION, ['encontrado' => 1, 'estado_id' => $this->estado('regular')->id]);
        $bien->refresh();
        $this->assertSame(Situacion::ACTIVO, $bien->situacion);
        $this->assertSame(now()->toDateString(), $bien->fecha_ultima_verificacion->toDateString());
    }

    public function test_fecha_de_movimiento_no_puede_ser_anterior_al_ultimo(): void
    {
        $bien = $this->crearBien(['fecha_ultima_verificacion' => now()->subDays(2)->toDateString()]);

        $this->expectException(ValidationException::class);
        $this->movimiento($bien, TipoMovimiento::TRASLADO, [
            'fecha' => now()->subDays(5)->toDateString(),
            'ambiente_destino_id' => Ambiente::factory()->create()->id,
        ]);
    }
}
