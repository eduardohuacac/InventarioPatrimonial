<?php

namespace Database\Seeders;

use App\Models\EstadoConservacion;
use Illuminate\Database\Seeder;

class EstadoConservacionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['bueno' => 1, 'regular' => 2, 'malo' => 3] as $nombre => $orden) {
            EstadoConservacion::firstOrCreate(['nombre' => $nombre], ['orden' => $orden]);
        }
    }
}
