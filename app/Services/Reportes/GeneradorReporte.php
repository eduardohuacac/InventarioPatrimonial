<?php

namespace App\Services\Reportes;

use App\Enums\Situacion;
use App\Models\Bien;
use App\Services\Alertas;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Construye los reportes de RF10 / RF11 como una estructura tabular común
 * que luego se muestra en pantalla o se exporta a PDF o Excel.
 */
class GeneradorReporte
{
    public const TIPOS = [
        'responsable' => 'Bienes por responsable',
        'ambiente' => 'Bienes por ambiente',
        'estado' => 'Bienes por estado de conservación',
        'situacion' => 'Bienes por situación',
        'prestamos_vencidos' => 'Préstamos vencidos',
        'sin_verificacion' => 'Bienes sin verificación posterior al último cierre',
        'inventario' => 'Preparación del inventario anual (corte 31/12)',
    ];

    public function __construct(private readonly Alertas $alertas) {}

    /**
     * @return array{titulo: string, subtitulo: string, columnas: string[], grupos: array<string, array<int, array>>, total: int}
     */
    public function generar(string $tipo, array $filtros = []): array
    {
        abort_unless(isset(self::TIPOS[$tipo]), 404);

        $consulta = $this->consultaBase($tipo)->with(['ambiente', 'responsable', 'estado']);
        $this->aplicarFiltros($consulta, $filtros, $tipo);
        $bienes = $consulta->orderBy('codigo_patrimonial')->get();
        $cierre = Alertas::ultimoCierre();
        $agrupador = $this->agrupador($tipo);

        $grupos = [];
        foreach ($bienes as $b) {
            $grupos[$agrupador($b)][] = $this->fila($b, $tipo, $cierre);
        }
        ksort($grupos, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'tipo' => $tipo,
            'titulo' => self::TIPOS[$tipo],
            'subtitulo' => $this->subtitulo($tipo, $cierre),
            'columnas' => $this->columnas($tipo, $cierre),
            'grupos' => $grupos,
            'total' => $bienes->count(),
            'generado' => now(),
        ];
    }

    private function consultaBase(string $tipo): Builder
    {
        return match ($tipo) {
            'prestamos_vencidos' => $this->alertas->prestamosVencidos(),
            'sin_verificacion' => $this->alertas->sinVerificacionReciente(),
            default => Bien::query(),
        };
    }

    private function agrupador(string $tipo): \Closure
    {
        return match ($tipo) {
            'responsable' => fn (Bien $b) => $b->responsable->nombres,
            'ambiente' => fn (Bien $b) => $b->ambiente->nombre,
            'estado' => fn (Bien $b) => ucfirst($b->estado->nombre),
            'situacion' => fn (Bien $b) => $b->situacion->etiqueta(),
            default => fn () => 'Todos',
        };
    }

    private function columnas(string $tipo, Carbon $cierre): array
    {
        $base = ['Código', 'Descripción', 'Ambiente', 'Responsable', 'Estado', 'Situación', 'Últ. verificación'];

        return match ($tipo) {
            'prestamos_vencidos' => [...$base, 'Devolución prevista', 'Días de atraso'],
            'inventario' => [...$base, 'Verificado tras '.$cierre->format('d/m/Y')],
            default => $base,
        };
    }

    private function fila(Bien $b, string $tipo, Carbon $cierre): array
    {
        $fila = [
            $b->codigo_patrimonial,
            trim($b->descripcion.' '.$b->marca.' '.$b->modelo),
            $b->ambiente->nombre,
            $b->responsable->nombres,
            ucfirst($b->estado->nombre),
            $b->situacion->etiqueta(),
            $b->fecha_ultima_verificacion?->format('d/m/Y'),
        ];

        return match ($tipo) {
            'prestamos_vencidos' => [...$fila, $b->fecha_prevista_devolucion?->format('d/m/Y'), (int) $b->fecha_prevista_devolucion?->diffInDays(today())],
            'inventario' => [...$fila, $b->fecha_ultima_verificacion?->gt($cierre) ? 'Sí' : 'No'],
            default => $fila,
        };
    }

    private function aplicarFiltros(Builder $q, array $f, string $tipo): void
    {
        if (! empty($f['ambiente_id'])) {
            $q->where('ambiente_actual_id', $f['ambiente_id']);
        }
        if (! empty($f['responsable_id'])) {
            $q->where('responsable_actual_id', $f['responsable_id']);
        }
        if (! empty($f['estado_id'])) {
            $q->where('estado_actual_id', $f['estado_id']);
        }
        if (! empty($f['situacion'])) {
            $q->where('situacion', $f['situacion']);
        } elseif (in_array($tipo, ['responsable', 'ambiente', 'estado', 'inventario'], true)) {
            $q->where('situacion', '!=', Situacion::DADO_DE_BAJA->value);
        }
    }

    private function subtitulo(string $tipo, Carbon $cierre): string
    {
        return match ($tipo) {
            'sin_verificacion' => 'Última verificación igual o anterior al '.$cierre->format('d/m/Y'),
            'inventario' => 'Información interna de apoyo para la comisión de inventario. No reemplaza el registro en el SIGA – Módulo de Patrimonio.',
            default => 'Excluye bienes dados de baja salvo que se filtre por situación.',
        };
    }
}
