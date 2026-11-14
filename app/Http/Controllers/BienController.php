<?php

namespace App\Http\Controllers;

use App\Domain\Movimientos\FabricaHandlers;
use App\Enums\Situacion;
use App\Enums\TipoMovimiento;
use App\Models\Ambiente;
use App\Models\Bien;
use App\Models\EstadoConservacion;
use App\Models\Personal;
use App\Services\RegistroBienService;
use Illuminate\Http\Request;

class BienController extends Controller
{
    /** Lista y búsqueda por código o descripción (RF04). */
    public function index(Request $request)
    {
        $filtros = $request->only(['q', 'ambiente_id', 'responsable_id', 'estado_id', 'situacion']);

        // Búsqueda exacta por código (p. ej. con lector de código de barras): va directo a la ficha.
        if (! empty($filtros['q']) && count(array_filter($filtros)) === 1) {
            $exacto = Bien::where('codigo_patrimonial', strtoupper(trim($filtros['q'])))->first();
            if ($exacto) {
                return redirect()->route('bienes.show', $exacto);
            }
        }

        $bienes = Bien::with(['ambiente', 'responsable', 'estado'])
            ->buscar($filtros['q'] ?? null)
            ->when($filtros['ambiente_id'] ?? null, fn ($q, $v) => $q->where('ambiente_actual_id', $v))
            ->when($filtros['responsable_id'] ?? null, fn ($q, $v) => $q->where('responsable_actual_id', $v))
            ->when($filtros['estado_id'] ?? null, fn ($q, $v) => $q->where('estado_actual_id', $v))
            ->when($filtros['situacion'] ?? null, fn ($q, $v) => $q->where('situacion', $v))
            ->orderBy('codigo_patrimonial')
            ->paginate(25)->withQueryString();

        return view('bienes.index', [
            'bienes' => $bienes,
            'filtros' => $filtros,
            'ambientes' => Ambiente::orderBy('nombre')->get(),
            'personal' => Personal::orderBy('nombres')->get(),
            'estados' => EstadoConservacion::ordenados(),
        ]);
    }

    public function create()
    {
        return view('bienes.create', $this->catalogos());
    }

    /** Registro de bien con validación de obligatorios y código único (RF03, HU01). */
    public function store(Request $request, RegistroBienService $servicio)
    {
        $datos = $request->validate(RegistroBienService::reglas(), RegistroBienService::mensajes());
        $bien = $servicio->registrar($datos, $request->user());

        return redirect()->route('bienes.show', $bien)->with('ok', "Bien {$bien->codigo_patrimonial} registrado.");
    }

    /** Ficha: situación actual + línea de tiempo (RF04, RF05, HU05). */
    public function show(Bien $bien)
    {
        $this->authorize('view', $bien);
        $bien->load(['ambiente', 'responsable', 'estado', 'creador']);
        $movimientos = $bien->movimientos()
            ->with(['ambienteOrigen', 'ambienteDestino', 'responsableAnterior', 'responsableNuevo', 'estado', 'documento', 'registrador', 'aprobacion.director', 'correcciones.aprobacion'])
            ->get();

        $responsablePrevioPrestamo = $bien->situacion === Situacion::PRESTADO
            ? $movimientos->where('tipo', TipoMovimiento::PRESTAMO)->last()?->responsableAnterior
            : null;

        return view('bienes.show', [
            'bien' => $bien,
            'movimientos' => $movimientos->reverse()->values(),
            'tiposDisponibles' => $this->tiposDisponibles($bien),
            'responsablePrevioPrestamo' => $responsablePrevioPrestamo,
        ]);
    }

    public function edit(Bien $bien)
    {
        $this->authorize('update', $bien);

        return view('bienes.edit', compact('bien'));
    }

    /**
     * Solo datos descriptivos. Ambiente, responsable y estado cambian
     * únicamente mediante movimientos con sustento.
     */
    public function update(Request $request, Bien $bien)
    {
        $this->authorize('update', $bien);
        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'min:3', 'max:255'],
            'codigo_catalogo_siga' => ['nullable', 'string', 'max:20'],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'serie' => ['nullable', 'string', 'max:80'],
            'fecha_ingreso' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
        $bien->update($datos);

        return redirect()->route('bienes.show', $bien)->with('ok', 'Datos descriptivos actualizados (queda registrado en auditoría).');
    }

    private function catalogos(): array
    {
        return [
            'ambientes' => Ambiente::activos()->get(),
            'personal' => Personal::activos()->get(),
            'estados' => EstadoConservacion::ordenados(),
        ];
    }

    private function tiposDisponibles(Bien $bien): array
    {
        $fabrica = app(FabricaHandlers::class);

        return collect(TipoMovimiento::registrables())
            ->filter(fn (TipoMovimiento $t) => in_array($bien->situacion, $fabrica->para($t)->situacionesPermitidas(), true))
            ->values()->all();
    }
}
