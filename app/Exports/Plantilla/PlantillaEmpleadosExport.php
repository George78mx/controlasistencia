<?php

namespace App\Exports\Plantilla;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PlantillaEmpleadosExport implements WithMultipleSheets
{
    public function __construct(private bool $incluirEmpleados = false) {}

    public function sheets(): array
    {
        return [
            new HojaEmpleados($this->incluirEmpleados),
            new HojaCatalogos(),
            new HojaInstrucciones(),
        ];
    }
}
