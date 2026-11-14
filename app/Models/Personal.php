<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Personal de la I.E. que puede ser responsable de bienes. */
class Personal extends Model
{
    use Auditable, HasFactory;

    protected $table = 'personal';

    protected $fillable = ['nombres', 'cargo', 'dni', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function bienes(): HasMany
    {
        return $this->hasMany(Bien::class, 'responsable_actual_id');
    }

    /** Ley N.° 29733: el DNI se muestra enmascarado. */
    public function dniEnmascarado(): ?string
    {
        return $this->dni ? str_repeat('•', 5).substr($this->dni, -3) : null;
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('nombres');
    }
}
