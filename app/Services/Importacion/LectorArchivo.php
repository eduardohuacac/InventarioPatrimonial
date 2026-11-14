<?php

namespace App\Services\Importacion;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Lee un archivo CSV o Excel de registros depurados y devuelve filas
 * normalizadas (claves = columnas de la plantilla).
 */
class LectorArchivo
{
    public const COLUMNAS = [
        'id_origen', 'codigo_patrimonial', 'codigo_catalogo_siga', 'descripcion', 'marca', 'modelo', 'serie',
        'ambiente', 'responsable', 'estado', 'fecha_verificacion', 'resultado', 'grupo_fusion',
        'reglas', 'dato_anterior', 'dato_nuevo', 'evidencia', 'autorizado_por', 'fecha_autorizacion',
    ];

    public const OBLIGATORIAS = ['id_origen', 'codigo_patrimonial', 'descripcion', 'ambiente', 'responsable', 'estado', 'fecha_verificacion', 'resultado'];

    public const MAX_FILAS = 5000;

    /** @return array{filas: array<int, array<string, ?string>>, faltantes: string[]} */
    public function leer(string $ruta, string $extension): array
    {
        $extension = strtolower($extension);
        if (in_array($extension, ['csv', 'txt'], true)) {
            $lector = new Csv;
            $lector->setInputEncoding(Csv::GUESS_ENCODING);
            $lector->setDelimiter($this->detectarDelimitador($ruta));
            $libro = $lector->load($ruta);
        } else {
            $lector = IOFactory::createReaderForFile($ruta);
            $lector->setReadDataOnly(true);
            $libro = $lector->load($ruta);
        }

        $hoja = $libro->getSheet(0);
        $datos = $hoja->toArray(null, true, false, false);
        $libro->disconnectWorksheets();

        $encabezado = array_map(fn ($c) => $this->normalizarEncabezado((string) $c), array_shift($datos) ?? []);
        $faltantes = array_values(array_diff(self::OBLIGATORIAS, $encabezado));

        $filas = [];
        foreach ($datos as $fila) {
            if (count(array_filter($fila, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // fila vacía
            }
            $registro = [];
            foreach (self::COLUMNAS as $col) {
                $i = array_search($col, $encabezado, true);
                $valor = $i === false ? null : ($fila[$i] ?? null);
                $registro[$col] = $this->limpiar($col, $valor);
            }
            $filas[] = $registro;
            if (count($filas) > self::MAX_FILAS) {
                break;
            }
        }

        return ['filas' => $filas, 'faltantes' => $faltantes];
    }

    private function normalizarEncabezado(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', '.' => '', '°' => '']);

        return preg_replace('/[^a-z0-9]+/', '_', $texto);
    }

    private function limpiar(string $columna, mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        if (in_array($columna, ['fecha_verificacion', 'fecha_autorizacion'], true)) {
            return $this->fecha($valor);
        }
        $texto = trim(preg_replace('/\s+/u', ' ', (string) $valor));

        return $texto === '' ? null : $texto;
    }

    /** Devuelve Y-m-d o el texto original (para que el diagnóstico lo marque R4). */
    private function fecha(mixed $valor): ?string
    {
        if (is_numeric($valor) && (float) $valor > 20000 && (float) $valor < 80000) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $valor))->toDateString();
        }
        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y'] as $formato) {
            $f = \DateTime::createFromFormat('!'.$formato, $texto);
            if ($f && $f->format($formato) === $texto) {
                return $f->format('Y-m-d');
            }
        }

        return $texto;
    }

    private function detectarDelimitador(string $ruta): string
    {
        $linea = (string) fgets(fopen($ruta, 'r'));

        return substr_count($linea, ';') > substr_count($linea, ',') ? ';' : ',';
    }
}
