<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use SoloInsercion;

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'accion', 'entidad', 'entidad_id', 'detalle', 'ip', 'fecha_hora'];

    protected function casts(): array
    {
        return ['detalle' => 'array', 'fecha_hora' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
