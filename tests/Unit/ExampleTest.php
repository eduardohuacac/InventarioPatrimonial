<?php

namespace Tests\Unit;

use App\Enums\TipoMovimiento;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_correccion_y_registro_no_son_registrables_desde_la_ficha(): void
    {
        $this->assertNotContains(TipoMovimiento::REGISTRO, TipoMovimiento::registrables());
        $this->assertNotContains(TipoMovimiento::CORRECCION, TipoMovimiento::registrables());
    }
}
