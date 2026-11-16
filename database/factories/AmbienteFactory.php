<?php

namespace Database\Factories;

use App\Models\Ambiente;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ambiente> */
class AmbienteFactory extends Factory
{
    protected $model = Ambiente::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Ambiente de prueba '.fake()->unique()->numberBetween(1, 99999),
            'tipo' => 'aula',
            'piso' => (string) fake()->numberBetween(1, 3),
            'activo' => true,
        ];
    }
}
