<?php

namespace App\Exports\Plantilla;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HojaInstrucciones implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string
    {
        return 'Instrucciones';
    }

    public function array(): array
    {
        $filas = [
            ['Plantilla de importación de empleados'],
            [''],
            ['1. Captura un empleado por fila en la hoja "Empleados". No cambies ni muevas los encabezados.'],
            ['2. Columnas en rojo son obligatorias: numero_empleado y nombre.'],
            ['3. numero_empleado debe ser igual al Employee No. de la terminal Hikvision.'],
            ['4. Si el número de empleado ya existe, se actualizan sus datos. Las celdas vacías NO borran lo que ya tenía.'],
            ['5. Departamento, puesto, horario y equipo deben escribirse igual que en la hoja "Catálogos" (hay listas desplegables).'],
            ['6. Fechas en formato dd/mm/aaaa. Activo: SI o NO.'],
            ['7. Borra la fila de ejemplo antes de importar.'],
            ['8. En la plataforma usa "Validar sin guardar" para revisar el archivo antes de importarlo.'],
            [''],
            ['Columna', 'Obligatoria', 'Descripción'],
        ];
        foreach (Columnas::LISTA as [$titulo, $obl, $ancho, $desc]) {
            $filas[] = [$titulo, $obl ? 'Sí' : 'No', $desc];
        }
        return $filas;
    }

    public function columnWidths(): array
    {
        return ['A' => 24, 'B' => 12, 'C' => 90];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true, 'size' => 14]], 12 => ['font' => ['bold' => true]]];
    }
}
