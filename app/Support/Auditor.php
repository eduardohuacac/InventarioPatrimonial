<?php

namespace App\Support;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/** Punto único para registrar operaciones en la auditoría (RF13). */
class Auditor
{
    public static function registrar(string $accion, Model|string|null $entidad = null, array $detalle = [], ?int $usuarioId = null): void
    {
        Auditoria::create([
            'usuario_id' => $usuarioId ?? Auth::id(),
            'accion' => $accion,
            'entidad' => $entidad instanceof Model ? class_basename($entidad) : $entidad,
            'entidad_id' => $entidad instanceof Model ? $entidad->getKey() : null,
            'detalle' => $detalle ?: null,
            'ip' => app()->runningInConsole() ? null : Request::ip(),
            'fecha_hora' => now(),
        ]);
    }
}
