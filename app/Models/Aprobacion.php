<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Decisión de la Dirección sobre un movimiento (solo inserción). */
class Aprobacion extends Model
{
    use SoloInsercion;

    protected $table = 'aprobaciones';

    public $timestamps = false;

    protected $fillable = ['movimiento_id', 'decision', 'observacion', 'decidido_por', 'decidido_en'];

    protected function casts(): array
    {
        return ['decidido_en' => 'datetime'];
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }
}
