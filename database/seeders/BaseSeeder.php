<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Datos mínimos para poner el sistema en uso: estados y un administrador. */
class BaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EstadoConservacionSeeder::class);

        User::firstOrCreate(['email' => 'admin@ie50023.edu.pe'], [
            'name' => 'Administrador del sistema',
            'password' => 'Patrimonio2026',
            'perfil' => 'ADMIN',
            'activo' => true,
        ]);
    }
}
