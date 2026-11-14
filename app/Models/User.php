<?php

namespace App\Models;

use App\Enums\Perfil;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory;

    protected $fillable = ['name', 'email', 'password', 'perfil', 'personal_id', 'activo', 'ultimo_acceso'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'perfil' => Perfil::class,
            'activo' => 'boolean',
            'ultimo_acceso' => 'datetime',
        ];
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class);
    }

    public function tienePerfil(Perfil|string ...$perfiles): bool
    {
        foreach ($perfiles as $perfil) {
            $valor = $perfil instanceof Perfil ? $perfil : Perfil::from($perfil);
            if ($this->perfil === $valor) {
                return true;
            }
        }

        return false;
    }

    public function esEncargado(): bool
    {
        return $this->perfil === Perfil::ENCARGADO;
    }

    public function esDirector(): bool
    {
        return $this->perfil === Perfil::DIRECTOR;
    }

    public function esAdmin(): bool
    {
        return $this->perfil === Perfil::ADMIN;
    }

    /** Director y encargado pueden consultar todos los bienes. */
    public function veTodosLosBienes(): bool
    {
        return $this->tienePerfil(Perfil::DIRECTOR, Perfil::ENCARGADO);
    }
}
