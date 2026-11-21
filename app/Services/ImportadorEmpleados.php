<?php

namespace App\Services;

use App\Exports\Plantilla\Columnas;
use App\Models\Departamento;
use App\Models\Device;
use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Puesto;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Importa empleados desde la plantilla.
 * - Busca por numero_empleado: si existe lo actualiza (si se permite), si no lo crea.
 * - Las celdas vacías no borran datos de un empleado existente.
 * - Cada fila se valida por separado; las filas con error no detienen a las demás.
 */
class ImportadorEmpleados
{
    private array $catalogos = [];
    private array $sucursalDeEquipo = [];
    private array $porCrear = ['departamento' => [], 'puesto' => []];

    public function __construct(private CalculadoraAsistencia $calculadora) {}

    /**
     * @param Collection $filas filas con encabezados (salida de LecturaEmpleados)
     * @param array{actualizar: bool, crear_catalogos: bool, simular: bool} $op
     */
    public function importar(Collection $filas, array $op): array
    {
        $this->cargarCatalogos();
        $resultados = [];
        $vistos = [];
        $recalcular = [];

        if ($filas->isEmpty()) {
            return ['filas' => [], 'resumen' => $this->resumen([]), 'error' => 'El archivo no tiene filas de datos.'];
        }

        $faltantes = array_diff(['numero_empleado', 'nombre'], array_keys($filas->first()->toArray()));
        if ($faltantes) {
            return ['filas' => [], 'resumen' => $this->resumen([]),
                'error' => 'El archivo no tiene las columnas obligatorias: '.implode(', ', $faltantes).'. Descarga la plantilla y no cambies los encabezados.'];
        }

        foreach ($filas as $i => $fila) {
            $numFila = $i + 2; // fila 1 = encabezados
            $d = $this->normalizar($fila->toArray());

            if (!array_filter($d, fn ($v) => $v !== null)) continue; // fila vacía

            $r = ['fila' => $numFila, 'numero' => $d['numero_empleado'], 'nombre' => trim(($d['nombre'] ?? '').' '.($d['apellido_paterno'] ?? '')),
                  'accion' => null, 'mensajes' => []];

            if (str_contains(mb_strtolower((string) ($d['notas'] ?? '')), 'fila de ejemplo')) {
                $resultados[] = $r + ['accion' => 'omitido'] + ['mensajes' => ['Fila de ejemplo de la plantilla.']];
                continue;
            }

            if ($d['numero_empleado'] && isset($vistos[$d['numero_empleado']])) {
                $r['accion'] = 'error';
                $r['mensajes'][] = "El número de empleado se repite en la fila {$vistos[$d['numero_empleado']]}.";
                $resultados[] = $r;
                continue;
            }
            if ($d['numero_empleado']) $vistos[$d['numero_empleado']] = $numFila;

            $existente = $d['numero_empleado'] ? Empleado::withTrashed()->where('numero_empleado', $d['numero_empleado'])->first() : null;

            if ($existente?->trashed()) {
                $r['accion'] = 'error';
                $r['mensajes'][] = 'Ese número pertenece a un empleado eliminado; no se puede reutilizar.';
                $resultados[] = $r;
                continue;
            }
            if ($existente && !$op['actualizar']) {
                $r['accion'] = 'omitido';
                $r['mensajes'][] = 'Ya existe y se eligió no actualizar.';
                $resultados[] = $r;
                continue;
            }

            [$datos, $errores, $avisos] = $this->construir($d, $existente, $op);
            $r['mensajes'] = array_merge($errores, $avisos);

            if ($errores) {
                $r['accion'] = 'error';
                $resultados[] = $r;
                continue;
            }

            $r['accion'] = $existente ? 'actualizado' : 'creado';
            if ($existente) {
                $cambios = array_keys(array_diff_assoc(
                    array_map(fn ($v) => (string) $v, $datos),
                    array_map(fn ($v) => $v instanceof Carbon ? $v->toDateString() : (string) $v, $existente->only(array_keys($datos)))
                ));
                if (!$cambios) {
                    $r['accion'] = 'sin_cambios';
                    $resultados[] = $r;
                    continue;
                }
                $r['mensajes'][] = 'Cambia: '.implode(', ', $cambios).'.';
            }

            if (!$op['simular']) {
                try {
                    DB::transaction(function () use (&$datos, $existente, &$recalcular) {
                        $datos = $this->resolverCatalogosNuevos($datos);
                        if ($existente) {
                            $antesHorario = $existente->horario_id;
                            $existente->update($datos);
                            if ($antesHorario != $existente->horario_id) $recalcular[] = $existente->id;
                        } else {
                            $nuevo = Empleado::create($datos);
                            if ($nuevo->horario_id) $recalcular[] = $nuevo->id;
                        }
                    });
                } catch (Throwable $e) {
                    $r['accion'] = 'error';
                    $r['mensajes'] = ['No se pudo guardar: '.Str::limit($e->getMessage(), 200)];
                }
            }
            $resultados[] = $r;
        }

        if ($recalcular) {
            $empleados = Empleado::with('horario.dias')->whereIn('id', $recalcular)->get();
            $this->calculadora->calcularRango(now()->subDays(31), now(), $empleados);
        }

        return ['filas' => $resultados, 'resumen' => $this->resumen($resultados), 'error' => null];
    }

