<?php

namespace Database\Factories;

use App\Models\Personal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Personal> */
class PersonalFactory extends Factory
{
    protected $model = Personal::class;

    public function definition(): array
    {
        return [
            'nombres' => fake('es_PE')->name(),
            'cargo' => fake()->randomElement(['Docente', 'Auxiliar de educación', 'Administrativo', 'Subdirector(a)']),
            'dni' => null,
            'activo' => true,
        ];
    }
}
