<?php

namespace App\Models\Concerns;

use App\Support\Auditor;

/** Registra en auditoría la creación y modificación del modelo (RF13). */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($modelo) {
            Auditor::registrar('crear', $modelo, ['nuevo' => $modelo->datosAuditables($modelo->getAttributes())]);
        });

        static::updated(function ($modelo) {
            $cambios = $modelo->datosAuditables($modelo->getChanges());
            unset($cambios['updated_at']);
            if ($cambios === []) {
                return;
            }
            $anteriores = array_intersect_key($modelo->getOriginal(), $cambios);
            Auditor::registrar('modificar', $modelo, [
                'anterior' => $modelo->datosAuditables($anteriores),
                'nuevo' => $cambios,
            ]);
        });
    }

    /** Excluye datos sensibles del detalle de auditoría. */
    public function datosAuditables(array $datos): array
    {
        return array_diff_key($datos, array_flip(['password', 'remember_token', 'created_at', 'updated_at']));
    }
}
