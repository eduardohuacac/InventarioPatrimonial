<?php

namespace App\Models;

use App\Enums\TipoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Documento de sustento (acta, papeleta, informe...). */
class Documento extends Model
{
    protected $fillable = ['tipo', 'numero', 'fecha', 'archivo_path', 'registrado_por'];

    protected function casts(): array
    {
        return ['tipo' => TipoDocumento::class, 'fecha' => 'date'];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    public function rotulo(): string
    {
        return $this->tipo->etiqueta().' N.° '.$this->numero;
    }
}
