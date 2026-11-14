<?php

namespace App\Services\Reportes;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportadorExcel
{
    public function exportar(array $reporte, string $ruta): void
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet()->setTitle('Reporte');
        $hoja->setCellValue('A1', 'I.E. N.° 50023 «República de México» – '.$reporte['titulo']);
        $hoja->setCellValue('A2', $reporte['subtitulo']);
        $hoja->setCellValue('A3', 'Generado: '.$reporte['generado']->format('d/m/Y H:i').' · Total: '.$reporte['total']);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $fila = 5;
        $ultimaCol = chr(ord('A') + count($reporte['columnas']));
        foreach ($reporte['grupos'] as $grupo => $filas) {
            $hoja->setCellValue("A{$fila}", $grupo.' ('.count($filas).')');
            $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
            $fila++;
            $hoja->fromArray($reporte['columnas'], null, "A{$fila}");
            $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->getFont()->setBold(true);
            $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
            $fila++;
            foreach ($filas as $datos) {
                $hoja->fromArray(array_map(fn ($v) => (string) $v, $datos), null, "A{$fila}", true);
                $hoja->getCell("A{$fila}")->setValueExplicit((string) $datos[0], DataType::TYPE_STRING);
                $fila++;
            }
            $fila++;
        }
        foreach (range('A', $ultimaCol) as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }

        (new Xlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();
    }
}
