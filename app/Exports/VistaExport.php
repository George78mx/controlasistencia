<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VistaExport implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    public function __construct(private string $vista, private array $datos, private string $hoja = 'Reporte') {}

    public function view(): View
    {
        return view($this->vista, $this->datos);
    }

    public function title(): string
    {
        return mb_substr($this->hoja, 0, 31);
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true, 'size' => 14]]];
    }
}
