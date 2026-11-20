<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Lee solo la primera hoja del archivo (hoja "Empleados" de la plantilla). */
class LecturaEmpleados implements WithMultipleSheets
{
    public Collection $filas;

    public function __construct()
    {
        $this->filas = collect();
    }

    public function sheets(): array
    {
        $lector = new class($this) implements ToCollection, WithHeadingRow, WithCalculatedFormulas {
            public function __construct(private LecturaEmpleados $padre) {}

            public function collection(Collection $rows)
            {
                $this->padre->filas = $rows;
            }
        };

        return [0 => $lector];
    }
}