    /** Valida una fila y devuelve [datos para guardar, errores, avisos]. */
    private function construir(array $d, ?Empleado $existente, array $op): array
    {
        $errores = [];
        $avisos = [];

        $v = Validator::make($d, [
            'numero_empleado' => ['required', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
            'nombre' => [$existente ? 'nullable' : 'required', 'max:100'],
            'apellido_paterno' => ['nullable', 'max:100'],
            'apellido_materno' => ['nullable', 'max:100'],
            'rfc' => ['nullable', 'min:12', 'max:13'],
            'curp' => ['nullable', 'size:18'],
            'nss' => ['nullable', 'digits:11'],
            'genero' => ['nullable', 'in:M,F,X'],
            'email' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'max:20'],
            'telefono_emergencia' => ['nullable', 'max:20'],
            'direccion' => ['nullable', 'max:255'],
            'contacto_emergencia' => ['nullable', 'max:150'],
            'notas' => ['nullable', 'max:2000'],
        ], [
            'numero_empleado.regex' => 'El número de empleado solo admite letras, números, guion y guion bajo.',
            'genero.in' => 'Género debe ser M, F o X.',
        ]);
        $errores = $v->errors()->all();

        $datos = [];
        foreach (['numero_empleado', 'nombre', 'apellido_paterno', 'apellido_materno', 'rfc', 'curp', 'nss', 'genero', 'email',
                     'telefono', 'direccion', 'contacto_emergencia', 'telefono_emergencia', 'notas'] as $c) {
            if ($d[$c] !== null) $datos[$c] = in_array($c, ['rfc', 'curp']) ? mb_strtoupper($d[$c]) : $d[$c];
        }

        foreach (Columnas::FECHAS as $c) {
            if ($d[$c] === null) continue;
            $f = $this->fecha($d[$c]);
            $f ? $datos[$c] = $f : $errores[] = "La fecha de ".['fecha_nacimiento' => 'nacimiento', 'fecha_ingreso' => 'ingreso', 'fecha_baja' => 'baja'][$c]." no es válida ({$d[$c]}). Usa dd/mm/aaaa.";
        }
        $ingreso = $datos['fecha_ingreso'] ?? $existente?->fecha_ingreso?->toDateString();
        if (isset($datos['fecha_baja']) && $ingreso && $datos['fecha_baja'] < $ingreso) {
            $errores[] = 'La fecha de baja es anterior a la de ingreso.';
        }

        if ($d['activo'] !== null) {
            $a = $this->booleano($d['activo']);
            $a === null ? $errores[] = "Activo debe ser SI o NO ({$d['activo']})." : $datos['activo'] = $a;
        } elseif (!$existente) {
            $datos['activo'] = !isset($datos['fecha_baja']);
        }

        foreach (['departamento' => 'departamento_id', 'puesto' => 'puesto_id', 'horario' => 'horario_id', 'sucursal' => 'sucursal_id', 'equipo' => 'device_id'] as $c => $campo) {
            if ($d[$c] === null) continue;
            $id = $this->catalogos[$c][$this->clave($d[$c])] ?? null;
            if ($id) {
                $datos[$campo] = $id;
            } elseif (in_array($c, ['departamento', 'puesto']) && $op['crear_catalogos']) {
                $datos[$campo] = "nuevo:{$c}:{$d[$c]}";
                $avisos[] = "Se creará el {$c} «{$d[$c]}».";
            } else {
                $errores[] = ucfirst($c)." «{$d[$c]}» no existe en el catálogo.";
            }
        }

        // Sin sucursal en el archivo: se toma la del equipo (si el empleado no tenía una)
        if (empty($datos['sucursal_id']) && !empty($datos['device_id']) && !$existente?->sucursal_id) {
            $suc = $this->sucursalDeEquipo[$datos['device_id']] ?? null;
            if ($suc) $datos['sucursal_id'] = $suc;
        }

        if (!$errores && !$existente && empty($datos['horario_id'])) {
            $avisos[] = 'Sin horario: no se calcularán retardos ni faltas.';
        }

        return [$datos, $errores, $avisos];
    }

