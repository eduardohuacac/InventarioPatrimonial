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
            'nombre' => 'Aula '.fake()->unique()->numberBetween(100, 999),
            'tipo' => 'aula',
            'piso' => (string) fake()->numberBetween(1, 3),
            'activo' => true,
        ];
    }
}
