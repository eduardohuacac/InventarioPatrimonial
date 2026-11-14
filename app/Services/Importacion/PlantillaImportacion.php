<?php

namespace App\Services\Importacion;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Genera la plantilla Excel para la carga de registros depurados. */
class PlantillaImportacion
{
    public function generar(string $ruta): void
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet()->setTitle('Registros');
        $hoja->fromArray(LectorArchivo::COLUMNAS, null, 'A1');
        $hoja->fromArray([
            ['H1-002', '740880370001', '74088037', 'Mesa de madera para docente', null, null, null, 'Sala de profesores', 'Rosa Quispe Mamani', 'regular', '2026-11-05', 'corregida', null, 'R3', 'responsable vacío', 'Rosa Quispe Mamani', 'Ficha de verificación N.° 001', 'Dirección', '2026-11-06'],
            ['H1-003', '740880370002', '74088037', 'Mesa de madera para docente', null, null, null, 'Aula 101', 'Luis Huamán Ccori', 'bueno', '2026-11-05', 'conservada', null, null, null, null, 'Ficha de verificación N.° 001', 'Dirección', '2026-11-06'],
        ], null, 'A2');
        $hoja->getStyle('A1:S1')->getFont()->setBold(true);
        $hoja->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDE68A');
        $hoja->getStyle('J1:L1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDE68A');
        foreach (range('A', 'S') as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }

        $ayuda = $libro->createSheet()->setTitle('Instrucciones');
        $ayuda->fromArray([
            ['Columna', 'Descripción'],
            ['id_origen', 'Hoja + n.° de fila del archivo original (p. ej. H1-023). Obligatorio y único.'],
            ['codigo_patrimonial', 'Código patrimonial individual del bien. Obligatorio.'],
            ['codigo_catalogo_siga', 'Código del tipo de artículo en el catálogo SIGA (opcional).'],
            ['descripcion, marca, modelo, serie', 'Datos descriptivos. descripcion es obligatoria.'],
            ['ambiente', 'Nombre exacto de un ambiente registrado en el sistema.'],
            ['responsable', 'Nombre completo (o DNI) de una persona registrada y activa.'],
            ['estado', 'bueno, regular o malo.'],
            ['fecha_verificacion', 'Fecha de la última verificación física (dd/mm/aaaa o aaaa-mm-dd).'],
            ['resultado', 'conservada | corregida | fusionada | pendiente | excluida.'],
            ['grupo_fusion', 'Obligatorio si resultado = fusionada. La primera fila del grupo es la que se carga.'],
            ['reglas', 'Reglas detectadas en la depuración: R1;R2;R3;R4;R5.'],
            ['dato_anterior / dato_nuevo', 'Valor antes y después de la corrección autorizada.'],
            ['evidencia, autorizado_por, fecha_autorizacion', 'Sustento de la decisión de depuración.'],
            ['Nota', 'Las filas «pendiente» se cargan como «no ubicado» hasta su verificación física.'],
        ], null, 'A1');
        $ayuda->getStyle('A1:B1')->getFont()->setBold(true);
        $ayuda->getColumnDimension('A')->setWidth(42);
        $ayuda->getColumnDimension('B')->setWidth(95);

        (new Xlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();
    }
}
