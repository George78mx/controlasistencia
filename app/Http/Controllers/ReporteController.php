<?php

namespace App\Http\Controllers;

use App\Exports\VistaExport;
use App\Models\Asistencia;
use App\Models\Checada;
use App\Models\Departamento;
use App\Models\Device;
use App\Models\Empleado;
use App\Models\Incidencia;
use App\Models\Setting;
use App\Models\Sucursal;
use App\Support\SucursalActiva;
use App\Models\TipoIncidencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public const TIPOS = [
        'general'     => ['Asistencia general', 'Resumen por empleado: asistencias, retardos, faltas e incidencias del periodo.', 'people', 'primary'],
        'kardex'      => ['Kárdex individual', 'Detalle día por día de un empleado con entradas, salidas y retardos.', 'person-vcard', 'info'],
        'retardos'    => ['Retardos', 'Todos los retardos del periodo con los minutos de cada uno.', 'alarm', 'warning'],
        'faltas'      => ['Faltas', 'Faltas del periodo, incluidas las generadas por retardo.', 'x-octagon', 'danger'],
        'incidencias' => ['Incidencias', 'Vacaciones, incapacidades, permisos y demás justificaciones.', 'clipboard2-check', 'success'],
        'checadas'    => ['Bitácora de checadas', 'Registros crudos descargados de los equipos.', 'fingerprint', 'secondary'],
    ];

    public function index()
    {
        return view('reportes.index', ['tipos' => self::TIPOS]);
    }

    public function ver(Request $request, string $tipo)
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'formato' => ['nullable', 'in:html,pdf,excel'],
        ]);

        $filtros = [
            'desde' => Carbon::parse($request->desde ?: now()->startOfMonth()->toDateString()),
            'hasta' => Carbon::parse($request->hasta ?: now()->toDateString()),
            // Si el formulario no manda sucursal, se usa la elegida en la barra superior
            'sucursal_id' => $request->has('sucursal_id') ? ($request->sucursal_id ?: null) : SucursalActiva::id(),
            'departamento_id' => $request->departamento_id,
            'device_id' => $request->device_id,
            'empleado_id' => $request->empleado_id,
            'tipo_incidencia_id' => $request->tipo_incidencia_id,
        ];

        $datos = $this->{'datos'.ucfirst($tipo)}($filtros);
        $datos += [
            'tipo' => $tipo,
            'titulo' => self::TIPOS[$tipo][0],
            'filtros' => $filtros,
            'empresa' => Setting::get('empresa_nombre'),
            'periodo' => $filtros['desde']->format('d/m/Y').' al '.$filtros['hasta']->format('d/m/Y'),
            'retardosPorFalta' => Setting::int('retardos_por_falta', 3),
            'filtroTexto' => $this->filtroTexto($filtros),
        ];

        $formato = $request->formato ?: 'html';
        if ($formato !== 'html') {
            abort_unless($request->user()->can('reportes.exportar'), 403);
            $archivo = Str::slug($datos['titulo']).'_'.$filtros['desde']->format('Ymd').'-'.$filtros['hasta']->format('Ymd');
            @set_time_limit(300);
            if ($formato === 'pdf') {
                return Pdf::loadView('reportes.pdf', $datos)
                    ->setPaper('letter', in_array($tipo, ['general', 'kardex', 'checadas', 'retardos', 'faltas']) ? 'landscape' : 'portrait')
                    ->download("$archivo.pdf");
            }
            return Excel::download(new VistaExport('reportes.excel', $datos, $datos['titulo']), "$archivo.xlsx");
        }

        return view('reportes.ver', $datos + [
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'equipos' => Device::when($filtros['sucursal_id'], fn ($q, $s) => $q->where('sucursal_id', $s))->orderBy('nombre')->get(),
            'empleados' => Empleado::withTrashed()->deSucursal($filtros['sucursal_id'])->orderBy('nombre')->get(),
            'tiposIncidencia' => TipoIncidencia::orderBy('nombre')->get(),
        ]);
    }

    private function empleadosQuery(array $f)
    {
        return Empleado::withTrashed()
            ->when($f['sucursal_id'] ?? null, fn ($q, $v) => $q->where('sucursal_id', $v))
            ->when($f['departamento_id'], fn ($q, $v) => $q->where('departamento_id', $v))
            ->when($f['device_id'], fn ($q, $v) => $q->where('device_id', $v))
            ->when($f['empleado_id'], fn ($q, $v) => $q->whereKey($v));
    }

    private function asistenciasQuery(array $f)
    {
        return Asistencia::with(['empleado.departamento', 'empleado.puesto', 'incidencia.tipo'])
            ->whereBetween('fecha', [$f['desde']->toDateString(), $f['hasta']->toDateString()])
            ->whereIn('empleado_id', $this->empleadosQuery($f)->select('id'));
    }

    private function datosGeneral(array $f): array
    {
        $rpf = Setting::int('retardos_por_falta', 3);
        $filas = $this->asistenciasQuery($f)->get()->groupBy('empleado_id')->map(function ($regs) use ($rpf) {
            $c = $regs->countBy('estatus');
            $laborables = ($c['asistencia'] ?? 0) + ($c['retardo'] ?? 0) + ($c['falta'] ?? 0) + ($c['justificada'] ?? 0);
            $retardos = $c['retardo'] ?? 0;
            $faltasPorRetardo = $rpf > 0 ? intdiv($retardos, $rpf) : 0;
            $asistio = ($c['asistencia'] ?? 0) + $retardos;
            return (object) [
                'empleado' => $regs->first()->empleado,
                'laborables' => $laborables,
                'asistencias' => $c['asistencia'] ?? 0,
                'retardos' => $retardos,
                'minutos_retardo' => $regs->where('estatus', 'retardo')->sum('minutos_retardo'),
                'faltas' => $c['falta'] ?? 0,
                'faltas_por_retardo' => $faltasPorRetardo,
                'faltas_totales' => ($c['falta'] ?? 0) + $faltasPorRetardo,
                'justificadas' => $c['justificada'] ?? 0,
                'descansos' => ($c['descanso'] ?? 0) + ($c['festivo'] ?? 0),
                'horas' => $regs->sum('minutos_trabajados'),
                'porcentaje' => $laborables ? round(($asistio + ($c['justificada'] ?? 0)) * 100 / $laborables, 1) : 0,
            ];
        })->sortBy(fn ($r) => $r->empleado?->nombre_completo)->values();

        return ['filas' => $filas];
    }

    private function datosKardex(array $f): array
    {
        $empleado = $f['empleado_id'] ? Empleado::withTrashed()->with(['departamento', 'puesto', 'horario'])->find($f['empleado_id']) : null;
        $registros = $empleado ? $this->asistenciasQuery($f)->orderBy('fecha')->get() : collect();
        $rpf = Setting::int('retardos_por_falta', 3);
        $c = $registros->countBy('estatus');
        return [
            'empleado' => $empleado,
            'registros' => $registros,
            'resumen' => [
                'Asistencias' => $c['asistencia'] ?? 0,
                'Retardos' => $c['retardo'] ?? 0,
                'Minutos de retardo' => $registros->where('estatus', 'retardo')->sum('minutos_retardo'),
                'Faltas' => $c['falta'] ?? 0,
                'Faltas por retardos' => $rpf > 0 ? intdiv($c['retardo'] ?? 0, $rpf) : 0,
                'Justificadas' => $c['justificada'] ?? 0,
                'Descansos / festivos' => ($c['descanso'] ?? 0) + ($c['festivo'] ?? 0),
                'Horas trabajadas' => Asistencia::minutosAHoras($registros->sum('minutos_trabajados')),
            ],
        ];
    }

    private function datosRetardos(array $f): array
    {
        return ['registros' => $this->asistenciasQuery($f)->where(fn ($q) => $q->where('estatus', 'retardo')->orWhere('falta_por_retardo', true))
            ->orderBy('fecha')->get()];
    }

    private function datosFaltas(array $f): array
    {
        return ['registros' => $this->asistenciasQuery($f)->where('estatus', 'falta')->orderBy('fecha')->get()];
    }

    private function datosIncidencias(array $f): array
    {
        return ['registros' => Incidencia::with(['empleado.departamento', 'tipo', 'usuario'])
            ->whereIn('empleado_id', $this->empleadosQuery($f)->select('id'))
            ->when($f['tipo_incidencia_id'], fn ($q, $v) => $q->where('tipo_incidencia_id', $v))
            ->where('fecha_inicio', '<=', $f['hasta']->toDateString())
            ->where('fecha_fin', '>=', $f['desde']->toDateString())
            ->orderBy('fecha_inicio')->get()];
    }

    private function datosChecadas(array $f): array
    {
        $q = Checada::with(['empleado.departamento', 'device'])
            ->whereBetween('fecha_hora', [$f['desde']->copy()->startOfDay(), $f['hasta']->copy()->endOfDay()])
            ->deSucursal($f['sucursal_id'], $f['device_id'])
            ->when($f['empleado_id'] || $f['departamento_id'], fn ($q) => $q->whereIn('empleado_id',
                $this->empleadosQuery(array_merge($f, ['device_id' => null, 'sucursal_id' => null]))->select('id')))
            ->orderBy('fecha_hora');
        $total = (clone $q)->count();
        return ['registros' => $q->limit(20000)->get(), 'total' => $total];
    }

    private function filtroTexto(array $f): string
    {
        $t = [];
        if ($f['sucursal_id']) $t[] = 'Sucursal: '.Sucursal::find($f['sucursal_id'])?->nombre;
        if ($f['departamento_id']) $t[] = 'Departamento: '.Departamento::find($f['departamento_id'])?->nombre;
        if ($f['device_id']) $t[] = 'Equipo: '.Device::find($f['device_id'])?->nombre;
        if ($f['empleado_id']) $t[] = 'Empleado: '.Empleado::withTrashed()->find($f['empleado_id'])?->nombre_completo;
        if ($f['tipo_incidencia_id']) $t[] = 'Tipo: '.TipoIncidencia::find($f['tipo_incidencia_id'])?->nombre;
        return implode(' · ', $t);
    }
}
