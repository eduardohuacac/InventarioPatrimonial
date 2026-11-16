<?php

namespace Tests\Feature;

use App\Enums\Situacion;
use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\EstadoConservacion;
use App\Models\Movimiento;
use App\Models\Personal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pruebas de integración de extremo a extremo sobre la interfaz web. */
class FlujosWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function usuario(string $correo): User
    {
        return User::where('email', $correo)->firstOrFail();
    }

    /** Regresión: todas las pantallas responden para cada perfil. */
    public function test_todas_las_pantallas_responden_por_perfil(): void
    {
        $bien = Bien::first();
        $lote = null;
        $rutas = [
            'patrimonio@ie50023.edu.pe' => ['/', '/bienes', '/bienes-nuevo', "/bienes/{$bien->codigo_patrimonial}", "/bienes/{$bien->codigo_patrimonial}/editar",
                "/bienes/{$bien->codigo_patrimonial}/movimientos/traslado", '/aprobaciones', '/alertas', '/reportes', '/reportes?tipo=inventario',
                '/reportes?tipo=prestamos_vencidos', '/importacion', '/mis-bienes', '/perfil/clave',
                '/movimientos/'.Movimiento::where('tipo', 'TRASLADO')->first()->id.'/corregir'],
            'director@ie50023.edu.pe' => ['/', '/bienes', '/aprobaciones', '/alertas', '/reportes?tipo=estado', '/auditoria', '/importacion'],
            'admin@ie50023.edu.pe' => ['/', '/admin/usuarios', '/admin/usuarios/create', '/admin/ambientes', '/admin/ambientes/create', '/admin/personal', '/admin/personal/create', '/auditoria'],
            'rquispe@ie50023.edu.pe' => ['/mis-bienes', '/perfil/clave'],
        ];
        foreach ($rutas as $correo => $lista) {
            foreach ($lista as $ruta) {
                $r = $this->actingAs($this->usuario($correo))->get($ruta);
                $this->assertTrue(in_array($r->status(), [200], true), "{$correo} {$ruta} devolvió {$r->status()}");
            }
        }
        $this->actingAs($this->usuario('rquispe@ie50023.edu.pe'))->get('/')->assertRedirect(route('mis-bienes'));
    }

    public function test_registrar_bien_desde_formulario(): void
    {
        $r = $this->actingAs($this->usuario('patrimonio@ie50023.edu.pe'))->post('/bienes', [
            'codigo_patrimonial' => '952282850099',
            'descripcion' => 'Pizarra interactiva',
            'ambiente_id' => Ambiente::first()->id,
            'responsable_id' => Personal::first()->id,
            'estado_id' => EstadoConservacion::where('nombre', 'bueno')->value('id'),
            'fecha_ultima_verificacion' => today()->toDateString(),
        ]);
        $r->assertRedirect('/bienes/952282850099');
        $this->assertDatabaseHas('movimientos', ['tipo' => 'REGISTRO', 'bien_id' => Bien::where('codigo_patrimonial', '952282850099')->value('id')]);
    }

    public function test_prestamo_con_archivo_de_sustento(): void
    {
        Storage::fake('local');
        $bien = Bien::where('situacion', 'activo')->first();
        $this->actingAs($this->usuario('patrimonio@ie50023.edu.pe'))
            ->post("/bienes/{$bien->codigo_patrimonial}/movimientos/prestamo", [
                'fecha' => today()->toDateString(),
                'responsable_nuevo_id' => Personal::where('id', '!=', $bien->responsable_actual_id)->first()->id,
                'fecha_prevista_devolucion' => today()->addDays(3)->toDateString(),
                'documento_tipo' => 'papeleta_prestamo',
                'documento_numero' => 'PP-900',
                'archivo' => UploadedFile::fake()->create('papeleta.pdf', 120, 'application/pdf'),
            ])->assertRedirect("/bienes/{$bien->codigo_patrimonial}");

        $bien->refresh();
        $this->assertSame(Situacion::PRESTADO, $bien->situacion);
        $doc = Movimiento::where('bien_id', $bien->id)->latest('id')->first()->documento;
        Storage::disk('local')->assertExists($doc->archivo_path);
    }

    public function test_archivo_ejecutable_es_rechazado(): void
    {
        $bien = Bien::where('situacion', 'activo')->first();
        $this->actingAs($this->usuario('patrimonio@ie50023.edu.pe'))
            ->post("/bienes/{$bien->codigo_patrimonial}/movimientos/traslado", [
                'fecha' => today()->toDateString(),
                'ambiente_destino_id' => Ambiente::where('id', '!=', $bien->ambiente_actual_id)->first()->id,
                'documento_tipo' => 'papeleta_desplazamiento',
                'documento_numero' => 'PD-1',
                'archivo' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            ])->assertSessionHasErrors('archivo');
    }

    public function test_director_aprueba_correccion_pendiente_del_demo(): void
    {
        $correccion = Movimiento::pendientesDeAprobacion()->where('tipo', 'CORRECCION')->firstOrFail();
        $bien = $correccion->bien;
        $this->assertSame('Almacén', $bien->ambiente->nombre);

        $this->actingAs($this->usuario('director@ie50023.edu.pe'))
            ->post(route('aprobaciones.decidir', $correccion), ['decision' => 'APROBADO'])
            ->assertRedirect(route('aprobaciones.index'));

        $this->assertNotSame('Almacén', $bien->fresh()->ambiente->nombre);
    }

    public function test_rechazo_exige_observacion(): void
    {
        $sol = Movimiento::pendientesDeAprobacion()->where('tipo', 'SOLICITUD_BAJA')->firstOrFail();
        $this->actingAs($this->usuario('director@ie50023.edu.pe'))
            ->post(route('aprobaciones.decidir', $sol), ['decision' => 'RECHAZADO'])
            ->assertSessionHasErrors('observacion');
    }

    public function test_exportar_reportes_pdf_y_excel(): void
    {
        $u = $this->usuario('director@ie50023.edu.pe');
        $pdf = $this->actingAs($u)->get('/reportes/exportar?tipo=responsable&formato=pdf');
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));

        $xlsx = $this->actingAs($u)->get('/reportes/exportar?tipo=inventario&formato=xlsx');
        $xlsx->assertOk();
        $this->assertDatabaseHas('auditorias', ['accion' => 'exportar_reporte']);
    }

    public function test_alertas_muestran_prestamos_vencidos(): void
    {
        $vencido = Bien::where('situacion', 'prestado')->whereDate('fecha_prevista_devolucion', '<', today())->firstOrFail();
        $this->actingAs($this->usuario('patrimonio@ie50023.edu.pe'))->get('/alertas')
            ->assertOk()->assertSee($vencido->codigo_patrimonial);
    }
}
