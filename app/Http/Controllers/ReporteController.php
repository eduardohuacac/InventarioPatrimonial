<?php

namespace App\Http\Controllers;

use App\Enums\Situacion;
use App\Models\Ambiente;
use App\Models\EstadoConservacion;
use App\Models\Personal;
use App\Services\Reportes\ExportadorExcel;
use App\Services\Reportes\GeneradorReporte;
use App\Support\Auditor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Reportes por responsable, ambiente, estado y situación; exportación a PDF y Excel (RF10). */
class ReporteController extends Controller
{
    public function index(Request $request, GeneradorReporte $generador)
    {
        $filtros = $this->filtros($request);
        $reporte = $request->filled('tipo') ? $generador->generar($filtros['tipo'], $filtros) : null;

        return view('reportes.index', [
            'reporte' => $reporte,
            'filtros' => $filtros,
            'tipos' => GeneradorReporte::TIPOS,
            'ambientes' => Ambiente::orderBy('nombre')->get(),
            'personal' => Personal::orderBy('nombres')->get(),
            'estados' => EstadoConservacion::ordenados(),
            'situaciones' => Situacion::cases(),
        ]);
    }

    public function exportar(Request $request, GeneradorReporte $generador, ExportadorExcel $excel)
    {
        $filtros = $this->filtros($request);
        $request->validate(['formato' => ['required', Rule::in(['pdf', 'xlsx'])]]);
        $reporte = $generador->generar($filtros['tipo'], $filtros);
        $nombre = 'reporte_'.$filtros['tipo'].'_'.now()->format('Ymd_His');

        Auditor::registrar('exportar_reporte', null, ['tipo' => $filtros['tipo'], 'formato' => $request->formato, 'filas' => $reporte['total']]);

        if ($request->formato === 'pdf') {
            return Pdf::loadView('reportes.pdf', ['reporte' => $reporte])
                ->setPaper('a4', count($reporte['columnas']) > 7 ? 'landscape' : 'portrait')
                ->download($nombre.'.pdf');
        }

        $ruta = storage_path("app/private/{$nombre}.xlsx");
        @mkdir(dirname($ruta), 0775, true);
        $excel->exportar($reporte, $ruta);

        return response()->download($ruta)->deleteFileAfterSend();
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'tipo' => ['nullable', Rule::in(array_keys(GeneradorReporte::TIPOS))],
            'ambiente_id' => ['nullable', 'integer'],
            'responsable_id' => ['nullable', 'integer'],
            'estado_id' => ['nullable', 'integer'],
            'situacion' => ['nullable', Rule::enum(Situacion::class)],
        ]) + ['tipo' => 'responsable'];
    }
}
