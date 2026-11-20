<?php

namespace App\Exports\Plantilla;

use App\Models\Departamento;
use App\Models\Device;
use App\Models\Horario;
use App\Models\Puesto;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HojaCatalogos implements FromArray, WithTitle, ShouldAutoSize, WithStyles
{
    private static array $listas = [];

    public static function listas(): array
    {
        return self::$listas ?: self::$listas = [
            'departamentos' => Departamento::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
            'puestos'       => Puesto::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
            'horarios'      => Horario::where('activo', true)->orderBy('nombre')->pluck('nombre')->all(),
            'equipos'       => Device::orderBy('nombre')->pluck('nombre')->all(),
        ];
    }

    public static function cuenta(string $lista): int
    {
        return count(self::listas()[$lista]);
    }

    public function title(): string
    {
        return 'Catálogos';
    }

    public function array(): array
    {
        $l = self::listas();
        $filas = [['Departamentos', 'Puestos', 'Horarios', 'Equipos']];
        $n = max(1, ...array_values(array_map('count', $l)));
        for ($i = 0; $i < $n; $i++) {
            $filas[] = [$l['departamentos'][$i] ?? null, $l['puestos'][$i] ?? null, $l['horarios'][$i] ?? null, $l['equipos'][$i] ?? null];
        }
        return $filas;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
