<?php

namespace App\Models;

use App\Enums\Situacion;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bien patrimonial. Los campos *_actual_*, situacion, fecha_ultima_verificacion
 * y fecha_prevista_devolucion son la situación vigente: solo los cambia
 * App\Domain\Movimientos\ProyectorSituacion a partir del historial.
 */
class Bien extends Model
{
    use Auditable, HasFactory;

    protected $table = 'bienes';

    protected $fillable = [
        'codigo_patrimonial', 'codigo_catalogo_siga', 'descripcion', 'marca', 'modelo', 'serie',
        'fecha_ingreso', 'ambiente_actual_id', 'responsable_actual_id', 'estado_actual_id',
        'situacion', 'fecha_ultima_verificacion', 'fecha_prevista_devolucion', 'id_origen', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'situacion' => Situacion::class,
            'fecha_ingreso' => 'date',
            'fecha_ultima_verificacion' => 'date',
            'fecha_prevista_devolucion' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'codigo_patrimonial';
    }

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class, 'ambiente_actual_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'responsable_actual_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoConservacion::class, 'estado_actual_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('id');
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($texto) {
            $q->where('codigo_patrimonial', 'like', $texto.'%')
                ->orWhere('descripcion', 'like', '%'.$texto.'%')
                ->orWhere('serie', 'like', $texto.'%');
        });
    }

    public function prestamoVencido(): bool
    {
        return $this->situacion === Situacion::PRESTADO
            && $this->fecha_prevista_devolucion !== null
            && $this->fecha_prevista_devolucion->lt(today());
    }
}
