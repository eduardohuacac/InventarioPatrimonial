<?php

namespace Tests\Support;

use App\Domain\Movimientos\FabricaHandlers;
use App\Enums\TipoMovimiento;
use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\EstadoConservacion;
use App\Models\Movimiento;
use App\Models\Personal;
use App\Models\User;
use App\Services\RegistroBienService;
use App\Services\RegistroMovimientoService;
use Database\Seeders\EstadoConservacionSeeder;

/** Ayudas reutilizables para armar escenarios de prueba del dominio. */
trait CreaEscenario
{
    protected User $encargado;

    protected User $director;

    protected function prepararCatalogos(): void
    {
        $this->seed(EstadoConservacionSeeder::class);
        $this->encargado = User::factory()->perfil('ENCARGADO')->create();
        $this->director = User::factory()->perfil('DIRECTOR')->create();
    }

    protected function estado(string $nombre): EstadoConservacion
    {
        return EstadoConservacion::where('nombre', $nombre)->firstOrFail();
    }

    protected function crearBien(array $sobrescribir = []): Bien
    {
        $datos = array_merge([
            'codigo_patrimonial' => 'CP'.fake()->unique()->numerify('########'),
            'descripcion' => 'Proyector multimedia',
            'ambiente_id' => Ambiente::factory()->create()->id,
            'responsable_id' => Personal::factory()->create()->id,
            'estado_id' => $this->estado('bueno')->id,
            'fecha_ultima_verificacion' => now()->subDays(10)->toDateString(),
        ], $sobrescribir);

        return app(RegistroBienService::class)->registrar($datos, $this->encargado);
    }

    protected function movimiento(Bien $bien, TipoMovimiento $tipo, array $datos): Movimiento
    {
        $base = [
            'fecha' => now()->toDateString(),
            'documento_tipo' => app(FabricaHandlers::class)->para($tipo)->documentosAceptados()[0]->value,
            'documento_numero' => (string) fake()->unique()->numberBetween(1, 99999),
        ];

        return app(RegistroMovimientoService::class)->registrar($bien, $tipo, array_merge($base, $datos), $this->encargado);
    }
}
