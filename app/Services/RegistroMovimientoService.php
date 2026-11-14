<?php

namespace App\Services;

use App\Domain\Movimientos\FabricaHandlers;
use App\Domain\Movimientos\ProyectorSituacion;
use App\Domain\Movimientos\SituacionBien;
use App\Enums\Decision;
use App\Enums\TipoMovimiento;
use App\Exceptions\ReglaNegocioException;
use App\Models\Aprobacion;
use App\Models\Bien;
use App\Models\Documento;
use App\Models\Movimiento;
use App\Models\User;
use App\Support\Auditor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Caso de uso «Registrar movimiento» (RF06–RF08). Valida las reglas del
 * negocio, inserta el movimiento y actualiza la situación vigente del bien
 * en una sola transacción (RNF03).
 */
class RegistroMovimientoService
{
    public function __construct(
        private readonly FabricaHandlers $fabrica,
        private readonly ProyectorSituacion $proyector,
    ) {}

    public function registrar(Bien $bien, TipoMovimiento $tipo, array $entrada, User $usuario, ?UploadedFile $archivo = null): Movimiento
    {
        if (in_array($tipo, [TipoMovimiento::REGISTRO, TipoMovimiento::CORRECCION], true)) {
            throw ReglaNegocioException::en('tipo', 'Este tipo de movimiento no se registra desde aquí.');
        }

        return DB::transaction(function () use ($bien, $tipo, $entrada, $usuario, $archivo) {
            $bien = Bien::whereKey($bien->id)->lockForUpdate()->firstOrFail();
            $handler = $this->fabrica->para($tipo);
            $actual = SituacionBien::desdeBien($bien);

            if (! in_array($actual->situacion, $handler->situacionesPermitidas(), true)) {
                throw ReglaNegocioException::en('tipo', sprintf(
                    'No se puede registrar «%s» porque el bien está «%s».',
                    $tipo->etiqueta(),
                    $actual->situacion->etiqueta()
                ));
            }

            $datos = $this->validar($bien, $handler, $actual, $entrada, $archivo);

            if ($tipo === TipoMovimiento::BAJA) {
                $datos['solicitud_aprobada'] = $this->solicitudDeBajaAprobada($bien);
            }
            $handler->validarContexto($actual, $datos);

            $documento = $this->resolverDocumento($datos, $usuario, $archivo);

            $movimiento = Movimiento::create(array_merge($handler->construir($actual, $datos), [
                'bien_id' => $bien->id,
                'tipo' => $tipo,
                'fecha' => $datos['fecha'],
                'documento_id' => $documento?->id,
                'observacion' => $datos['observacion'] ?? null,
                'requiere_aprobacion' => $handler->requiereAprobacion(),
                'registrado_por' => $usuario->id,
                'registrado_en' => now(),
            ]));

            $this->proyector->sincronizar($bien);
            Auditor::registrar('registrar_movimiento', $movimiento, [
                'tipo' => $tipo->value,
                'bien' => $bien->codigo_patrimonial,
            ]);

            return $movimiento;
        });
    }

    /**
     * Registra una corrección que anula un movimiento erróneo (RF08).
     * Queda pendiente de aprobación de la Dirección.
     */
    public function registrarCorreccion(Movimiento $objetivo, array $entrada, User $usuario, ?UploadedFile $archivo = null): Movimiento
    {
        $this->asegurarCorregible($objetivo);
        $handler = $this->fabrica->para(TipoMovimiento::CORRECCION);

        return DB::transaction(function () use ($objetivo, $entrada, $usuario, $archivo, $handler) {
            $bien = Bien::whereKey($objetivo->bien_id)->lockForUpdate()->firstOrFail();
            $actual = SituacionBien::desdeBien($bien);
            $datos = $this->validar($bien, $handler, $actual, $entrada, $archivo);
            $datos['movimiento_anulado_id'] = $objetivo->id;
            $documento = $this->resolverDocumento($datos, $usuario, $archivo);

            $correccion = Movimiento::create(array_merge($handler->construir($actual, $datos), [
                'bien_id' => $bien->id,
                'tipo' => TipoMovimiento::CORRECCION,
                'fecha' => $datos['fecha'],
                'documento_id' => $documento?->id,
                'observacion' => $datos['observacion'] ?? null,
                'requiere_aprobacion' => true,
                'registrado_por' => $usuario->id,
                'registrado_en' => now(),
            ]));

            Auditor::registrar('solicitar_correccion', $correccion, [
                'anula' => $objetivo->id,
                'bien' => $bien->codigo_patrimonial,
            ]);

            return $correccion;
        });
    }

