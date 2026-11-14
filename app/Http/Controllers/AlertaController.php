<?php

namespace App\Http\Controllers;

use App\Services\Alertas;

/** Alertas para la revisión mensual (RF11, HU09). */
class AlertaController extends Controller
{
    public function __invoke(Alertas $alertas)
    {
        return view('alertas.index', [
            'vencidos' => $alertas->prestamosVencidos()->get(),
            'sinVerificar' => $alertas->sinVerificacionReciente()->paginate(30),
            'noUbicados' => $alertas->noUbicados()->get(),
            'cierre' => Alertas::ultimoCierre(),
        ]);
    }
}
