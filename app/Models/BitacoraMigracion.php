<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraMigracion extends Model
{
    use SoloInsercion;

    protected $table = 'bitacora_migracion';

    public $timestamps = false;

    protected $fillable = [
        'lote_id', 'id_origen', 'reglas', 'dato_anterior', 'dato_nuevo', 'evidencia',
        'autorizado_por', 'fecha', 'resultado', 'grupo_fusion', 'bien_id', 'creado_en',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'creado_en' => 'datetime'];
    }

    public function bien(): BelongsTo
    {
        return $this->belongsTo(Bien::class);
    }
}
