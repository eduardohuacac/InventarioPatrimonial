<?php

namespace App\Services\Importacion;

use App\Enums\ResultadoMigracion;
use App\Services\Importacion\Reglas\ContextoDiagnostico;
use App\Services\Importacion\Reglas\R1CodigoRepetido;
use App\Services\Importacion\Reglas\R2PosibleDuplicado;
use App\Services\Importacion\Reglas\R3Incompleto;
use App\Services\Importacion\Reglas\R4Inconsistente;
use App\Services\Importacion\Reglas\R5SinCodigoIndividual;
use App\Services\Importacion\Reglas\ReglaCalidad;

/**
 * Aplica las reglas de calidad de datos de la tesis (OE2) a cada fila y
 * decide qué filas se cargan y cuáles impiden confirmar la importación.
 *
 * Refactorizado: cada regla R1–R5 vive en su propia clase (Strategy);
 * este servicio solo orquesta y valida la decisión de depuración.
 */
class DiagnosticoDepuracion
{
    /** @var ReglaCalidad[] */
    private array $reglas;

    public function __construct()
    {
        $this->reglas = [
            new R3Incompleto, new R1CodigoRepetido, new R2PosibleDuplicado,
            new R4Inconsistente, new R5SinCodigoIndividual,
        ];
    }

    /**
     * @param  array<int, array<string, ?string>>  $filas
     * @return array<int, array> filas con: marcas, marcas_sistema, errores, carga, principal y los ids resueltos
     */
    public function diagnosticar(array $filas): array
    {
        $ctx = new ContextoDiagnostico($filas);
        $principales = $this->principalesDeFusion($filas);
        $resultado = [];

        foreach ($filas as $i => $f) {
            [$marcas, $errores] = $this->aplicarReglas($i, $f, $ctx);
            $res = self::resultado($f);
            $principal = ($principales[$f['grupo_fusion'] ?? ''] ?? null) === $i;
            $carga = self::seCarga($res, $principal);

            $errores = $carga ? $errores : [];
            $errores = array_merge($errores, $this->erroresDeDecision($i, $f, $res, $filas));
            if ($carga && $this->codigoRepetidoEntreCargables($i, $f, $ctx, $principales)) {
                $errores[] = 'Código repetido con otra fila que también se cargaría.';
            }

            $resultado[] = $f + [
                'n' => $i + 1,
                'marcas' => array_values(array_unique(array_merge($marcas, self::marcasDeArchivo($f['reglas'])))),
                'marcas_sistema' => $marcas,
                'errores' => $errores,
                'carga' => $carga,
                'principal' => $principal,
                'ambiente_id' => $ctx->ambienteId($f['ambiente']),
                'responsable_id' => $ctx->responsableId($f['responsable']),
                'estado_id' => $ctx->estadoId($f['estado']),
            ];
        }

        return $resultado;
    }

    public function resumen(array $diagnostico): array
    {
        $contar = fn (string $r) => count(array_filter($diagnostico, fn ($f) => self::resultado($f)?->value === $r));
        $fusionadas = $contar('fusionada');
        $grupos = count(array_unique(array_filter(array_map(
            fn ($f) => self::resultado($f) === ResultadoMigracion::FUSIONADA ? $f['grupo_fusion'] : null,
            $diagnostico
        ))));
        $filas = count($diagnostico);
        $excluidas = $contar('excluida');

        return [
            'filas_origen' => $filas,
            'conservadas' => $contar('conservada'),
            'corregidas' => $contar('corregida'),
            'fusionadas' => $fusionadas,
            'grupos_fusion' => $grupos,
            'pendientes' => $contar('pendiente'),
            'excluidas' => $excluidas,
            'cargados_esperados' => $filas - $excluidas - ($fusionadas - $grupos),
            'con_errores' => count(array_filter($diagnostico, fn ($f) => $f['errores'] !== [])),
            'por_regla' => collect(['R1', 'R2', 'R3', 'R4', 'R5'])->mapWithKeys(fn ($r) => [
                $r => count(array_filter($diagnostico, fn ($f) => in_array($r, $f['marcas_sistema'], true))),
            ])->all(),
        ];
    }

    /** @return array{0: string[], 1: string[]} [marcas, errores] */
    private function aplicarReglas(int $i, array $f, ContextoDiagnostico $ctx): array
    {
        $marcas = [];
        $errores = [];
        foreach ($this->reglas as $regla) {
            $r = $regla->evaluar($i, $f, $ctx);
            if ($r['marcar']) {
                $marcas[] = $regla->codigo();
            }
            array_push($errores, ...$r['errores']);
        }

        return [$marcas, $errores];
    }

    /** Errores en la decisión de depuración (resultado, id de origen, grupo de fusión). */
    private function erroresDeDecision(int $i, array $f, ?ResultadoMigracion $res, array $filas): array
    {
        $errores = [];
        if (! $res) {
            $errores[] = 'Resultado inválido: use conservada, corregida, fusionada, pendiente o excluida.';
        }
        $previos = array_slice(array_column($filas, 'id_origen'), 0, $i);
        if ($f['id_origen'] && in_array($f['id_origen'], $previos, true)) {
            $errores[] = 'Identificador de origen repetido.';
        }
        if ($res === ResultadoMigracion::FUSIONADA && ! $f['grupo_fusion']) {
            $errores[] = 'Las filas fusionadas deben indicar grupo_fusion.';
        }

        return $errores;
    }

    /** La primera fila de cada grupo de fusión es la que se carga. */
    private function principalesDeFusion(array $filas): array
    {
        $principales = [];
        foreach ($filas as $i => $f) {
            if (self::resultado($f) === ResultadoMigracion::FUSIONADA && $f['grupo_fusion'] && ! isset($principales[$f['grupo_fusion']])) {
                $principales[$f['grupo_fusion']] = $i;
            }
        }

        return $principales;
    }

    /** R1 dentro de un grupo de fusión es esperado; entre filas que se cargarían, bloquea. */
    private function codigoRepetidoEntreCargables(int $i, array $f, ContextoDiagnostico $ctx, array $principales): bool
    {
        $codigo = ContextoDiagnostico::codigo($f);
        if (! $codigo) {
            return false;
        }

        return collect($ctx->porCodigo[$codigo])
            ->reject(fn ($j) => $j === $i)
            ->contains(fn ($j) => self::seCarga(self::resultado($ctx->filas[$j]), ($principales[$ctx->filas[$j]['grupo_fusion'] ?? ''] ?? null) === $j));
    }

    private static function seCarga(?ResultadoMigracion $res, bool $principal): bool
    {
        return in_array($res, [ResultadoMigracion::CONSERVADA, ResultadoMigracion::CORREGIDA, ResultadoMigracion::PENDIENTE], true) || $principal;
    }

    private static function resultado(array $f): ?ResultadoMigracion
    {
        return ResultadoMigracion::tryFrom(mb_strtolower((string) $f['resultado']));
    }

    private static function marcasDeArchivo(?string $texto): array
    {
        preg_match_all('/R[1-5]/i', (string) $texto, $m);

        return array_map('strtoupper', $m[0]);
    }
}
