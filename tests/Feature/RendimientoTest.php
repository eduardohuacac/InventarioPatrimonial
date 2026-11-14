<?php

namespace Tests\Feature;

use App\Models\Ambiente;
use App\Models\Personal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\CreaEscenario;
use Tests\TestCase;

/** RNF05: consulta de un bien en menos de 3 segundos con 5 000 bienes. */
#[Group('rendimiento')]
class RendimientoTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    public function test_rnf05_consulta_con_5000_bienes_en_menos_de_3_segundos(): void
    {
        $this->prepararCatalogos();
        $ambientes = Ambiente::factory()->count(20)->create()->pluck('id');
        $personas = Personal::factory()->count(40)->create()->pluck('id');
        // En MySQL/MariaDB los ids no se reinician entre pruebas: no asumir que empiezan en 1.
        $estados = \App\Models\EstadoConservacion::orderBy('id')->pluck('id');

        $ahora = now();
        foreach (array_chunk(range(1, 5000), 500) as $bloque) {
            DB::table('bienes')->insert(array_map(fn ($i) => [
                'codigo_patrimonial' => sprintf('74088037%04d', $i),
                'descripcion' => 'Bien de prueba '.$i,
                'ambiente_actual_id' => $ambientes[$i % 20],
                'responsable_actual_id' => $personas[$i % 40],
                'estado_actual_id' => $estados[$i % 3],
                'situacion' => 'activo',
                'fecha_ultima_verificacion' => '2026-06-01',
                'creado_por' => $this->encargado->id,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], $bloque));
        }
        $this->assertSame(5000, DB::table('bienes')->count());

        $mediciones = [];
        foreach (['/bienes?q=740880374321', '/bienes/740880372500', '/bienes?q=prueba+49', '/'] as $ruta) {
            $inicio = microtime(true);
            $r = $this->actingAs($this->encargado)->get($ruta);
            $mediciones[$ruta] = round((microtime(true) - $inicio) * 1000);
            $this->assertContains($r->status(), [200, 302]);
            $this->assertLessThan(3000, $mediciones[$ruta], "{$ruta} tardó {$mediciones[$ruta]} ms");
        }
        fwrite(STDERR, "\nRNF05 tiempos (ms): ".json_encode($mediciones)."\n");
    }
}
