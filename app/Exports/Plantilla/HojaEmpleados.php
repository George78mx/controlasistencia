<?php

namespace App\Exports\Plantilla;

use App\Models\Departamento;
use App\Models\Device;
use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Puesto;
use App\Models\Sucursal;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class HojaEmpleados implements FromArray, WithTitle, WithEvents
{
    private const FILAS_CON_LISTAS = 1000;

    public function __construct(private bool $incluirEmpleados) {}

    public function title(): string
    {
        return 'Empleados';
    }

    public function array(): array
    {
        $filas = [array_map(fn ($c) => $c[0], Columnas::LISTA)];

        if ($this->incluirEmpleados) {
            $empleados = Empleado::with(['departamento', 'puesto', 'horario', 'device', 'sucursal'])->orderBy('numero_empleado')->get();
            foreach ($empleados as $e) {
                $filas[] = [
                    $e->numero_empleado, $e->nombre, $e->apellido_paterno, $e->apellido_materno, $e->rfc, $e->curp, $e->nss,
                    $e->fecha_nacimiento?->format('d/m/Y'), $e->genero, $e->email, $e->telefono, $e->direccion,
                    $e->departamento?->nombre, $e->puesto?->nombre, $e->horario?->nombre, $e->sucursal?->nombre, $e->device?->nombre,
                    $e->fecha_ingreso?->format('d/m/Y'), $e->fecha_baja?->format('d/m/Y'), $e->activo ? 'SI' : 'NO',
                    $e->contacto_emergencia, $e->telefono_emergencia, $e->notas,
                ];
            }
        } else {
            $filas[] = [
                '1001', 'Ana María', 'López', 'Ruiz', 'LORA900101AB1', 'LORA900101MJCPZN09', '01234567890',
                '01/01/1990', 'F', 'ana.lopez@empresa.com', '3312345678', 'Av. Principal 123, Guadalajara',
                Departamento::orderBy('nombre')->value('nombre'), Puesto::orderBy('nombre')->value('nombre'),
                Horario::orderBy('nombre')->value('nombre'), Sucursal::orderBy('nombre')->value('nombre'), Device::orderBy('nombre')->value('nombre'),
                now()->format('d/m/Y'), '', 'SI', 'Juan López', '3398765432', 'Fila de ejemplo: bórrala antes de importar',
            ];
        }
        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();
                $ultima = Columnas::letra(array_key_last(Columnas::LISTA));
                $max = self::FILAS_CON_LISTAS + 1;

                // Encabezados
                $hoja->getStyle("A1:{$ultima}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                ]);
                foreach (Columnas::LISTA as $clave => [$titulo, $obligatorio, $ancho, $desc]) {
                    $col = Columnas::letra($clave);
                    $hoja->getColumnDimension($col)->setWidth($ancho);
                    if ($obligatorio) {
                        $hoja->getStyle("{$col}1")->getFill()->getStartColor()->setRgb('DC2626');
                    }
                    if ($desc) {
                        $hoja->getComment("{$col}1")->getText()->createTextRun($desc);
                    }
                    if (in_array($clave, Columnas::TEXTO, true) || in_array($clave, Columnas::FECHAS, true)) {
                        $hoja->getStyle("{$col}2:{$col}{$max}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                    }
                }
                $hoja->freezePane('C2');
                $hoja->setAutoFilter("A1:{$ultima}1");

                // Listas desplegables tomadas de la hoja Catálogos
                $listas = [
                    'departamento' => "'Catálogos'!\$A\$2:\$A\$".max(2, HojaCatalogos::cuenta('departamentos') + 1),
                    'puesto'       => "'Catálogos'!\$B\$2:\$B\$".max(2, HojaCatalogos::cuenta('puestos') + 1),
                    'horario'      => "'Catálogos'!\$C\$2:\$C\$".max(2, HojaCatalogos::cuenta('horarios') + 1),
                    'equipo'       => "'Catálogos'!\$D\$2:\$D\$".max(2, HojaCatalogos::cuenta('equipos') + 1),
                    'sucursal'     => "'Catálogos'!\$E\$2:\$E\$".max(2, HojaCatalogos::cuenta('sucursales') + 1),
                    'genero'       => '"M,F,X"',
                    'activo'       => '"SI,NO"',
                ];
                foreach ($listas as $clave => $formula) {
                    $col = Columnas::letra($clave);
                    $v = new DataValidation();
                    $v->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_WARNING)
                        ->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage(true)
                        ->setErrorTitle('Valor no reconocido')
                        ->setError('Elige un valor de la lista (hoja Catálogos).')
                        ->setFormula1($formula);
                    $hoja->setDataValidation("{$col}2:{$col}{$max}", $v);
                }
            },
        ];
    }
}