    private function resolverCatalogosNuevos(array $datos): array
    {
        foreach (['departamento_id' => Departamento::class, 'puesto_id' => Puesto::class] as $campo => $modelo) {
            if (isset($datos[$campo]) && is_string($datos[$campo]) && str_starts_with($datos[$campo], 'nuevo:')) {
                [, $tipo, $nombre] = explode(':', $datos[$campo], 3);
                $registro = $modelo::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
                $this->catalogos[$tipo][$this->clave($nombre)] = $registro->id;
                $datos[$campo] = $registro->id;
            }
        }
        return $datos;
    }

    private function normalizar(array $fila): array
    {
        $d = [];
        foreach (array_keys(Columnas::LISTA) as $c) {
            $v = $fila[$c] ?? null;
            if (is_int($v) && !in_array($c, Columnas::FECHAS, true)) $v = (string) $v;
            if (is_float($v) && floor($v) == $v && !in_array($c, Columnas::FECHAS, true)) $v = (string) (int) $v;
            if (is_string($v)) {
                $v = trim(preg_replace('/\s+/u', ' ', $v));
                if ($v === '') $v = null;
            }
            $d[$c] = $v;
        }
        if ($d['genero']) {
            $g = mb_strtoupper(mb_substr($d['genero'], 0, 1));
            $d['genero'] = ['H' => 'M', 'O' => 'X'][$g] ?? $g; // Hombre→M, Otro→X
        }
        if ($d['nss']) $d['nss'] = preg_replace('/\D/', '', $d['nss']);
        return $d;
    }

    /** Acepta fecha de Excel (número), dd/mm/aaaa, dd-mm-aaaa o aaaa-mm-dd. */
    private function fecha($v): ?string
    {
        if (is_numeric($v)) {
            $n = (float) $v;
            return ($n > 0 && $n < 80000) ? Carbon::instance(ExcelDate::excelToDateTimeObject($n))->toDateString() : null;
        }
        if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $v, $m)) {
            [$d, $mes, $a] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{4})[\/.-](\d{1,2})[\/.-](\d{1,2})$/', $v, $m)) {
            [$a, $mes, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }
        return checkdate($mes, $d, $a) && $a >= 1900 && $a <= 2100 ? sprintf('%04d-%02d-%02d', $a, $mes, $d) : null;
    }

    private function booleano($v): ?bool
    {
        $v = $this->clave((string) $v);
        return match ($v) {
            'si', 's', '1', 'true', 'verdadero', 'activo', 'x' => true,
            'no', 'n', '0', 'false', 'falso', 'inactivo' => false,
            default => null,
        };
    }

    private function clave(string $texto): string
    {
        return mb_strtolower(trim(Str::ascii($texto)));
    }

    private function cargarCatalogos(): void
    {
        $mapa = fn ($q) => $q->get(['id', 'nombre'])->mapWithKeys(fn ($r) => [$this->clave($r->nombre) => $r->id])->all();
        $this->catalogos = [
            'departamento' => $mapa(Departamento::query()),
            'puesto' => $mapa(Puesto::query()),
            'horario' => $mapa(Horario::query()),
            'equipo' => $mapa(Device::query()),
            'sucursal' => $mapa(Sucursal::query()),
        ];
        $this->sucursalDeEquipo = Device::pluck('sucursal_id', 'id')->all();
    }

    private function resumen(array $filas): array
    {
        $c = collect($filas)->countBy('accion');
        return [
            'creado' => $c['creado'] ?? 0,
            'actualizado' => $c['actualizado'] ?? 0,
            'sin_cambios' => $c['sin_cambios'] ?? 0,
            'omitido' => $c['omitido'] ?? 0,
            'error' => $c['error'] ?? 0,
            'total' => count($filas),
        ];
    }
}
