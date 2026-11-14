<?php

namespace App\Http\Controllers;

use App\Models\LoteImportacion;
use App\Services\Importacion\DiagnosticoDepuracion;
use App\Services\Importacion\ImportadorDepurados;
use App\Services\Importacion\LectorArchivo;
use App\Services\Importacion\PlantillaImportacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Importación de registros depurados con bitácora y reconciliación (RF09, HU08). */
class ImportacionController extends Controller
{
    public function index()
    {
        return view('importacion.index', [
            'lotes' => LoteImportacion::with('usuario')->orderByDesc('id')->get(),
        ]);
    }

    public function plantilla(PlantillaImportacion $plantilla)
    {
        $ruta = storage_path('app/private/plantilla_registros_depurados.xlsx');
        @mkdir(dirname($ruta), 0775, true);
        $plantilla->generar($ruta);

        return response()->download($ruta, 'plantilla_registros_depurados.xlsx')->deleteFileAfterSend();
    }

    /** Paso 1: lee el archivo y muestra el diagnóstico R1–R5 sin guardar nada. */
    public function previsualizar(Request $request, LectorArchivo $lector, DiagnosticoDepuracion $diagnostico)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ], ['archivo.mimes' => 'El archivo debe ser CSV o Excel (.xlsx, .xls).']);

        $archivo = $request->file('archivo');
        $leido = $lector->leer($archivo->getRealPath(), $archivo->getClientOriginalExtension());

        if ($leido['faltantes']) {
            return back()->withErrors(['archivo' => 'Faltan columnas obligatorias: '.implode(', ', $leido['faltantes']).'. Descargue la plantilla.']);
        }
        if ($leido['filas'] === []) {
            return back()->withErrors(['archivo' => 'El archivo no tiene filas de datos.']);
        }
        if (count($leido['filas']) > LectorArchivo::MAX_FILAS) {
            return back()->withErrors(['archivo' => 'El archivo supera el máximo de '.LectorArchivo::MAX_FILAS.' filas.']);
        }

        $token = (string) Str::uuid();
        Storage::disk('local')->put("importaciones/{$token}.json", json_encode([
            'nombre' => $archivo->getClientOriginalName(),
            'hash' => hash_file('sha256', $archivo->getRealPath()),
            'filas' => $leido['filas'],
        ]));
        $request->session()->put('importacion_token', $token);

        $diag = $diagnostico->diagnosticar($leido['filas']);

        return view('importacion.preview', [
            'nombre' => $archivo->getClientOriginalName(),
            'filas' => $diag,
            'resumen' => $diagnostico->resumen($diag),
        ]);
    }

    /** Paso 2: confirma y carga dentro de una transacción. */
    public function confirmar(Request $request, ImportadorDepurados $importador)
    {
        $token = $request->session()->pull('importacion_token');
        abort_unless($token && Str::isUuid($token) && Storage::disk('local')->exists("importaciones/{$token}.json"), 410, 'La previsualización expiró. Vuelva a subir el archivo.');

        $datos = json_decode(Storage::disk('local')->get("importaciones/{$token}.json"), true);
        Storage::disk('local')->delete("importaciones/{$token}.json");

        $lote = $importador->importar($datos['filas'], $datos['nombre'], $datos['hash'], $request->user());

        return redirect()->route('importacion.show', $lote)->with('ok', "Importación completada: {$lote->cargados} bienes cargados.");
    }

    /** Reporte de reconciliación y bitácora del lote. */
    public function show(LoteImportacion $lote)
    {
        $lote->load(['usuario', 'bitacora.bien']);

        return view('importacion.show', compact('lote'));
    }
}