    /** Decisión de la Dirección sobre una solicitud de baja o una corrección (RF07, RF08). */
    public function decidir(Movimiento $movimiento, Decision $decision, ?string $observacion, User $director): Aprobacion
    {
        if (! $movimiento->requiere_aprobacion) {
            throw ReglaNegocioException::en('decision', 'Este movimiento no requiere aprobación.');
        }

        return DB::transaction(function () use ($movimiento, $decision, $observacion, $director) {
            $bien = Bien::whereKey($movimiento->bien_id)->lockForUpdate()->firstOrFail();
            if (Aprobacion::where('movimiento_id', $movimiento->id)->exists()) {
                throw ReglaNegocioException::en('decision', 'Este movimiento ya fue resuelto.');
            }

            $aprobacion = Aprobacion::create([
                'movimiento_id' => $movimiento->id,
                'decision' => $decision->value,
                'observacion' => $observacion,
                'decidido_por' => $director->id,
                'decidido_en' => now(),
            ]);

            $this->proyector->sincronizar($bien);
            Auditor::registrar(strtolower($decision->value === 'APROBADO' ? 'aprobar' : 'rechazar'), $movimiento, [
                'tipo' => $movimiento->tipo->value,
                'bien' => $bien->codigo_patrimonial,
                'observacion' => $observacion,
            ]);

            return $aprobacion;
        });
    }

    /** Operaciones posteriores al movimiento a corregir, para que el encargado las revise. */
    public function posteriores(Movimiento $objetivo): Collection
    {
        return Movimiento::with(['aprobacion', 'correcciones.aprobacion'])
            ->where('bien_id', $objetivo->bien_id)
            ->where('id', '>', $objetivo->id)
            ->where('tipo', '!=', TipoMovimiento::CORRECCION->value)
            ->orderBy('id')
            ->get()
            ->reject(fn (Movimiento $m) => $m->estaAnulado())
            ->values();
    }

    public function asegurarCorregible(Movimiento $objetivo): void
    {
        $objetivo->loadMissing(['aprobacion', 'correcciones.aprobacion']);

        if (in_array($objetivo->tipo, [TipoMovimiento::REGISTRO, TipoMovimiento::CORRECCION], true)) {
            throw ReglaNegocioException::en('motivo', 'El registro inicial y las correcciones no se pueden anular.');
        }
        if ($objetivo->estaPendiente()) {
            throw ReglaNegocioException::en('motivo', 'El movimiento está pendiente de aprobación: la Dirección puede rechazarlo directamente.');
        }
        if ($objetivo->estaAnulado()) {
            throw ReglaNegocioException::en('motivo', 'El movimiento ya fue anulado.');
        }
        if ($objetivo->correcciones->contains(fn (Movimiento $c) => $c->estaPendiente())) {
            throw ReglaNegocioException::en('motivo', 'Ya existe una corrección pendiente para este movimiento.');
        }
    }

    private function validar(Bien $bien, $handler, SituacionBien $actual, array $entrada, ?UploadedFile $archivo): array
    {
        $ultimaFecha = Movimiento::where('bien_id', $bien->id)->max('fecha');
        $tiposDoc = array_map(fn ($t) => $t->value, $handler->documentosAceptados());
        $docObligatorio = $handler->documentoObligatorio();

        $reglas = array_merge([
            'fecha' => array_filter(['required', 'date', 'before_or_equal:today', $ultimaFecha ? 'after_or_equal:'.substr((string) $ultimaFecha, 0, 10) : null]),
            'documento_tipo' => [$docObligatorio ? 'required' : 'nullable', Rule::in($tiposDoc)],
            'documento_numero' => [$docObligatorio ? 'required' : 'required_with:documento_tipo', 'nullable', 'string', 'max:40', 'regex:/^[\pL\pN\-\/\. ]+$/u'],
            'documento_fecha' => ['nullable', 'date', 'before_or_equal:today'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ], $handler->reglas($actual));

        $mensajes = [
            'documento_tipo.required' => 'El movimiento debe tener un documento de sustento.',
            'documento_numero.required' => 'Indique el número del documento de sustento.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior al último movimiento del bien (:date).',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'fecha_prevista_devolucion.after' => 'La fecha prevista de devolución debe ser posterior a la fecha del préstamo.',
        ];

        $entrada['archivo'] = $archivo;
        $reglas['archivo'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        return Validator::make($entrada, $reglas, $mensajes)->validate();
    }

    private function resolverDocumento(array $datos, User $usuario, ?UploadedFile $archivo): ?Documento
    {
        if (empty($datos['documento_tipo'])) {
            return null;
        }

        $documento = Documento::firstOrCreate(
            ['tipo' => $datos['documento_tipo'], 'numero' => trim($datos['documento_numero'])],
            ['fecha' => $datos['documento_fecha'] ?? $datos['fecha'], 'registrado_por' => $usuario->id]
        );

        if ($archivo && ! $documento->archivo_path) {
            $documento->archivo_path = $archivo->store('sustentos', 'local');
            $documento->save();
        }

        return $documento;
    }

    private function solicitudDeBajaAprobada(Bien $bien): bool
    {
        $solicitud = Movimiento::with('aprobacion')
            ->where('bien_id', $bien->id)
            ->where('tipo', TipoMovimiento::SOLICITUD_BAJA->value)
            ->orderByDesc('id')
            ->first();

        return $solicitud?->estadoAprobacion() === Decision::APROBADO->value;
    }
}
