<?php

namespace App\Http\Controllers;

use App\Exports\Plantilla\Columnas;
use App\Exports\Plantilla\PlantillaEmpleadosExport;
use App\Imports\LecturaEmpleados;
use App\Services\ImportadorEmpleados;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportacionEmpleadosController extends Controller
{
    public function form()
    {
        return view('empleados.importar', [
            'columnas' => Columnas::LISTA,
            'resultado' => session('resultado_importacion'),
        ]);
    }

    public function plantilla(Request $request)
    {
        $conDatos = $request->boolean('con_empleados');
        $nombre = $conDatos ? 'empleados_'.now()->format('Ymd_His').'.xlsx' : 'plantilla_empleados.xlsx';
        return Excel::download(new PlantillaEmpleadosExport($conDatos), $nombre);
    }

    public function importar(Request $request, ImportadorEmpleados $importador)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'extensions:xlsx,xls,csv', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [
            'archivo.required' => 'Selecciona el archivo a importar.',
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'archivo.extensions' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
        ]);

        @set_time_limit(600);
        $opciones = [
            'actualizar' => $request->boolean('actualizar'),
            'crear_catalogos' => $request->boolean('crear_catalogos'),
            'simular' => $request->input('accion') === 'simular',
        ];

        try {
            $lectura = new LecturaEmpleados();
            Excel::import($lectura, $request->file('archivo'));
        } catch (Throwable $e) {
            return back()->with('error', 'No se pudo leer el archivo: '.$e->getMessage());
        }

        $resultado = $importador->importar($lectura->filas, $opciones);
        $resultado['opciones'] = $opciones;
        $resultado['archivo'] = $request->file('archivo')->getClientOriginalName();

        if ($resultado['error']) {
            return back()->with('error', $resultado['error'])->withInput();
        }

        $r = $resultado['resumen'];
        $mensaje = $opciones['simular']
            ? "Validación terminada (no se guardó nada): {$r['creado']} por crear, {$r['actualizado']} por actualizar, {$r['error']} con errores."
            : "Importación terminada: {$r['creado']} creados, {$r['actualizado']} actualizados, {$r['error']} con errores.";

        return redirect()->route('empleados.importar')
            ->with('resultado_importacion', $resultado)
            ->with($r['error'] ? 'error' : 'ok', $mensaje)
            ->withInput($request->except('archivo'));
    }
}
