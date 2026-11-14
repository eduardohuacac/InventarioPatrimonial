<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoteImportacion extends Model
{
    use SoloInsercion;

    protected $table = 'lotes_importacion';

    public $timestamps = false;

    protected $fillable = [
        'archivo_nombre', 'archivo_hash', 'filas_origen', 'conservadas', 'corregidas', 'fusionadas',
        'grupos_fusion', 'pendientes', 'excluidas', 'cargados', 'importado_por', 'importado_en',
    ];

    protected function casts(): array
    {
        return ['importado_en' => 'datetime'];
    }

    public function bitacora(): HasMany
    {
        return $this->hasMany(BitacoraMigracion::class, 'lote_id')->orderBy('id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importado_por');
    }

    /** Registros cargados esperados = Filas − Excluidas − (Fusionadas − Grupos). */
    public function cargadosEsperados(): int
    {
        return $this->filas_origen - $this->excluidas - ($this->fusionadas - $this->grupos_fusion);
    }

    public function cuadra(): bool
    {
        return $this->cargadosEsperados() === $this->cargados;
    }
}
