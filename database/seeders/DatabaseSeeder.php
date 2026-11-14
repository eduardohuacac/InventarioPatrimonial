<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan db:seed               → datos base + demostración (ficticios)
     * php artisan db:seed --class=BaseSeeder → solo datos base (uso real)
     */
    public function run(): void
    {
        $this->call(BaseSeeder::class);
        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
