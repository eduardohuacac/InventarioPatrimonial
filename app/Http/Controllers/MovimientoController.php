<?php

namespace App\Http\Controllers;

use App\Domain\Movimientos\FabricaHandlers;
use App\Enums\TipoMovimiento;
use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\EstadoConservacion;
use App\Models\Personal;
use App\Services\RegistroMovimientoService;
use Illuminate\Http\Request;

/** Registro de movimientos con documento de sustento obligatorio (RF06, RF07). */
class MovimientoController extends Controller
{
    public function create(Bien $bien, string $tipo, FabricaHandlers $fabrica)
    {
        $tipo = $this->tipo($tipo);
        $handler = $fabrica->para($tipo);
        $bien->load(['ambiente', 'responsable', 'estado']);

        $previoPrestamo = $tipo === TipoMovimiento::DEVOLUCION
            ? $bien->movimientos()->where('tipo', TipoMovimiento::PRESTAMO->value)->latest('id')->first()?->responsableAnterior
            : null;

        return view('movimientos.create', [
            'bien' => $bien,
            'tipoMov' => $tipo,
            'handler' => $handler,
            'permitido' => in_array($bien->situacion, $handler->situacionesPermitidas(), true),
            'ambientes' => Ambiente::activos()->get(),
            'personal' => Personal::activos()->get(),
            'estados' => EstadoConservacion::ordenados(),
            'previoPrestamo' => $previoPrestamo,
        ]);
    }

    public function store(Request $request, Bien $bien, string $tipo, RegistroMovimientoService $servicio)
    {
        $tipo = $this->tipo($tipo);
        $servicio->registrar($bien, $tipo, $request->except(['_token', 'archivo']), $request->user(), $request->file('archivo'));

        $mensaje = $tipo === TipoMovimiento::SOLICITUD_BAJA
            ? 'Solicitud de baja registrada. El bien queda «en trámite de baja» hasta la decisión de la Dirección.'
            : $tipo->etiqueta().' registrado(a) correctamente.';

        return redirect()->route('bienes.show', $bien)->with('ok', $mensaje);
    }

    private function tipo(string $valor): TipoMovimiento
    {
        $tipo = TipoMovimiento::tryFrom(strtoupper($valor));
        abort_unless($tipo && in_array($tipo, TipoMovimiento::registrables(), true), 404);

        return $tipo;
    }
}
