<?php

namespace App\Models;

use App\Enums\Decision;
use App\Enums\TipoMovimiento;
use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Movimiento del historial de un bien. Tabla de SOLO INSERCIÓN: un error se
 * corrige con un movimiento de tipo CORRECCION que anula al original.
 */
class Movimiento extends Model
{
    use SoloInsercion;

    public $timestamps = false;

    protected $fillable = [
        'bien_id', 'tipo', 'fecha', 'ambiente_origen_id', 'ambiente_destino_id',
        'responsable_anterior_id', 'responsable_nuevo_id', 'estado_id', 'documento_id',
        'fecha_prevista_devolucion', 'encontrado', 'movimiento_anulado_id', 'motivo', 'observacion',
        'requiere_aprobacion', 'registrado_por', 'registrado_en',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'fecha' => 'date',
            'fecha_prevista_devolucion' => 'date',
            'encontrado' => 'boolean',
            'requiere_aprobacion' => 'boolean',
            'registrado_en' => 'datetime',
        ];
    }

    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }

    public function ambienteOrigen(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'ambiente_origen_id');
    }

    public function ambienteDestino(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'ambiente_destino_id');
    }

    public function responsableAnterior(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'responsable_anterior_id');
    }

    public function responsableNuevo(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'responsable_nuevo_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoConservacion::class, 'estado_id');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function anulado(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_anulado_id');
    }

    public function aprobacion(): HasOne
    {
        return $this->hasOne(Aprobacion::class);
    }

    /** Correcciones que apuntan a este movimiento. */
    public function correcciones()
    {
        return $this->hasMany(Movimiento::class, 'movimiento_anulado_id');
    }

    /** NO_REQUIERE | PENDIENTE | APROBADO | RECHAZADO */
    public function estadoAprobacion(): string
    {
        if (! $this->requiere_aprobacion) {
            return 'NO_REQUIERE';
        }

        return $this->aprobacion?->decision ?? 'PENDIENTE';
    }

    public function estaPendiente(): bool
    {
        return $this->estadoAprobacion() === 'PENDIENTE';
    }

    /** ¿Fue anulado por una corrección aprobada? */
    public function estaAnulado(): bool
    {
        return $this->correcciones
            ->contains(fn (Movimiento $c) => $c->estadoAprobacion() === Decision::APROBADO->value);
    }

    public function scopePendientesDeAprobacion(Builder $query): Builder
    {
        return $query->where('requiere_aprobacion', true)->whereDoesntHave('aprobacion');
    }
}
