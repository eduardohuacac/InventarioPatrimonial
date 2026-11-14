<?php

namespace App\Http\Controllers;

use App\Enums\Situacion;
use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\Movimiento;
use App\Models\Personal;
use App\Models\User;
use App\Services\Alertas;
use App\Services\Indicadores;
use Illuminate\Http\Request;

/** Panel principal con contadores, alertas e indicadores. */
class PanelController extends Controller
{
    public function __invoke(Request $request, Alertas $alertas, Indicadores $indicadores)
    {
        $usuario = $request->user();

        if ($usuario->tienePerfil('CONSULTA')) {
            return redirect()->route('mis-bienes');
        }
        if ($usuario->esAdmin()) {
            return view('panel.admin', [
                'usuarios' => User::count(),
                'ambientes' => Ambiente::count(),
                'personal' => Personal::count(),
            ]);
        }

        $porSituacion = Bien::selectRaw('situacion, COUNT(*) as total')->groupBy('situacion')->pluck('total', 'situacion');

        return view('panel.index', [
            'total' => $porSituacion->sum(),
            'porSituacion' => collect(Situacion::cases())->mapWithKeys(fn ($s) => [$s->value => ['etiqueta' => $s->etiqueta(), 'css' => $s->claseCss(), 'total' => $porSituacion[$s->value] ?? 0]]),
            'porEstado' => Bien::join('estados_conservacion', 'estados_conservacion.id', '=', 'bienes.estado_actual_id')
                ->where('situacion', '!=', Situacion::DADO_DE_BAJA->value)
                ->selectRaw('estados_conservacion.nombre, COUNT(*) as total')
                ->groupBy('estados_conservacion.nombre', 'estados_conservacion.orden')
                ->orderBy('estados_conservacion.orden')->pluck('total', 'nombre'),
            'vencidos' => $alertas->prestamosVencidos()->limit(5)->get(),
            'totalVencidos' => $alertas->prestamosVencidos()->count(),
            'sinVerificar' => $alertas->sinVerificacionReciente()->count(),
            'pendientes' => Movimiento::pendientesDeAprobacion()->count(),
            'recientes' => Movimiento::with(['bien', 'registrador'])->orderByDesc('id')->limit(8)->get(),
            'indicadores' => $indicadores->calcular(),
            'cierre' => Alertas::ultimoCierre(),
        ]);
    }
}
