<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;

/** Consulta de auditoría: quién hizo qué y cuándo (RF13). */
class AuditoriaController extends Controller
{
    public function __invoke(Request $request)
    {
        $f = $request->validate([
            'usuario_id' => ['nullable', 'integer'],
            'accion' => ['nullable', 'string', 'max:40'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $registros = Auditoria::with('usuario')
            ->when($f['usuario_id'] ?? null, fn ($q, $v) => $q->where('usuario_id', $v))
            ->when($f['accion'] ?? null, fn ($q, $v) => $q->where('accion', $v))
            ->when($f['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha_hora', '>=', $v))
            ->when($f['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha_hora', '<=', $v))
            ->orderByDesc('id')->paginate(40)->withQueryString();

        return view('auditoria.index', [
            'registros' => $registros,
            'filtros' => $f,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'acciones' => Auditoria::select('accion')->distinct()->orderBy('accion')->pluck('accion'),
        ]);
    }
}
