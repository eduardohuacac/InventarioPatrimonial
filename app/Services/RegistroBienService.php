<?php

namespace App\Services;

use App\Domain\Movimientos\ProyectorSituacion;
use App\Enums\Situacion;
use App\Enums\TipoDocumento;
use App\Enums\TipoMovimiento;
use App\Models\Bien;
use App\Models\Documento;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Caso de uso «Registrar bien» (RF03, HU01): valida los seis datos
 * obligatorios y el código único, crea el bien y su movimiento REGISTRO.
 */
class RegistroBienService
{
    public function __construct(private readonly ProyectorSituacion $proyector) {}

    public static function reglas(): array
    {
        return [
            'codigo_patrimonial' => ['required', 'string', 'regex:/^[A-Za-z0-9\-]{4,20}$/', 'unique:bienes,codigo_patrimonial'],
            'descripcion' => ['required', 'string', 'min:3', 'max:255'],
            'ambiente_id' => ['required', Rule::exists('ambientes', 'id')->where('activo', true)],
            'responsable_id' => ['required', Rule::exists('personal', 'id')->where('activo', true)],
            'estado_id' => ['required', 'exists:estados_conservacion,id'],
            'fecha_ultima_verificacion' => ['required', 'date', 'before_or_equal:today', 'after:1990-01-01'],
            'codigo_catalogo_siga' => ['nullable', 'string', 'max:20'],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'serie' => ['nullable', 'string', 'max:80'],
            'fecha_ingreso' => ['nullable', 'date', 'before_or_equal:today'],
            'documento_tipo' => ['nullable', Rule::in([TipoDocumento::COMPROBANTE_INGRESO->value, TipoDocumento::ACTA_ENTREGA->value])],
            'documento_numero' => ['required_with:documento_tipo', 'nullable', 'string', 'max:40'],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'codigo_patrimonial.unique' => 'Ya existe un bien con ese código patrimonial.',
            'codigo_patrimonial.regex' => 'El código patrimonial debe tener de 4 a 20 letras, números o guiones.',
            'ambiente_id.required' => 'Seleccione el ambiente.',
            'responsable_id.required' => 'Seleccione el responsable.',
            'estado_id.required' => 'Seleccione el estado de conservación.',
            'fecha_ultima_verificacion.required' => 'Indique la fecha de última verificación.',
        ];
    }

    /** $datos ya validados. */
    public function registrar(array $datos, User $usuario, ?string $idOrigen = null, Situacion $situacionInicial = Situacion::ACTIVO): Bien
    {
        return DB::transaction(function () use ($datos, $usuario, $idOrigen, $situacionInicial) {
            $bien = Bien::create([
                'codigo_patrimonial' => strtoupper(trim($datos['codigo_patrimonial'])),
                'codigo_catalogo_siga' => $datos['codigo_catalogo_siga'] ?? null,
                'descripcion' => trim($datos['descripcion']),
                'marca' => $datos['marca'] ?? null,
                'modelo' => $datos['modelo'] ?? null,
                'serie' => $datos['serie'] ?? null,
                'fecha_ingreso' => $datos['fecha_ingreso'] ?? null,
                'ambiente_actual_id' => $datos['ambiente_id'],
                'responsable_actual_id' => $datos['responsable_id'],
                'estado_actual_id' => $datos['estado_id'],
                'situacion' => $situacionInicial,
                'fecha_ultima_verificacion' => $datos['fecha_ultima_verificacion'],
                'id_origen' => $idOrigen,
                'creado_por' => $usuario->id,
            ]);

            $documento = null;
            if (! empty($datos['documento_tipo'])) {
                $documento = Documento::firstOrCreate(
                    ['tipo' => $datos['documento_tipo'], 'numero' => trim($datos['documento_numero'])],
                    ['fecha' => $datos['fecha_ingreso'] ?? $datos['fecha_ultima_verificacion'], 'registrado_por' => $usuario->id]
                );
            }

            Movimiento::create([
                'bien_id' => $bien->id,
                'tipo' => TipoMovimiento::REGISTRO,
                'fecha' => $datos['fecha_ultima_verificacion'],
                'ambiente_destino_id' => $datos['ambiente_id'],
                'responsable_nuevo_id' => $datos['responsable_id'],
                'estado_id' => $datos['estado_id'],
                'documento_id' => $documento?->id,
                'encontrado' => $situacionInicial !== Situacion::NO_UBICADO,
                'observacion' => $idOrigen ? "Incorporado desde registros depurados ({$idOrigen})" : null,
                'requiere_aprobacion' => false,
                'registrado_por' => $usuario->id,
                'registrado_en' => now(),
            ]);

            return $this->proyector->sincronizar($bien);
        });
    }
}
