<?php

namespace App\Policies;

use App\Models\Bien;
use App\Models\User;

class BienPolicy
{
    /** El personal con perfil Consulta solo ve los bienes a su cargo (CP06). */
    public function view(User $user, Bien $bien): bool
    {
        return $user->veTodosLosBienes()
            || ($user->personal_id !== null && $bien->responsable_actual_id === $user->personal_id);
    }

    public function update(User $user, Bien $bien): bool
    {
        return $user->esEncargado();
    }
}
