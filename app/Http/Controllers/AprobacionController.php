<?php

namespace App\Http\Controllers;

use App\Enums\Decision;
use App\Models\Aprobacion;
use App\Models\Movimiento;
use App\Services\RegistroMovimientoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Bandeja de aprobaciones de la Dirección (RF07, RF08, HU06). */
class AprobacionController extends Controller
{
    public function index(RegistroMovimientoService $servicio)
    {
        $pendientes = Movimiento::pendientesDeAprobacion()
            ->with(['bien.ambiente', 'bien.responsable', 'documento', 'registrador', 'anulado.documento', 'anulado.ambienteDestino', 'anulado.responsableNuevo'])
            ->orderBy('id')->get()
            ->each(fn (Movimiento $m) => $m->setAttribute('posteriores', $m->anulado ? $servicio->posteriores($m->anulado) : collect()));

        $resueltas = Aprobacion::with(['movimiento.bien', 'director'])->orderByDesc('id')->limit(15)->get();

        return view('aprobaciones.index', compact('pendientes', 'resueltas'));
    }

    public function decidir(Request $request, Movimiento $movimiento, RegistroMovimientoService $servicio)
    {
        $datos = $request->validate([
            'decision' => ['required', Rule::enum(Decision::class)],
            'observacion' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('decision') === Decision::RECHAZADO->value)],
        ], ['observacion.required' => 'Indique el motivo del rechazo.']);

        $servicio->decidir($movimiento, Decision::from($datos['decision']), $datos['observacion'] ?? null, $request->user());

        return redirect()->route('aprobaciones.index')->with('ok', 'Decisión registrada para el bien '.$movimiento->bien->codigo_patrimonial.'.');
    }
}
