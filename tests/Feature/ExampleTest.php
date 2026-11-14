<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Prueba de humo: la aplicación arranca y muestra el inicio de sesión. */
class ExampleTest extends TestCase
{
    public function test_la_pagina_de_ingreso_responde(): void
    {
        $this->get('/login')->assertOk()->assertSee('Control de Bienes Patrimoniales');
    }
}
