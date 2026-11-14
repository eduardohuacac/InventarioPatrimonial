<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ambiente extends Model
{
    use Auditable, HasFactory;

    public const TIPOS = [
        'aula' => 'Aula',
        'oficina' => 'Oficina',
        'laboratorio' => 'Laboratorio',
        'almacen' => 'Almacén',
        'biblioteca' => 'Biblioteca',
        'otro' => 'Otro',
    ];

    protected $fillable = ['nombre', 'tipo', 'piso', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function bienes(): HasMany
    {
        return $this->hasMany(Bien::class, 'ambiente_actual_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('nombre');
    }
}
