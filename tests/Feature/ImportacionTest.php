<?php

namespace Tests\Feature;

use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\BitacoraMigracion;
use App\Models\LoteImportacion;
use App\Models\Personal;
use App\Services\Importacion\DiagnosticoDepuracion;
use App\Services\Importacion\ImportadorDepurados;
use App\Services\Importacion\LectorArchivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreaEscenario;
use Tests\TestCase;

/** RF09 / HU08 / CP08: importación de registros depurados con bitácora y reconciliación. */
class ImportacionTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararCatalogos();
        Ambiente::factory()->create(['nombre' => 'Aula 101']);
        Personal::factory()->create(['nombres' => 'Rosa Quispe Mamani']);
    }

    private function fila(int $i, array $extra = []): array
    {
        return array_merge(array_fill_keys(LectorArchivo::COLUMNAS, null), [
            'id_origen' => sprintf('H1-%03d', $i),
            'codigo_patrimonial' => sprintf('7408803%05d', $i),
            'descripcion' => 'Mesa de madera '.$i,
            'ambiente' => 'Aula 101',
            'responsable' => 'Rosa Quispe Mamani',
            'estado' => 'bueno',
            'fecha_verificacion' => today()->subDays(3)->toDateString(),
            'resultado' => 'conservada',
        ], $extra);
    }

    /** CP08: 100 filas, 5 fusionadas en 2 grupos y 3 excluidas → la fórmula cuadra. */
    public function test_cp08_reconciliacion_cuadra(): void
    {
        $filas = [];
        for ($i = 1; $i <= 100; $i++) {
            $filas[] = $this->fila($i);
        }
        // 3 excluidas (aunque estén incompletas no bloquean)
        foreach ([10, 20, 30] as $i) {
            $filas[$i - 1] = $this->fila($i, ['resultado' => 'excluida', 'responsable' => null]);
        }
        // Grupo G1: 3 filas con el mismo bien registrado tres veces (R1 dentro del grupo)
        foreach ([40, 41, 42] as $i) {
            $filas[$i - 1] = $this->fila($i, ['resultado' => 'fusionada', 'grupo_fusion' => 'G1', 'codigo_patrimonial' => '740880370040']);
        }
        // Grupo G2: 2 filas
        foreach ([50, 51] as $i) {
            $filas[$i - 1] = $this->fila($i, ['resultado' => 'fusionada', 'grupo_fusion' => 'G2', 'codigo_patrimonial' => '740880370050']);
        }
        // 2 pendientes (se cargan como no ubicado)
        $filas[59] = $this->fila(60, ['resultado' => 'pendiente']);
        $filas[60] = $this->fila(61, ['resultado' => 'pendiente']);

        $lote = app(ImportadorDepurados::class)->importar($filas, 'depurado.xlsx', str_repeat('a', 64), $this->encargado);

        // 100 − 3 − (5 − 2) = 94
        $this->assertSame(94, $lote->cargadosEsperados());
        $this->assertSame(94, $lote->cargados);
        $this->assertTrue($lote->cuadra());
        $this->assertSame(94, Bien::count());
        $this->assertSame(100, BitacoraMigracion::count());
        $this->assertSame(2, Bien::where('situacion', 'no_ubicado')->count());
        $this->assertSame('H1-040', Bien::where('codigo_patrimonial', '740880370040')->value('id_origen'));
        $this->assertSame(3, BitacoraMigracion::where('grupo_fusion', 'G1')->whereNotNull('bien_id')->count());
    }

    public function test_diagnostico_marca_reglas_r1_a_r5(): void
    {
        $this->crearBien(['codigo_patrimonial' => '999900000001']);
        $filas = [
            $this->fila(1, ['codigo_patrimonial' => '999900000001']),                      // R1 (existe en BD)
            $this->fila(2, ['serie' => 'S1', 'marca' => 'X']),                                  // R2 con fila 3
            $this->fila(3, ['serie' => 'S1', 'marca' => 'X', 'descripcion' => 'Mesa de madera 2']),
            $this->fila(4, ['responsable' => null]),                                          // R3
            $this->fila(5, ['ambiente' => 'Aula inexistente', 'fecha_verificacion' => '2099-01-01']), // R4
            $this->fila(6, ['codigo_patrimonial' => '74088037', 'codigo_catalogo_siga' => '74088037']), // R5
        ];
        $d = app(DiagnosticoDepuracion::class)->diagnosticar($filas);

        $this->assertContains('R1', $d[0]['marcas']);
        $this->assertContains('R2', $d[1]['marcas']);
        $this->assertContains('R2', $d[2]['marcas']);
        $this->assertContains('R3', $d[3]['marcas']);
        $this->assertContains('R4', $d[4]['marcas']);
        $this->assertCount(2, $d[4]['errores']);
        $this->assertContains('R5', $d[5]['marcas']);
        $this->assertNotEmpty($d[0]['errores']);
    }

    public function test_importacion_con_errores_no_guarda_nada(): void
    {
        $filas = [$this->fila(1), $this->fila(2, ['estado' => 'excelente'])];

        try {
            app(ImportadorDepurados::class)->importar($filas, 'x.csv', str_repeat('b', 64), $this->encargado);
            $this->fail('Se esperaba rechazo');
        } catch (ValidationException) {
        }
        $this->assertSame(0, Bien::count());
        $this->assertSame(0, LoteImportacion::count());
    }

    public function test_mismo_archivo_no_se_importa_dos_veces(): void
    {
        app(ImportadorDepurados::class)->importar([$this->fila(1)], 'a.csv', str_repeat('c', 64), $this->encargado);
        $this->expectException(ValidationException::class);
        app(ImportadorDepurados::class)->importar([$this->fila(2)], 'a.csv', str_repeat('c', 64), $this->encargado);
    }

    public function test_flujo_web_csv_con_punto_y_coma(): void
    {
        $csv = implode(';', LectorArchivo::COLUMNAS)."\n";
        $csv .= "H1-001;740880370001;74088037;Mesa de madera;;;;Aula 101;Rosa Quispe Mamani;Regular;05/11/2026;corregida;;R3;resp. vacío;Rosa Quispe Mamani;Ficha 001;Dirección;06/11/2026\n";
        $csv .= "H1-002;740880370002;74088037;Mesa de madera;;;;Aula 101;Rosa Quispe Mamani;bueno;05/11/2026;excluida;;R2;;;Ficha 001;Dirección;06/11/2026\n";
        $archivo = UploadedFile::fake()->createWithContent('depurado.csv', $csv);

        $this->actingAs($this->encargado)->post(route('importacion.previsualizar'), ['archivo' => $archivo])
            ->assertOk()->assertSee('Confirmar importación')->assertSee('H1-001');

        $this->actingAs($this->encargado)->post(route('importacion.confirmar'))->assertRedirect();
        $this->assertSame(1, Bien::count());
        $this->assertSame('regular', Bien::first()->estado->nombre);
        $this->assertSame(2, BitacoraMigracion::count());
    }

    public function test_plantilla_se_descarga(): void
    {
        $this->actingAs($this->encargado)->get(route('importacion.plantilla'))->assertOk()->assertDownload('plantilla_registros_depurados.xlsx');
    }
}
