<?php

namespace App\Exports\Plantilla;

/** Columnas de la plantilla de empleados (el orden define la columna de Excel). */
class Columnas
{
    /** clave => [encabezado visible, obligatorio, ancho, descripción] */
    public const LISTA = [
        'numero_empleado'     => ['numero_empleado', true, 16, 'Igual al Employee No. registrado en la terminal. Letras, números, guion y guion bajo.'],
        'nombre'              => ['nombre', true, 20, 'Nombre(s).'],
        'apellido_paterno'    => ['apellido_paterno', false, 18, ''],
        'apellido_materno'    => ['apellido_materno', false, 18, ''],
        'rfc'                 => ['rfc', false, 15, '12 o 13 caracteres.'],
        'curp'                => ['curp', false, 20, '18 caracteres.'],
        'nss'                 => ['nss', false, 13, '11 dígitos (la columna es de texto para conservar ceros a la izquierda).'],
        'fecha_nacimiento'    => ['fecha_nacimiento', false, 15, 'dd/mm/aaaa'],
        'genero'              => ['genero', false, 9, 'M, F o X.'],
        'email'               => ['email', false, 26, ''],
        'telefono'            => ['telefono', false, 14, ''],
        'direccion'           => ['direccion', false, 30, ''],
        'departamento'        => ['departamento', false, 20, 'Nombre exacto del catálogo (hoja Catálogos).'],
        'puesto'              => ['puesto', false, 20, 'Nombre exacto del catálogo (hoja Catálogos).'],
        'horario'             => ['horario', false, 28, 'Nombre exacto del horario. Necesario para calcular retardos y faltas.'],
        'sucursal'            => ['sucursal', false, 20, 'Nombre de la sucursal. Si la dejas vacía, toma la del equipo.'],
        'equipo'              => ['equipo', false, 18, 'Nombre del equipo principal.'],
        'fecha_ingreso'       => ['fecha_ingreso', false, 14, 'dd/mm/aaaa'],
        'fecha_baja'          => ['fecha_baja', false, 14, 'dd/mm/aaaa. Déjala vacía si sigue laborando.'],
        'activo'              => ['activo', false, 8, 'SI o NO. Vacío = SI para nuevos.'],
        'contacto_emergencia' => ['contacto_emergencia', false, 24, ''],
        'telefono_emergencia' => ['telefono_emergencia', false, 16, ''],
        'notas'               => ['notas', false, 30, ''],
    ];

    /** Columnas que se guardan como texto en Excel. */
    public const TEXTO = ['numero_empleado', 'rfc', 'curp', 'nss', 'telefono', 'telefono_emergencia'];

    public const FECHAS = ['fecha_nacimiento', 'fecha_ingreso', 'fecha_baja'];

    public static function letra(string $clave): string
    {
        $i = array_search($clave, array_keys(self::LISTA), true);
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
    }
}
