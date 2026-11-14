<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Descarga protegida del archivo de sustento (no está en una carpeta pública). */
class DocumentoController extends Controller
{
    public function archivo(Request $request, Documento $documento)
    {
        $usuario = $request->user();
        $permitido = $usuario->veTodosLosBienes()
            || ($usuario->personal_id && $documento->movimientos()->whereHas('bien', fn ($q) => $q->where('responsable_actual_id', $usuario->personal_id))->exists());

        abort_unless($permitido && $documento->archivo_path && Storage::disk('local')->exists($documento->archivo_path), 404);

        return Storage::disk('local')->response($documento->archivo_path, $documento->tipo->value.'-'.$documento->numero.'.'.pathinfo($documento->archivo_path, PATHINFO_EXTENSION));
    }
}
