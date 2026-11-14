<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Services\RegistroMovimientoService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Corrección de un movimiento mediante anulación con motivo (RF08). */
class CorreccionController extends Controller
{
    public function create(Movimiento $movimiento, RegistroMovimientoService $servicio)
    {
        $movimiento->load(['bien', 'ambienteOrigen', 'ambienteDestino', 'responsableAnterior', 'responsableNuevo', 'estado', 'documento']);
        $error = null;
        try {
            $servicio->asegurarCorregible($movimiento);
        } catch (ValidationException $e) {
            $error = collect($e->errors())->flatten()->first();
        }

        return view('correcciones.create', [
            'movimiento' => $movimiento,
            'posteriores' => $servicio->posteriores($movimiento)->load(['documento', 'registrador']),
            'error' => $error,
        ]);
    }

    public function store(Request $request, Movimiento $movimiento, RegistroMovimientoService $servicio)
    {
        $servicio->registrarCorreccion($movimiento, $request->except(['_token', 'archivo']), $request->user(), $request->file('archivo'));

        return redirect()->route('bienes.show', $movimiento->bien)
            ->with('ok', 'Corrección registrada. Queda pendiente de aprobación de la Dirección.');
    }
}
