<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Checada;
use App\Models\Device;
use App\Models\Empleado;
use App\Models\Incidencia;
use App\Models\Sucursal;
use App\Services\CalculadoraAsistencia;
use App\Support\SucursalActiva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request, CalculadoraAsistencia $calc)
    {
        // Mantiene al día el cálculo de hoy (máximo una vez cada 2 minutos)
        Cache::remember('dashboard-calculo-hoy', 120, function () use ($calc) {
            $calc->calcularRango(now(), now());
            return true;
        });

        $suc = SucursalActiva::id();
        $dev = $request->integer('device_id') ?: null;
        $hoy = now()->toDateString();

        $conteoHoy = Asistencia::deSucursal($suc, $dev)->where('fecha', $hoy)
            ->selectRaw('estatus, count(*) as total')->groupBy('estatus')->pluck('total', 'estatus');

        $desde = now()->subDays(13)->toDateString();
        $serie = Asistencia::deSucursal($suc, $dev)->whereBetween('fecha', [$desde, $hoy])
            ->selectRaw('fecha, estatus, count(*) as total')->groupBy('fecha', 'estatus')->get()
            ->groupBy(fn ($r) => $r->fecha->toDateString());
        $grafica = ['labels' => [], 'asistencia' => [], 'retardo' => [], 'falta' => [], 'justificada' => []];
        for ($d = now()->subDays(13); $d->toDateString() <= $hoy; $d->addDay()) {
            $f = $d->toDateString();
            $grafica['labels'][] = $d->translatedFormat('D d');
            foreach (['asistencia', 'retardo', 'falta', 'justificada'] as $e) {
                $grafica[$e][] = (int) optional($serie->get($f))->firstWhere('estatus', $e)?->total;
            }
        }

        // Resumen por sucursal (solo cuando se ven todas)
        $porSucursal = collect();
        if (!$suc) {
            $conteos = Asistencia::where('fecha', $hoy)
                ->join('empleados', 'empleados.id', '=', 'asistencias.empleado_id')
                ->selectRaw('empleados.sucursal_id, asistencias.estatus, count(*) as total')
                ->groupBy('empleados.sucursal_id', 'asistencias.estatus')->get()->groupBy('sucursal_id');
            $activos = Empleado::activos()->selectRaw('sucursal_id, count(*) total')->groupBy('sucursal_id')->pluck('total', 'sucursal_id');
            $porSucursal = Sucursal::where('activo', true)->orderBy('nombre')->get()
                ->map(function ($s) use ($conteos, $activos) {
                    $c = ($conteos->get($s->id) ?? collect())->pluck('total', 'estatus');
                    return (object) [
                        'sucursal' => $s,
                        'empleados' => $activos[$s->id] ?? 0,
                        'presentes' => ($c['asistencia'] ?? 0) + ($c['retardo'] ?? 0),
                        'retardos' => $c['retardo'] ?? 0,
                        'faltas' => $c['falta'] ?? 0,
                        'justificados' => $c['justificada'] ?? 0,
                        'pendientes' => $c['pendiente'] ?? 0,
                    ];
                });
            if (($activos[''] ?? 0) > 0) {
                $porSucursal->push((object) ['sucursal' => null, 'empleados' => $activos[''], 'presentes' => null,
                    'retardos' => null, 'faltas' => null, 'justificados' => null, 'pendientes' => null]);
            }
        }

        return view('dashboard', [
            'totalEmpleados' => Empleado::activos()->deSucursal($suc, $dev)->count(),
            'conteoHoy' => $conteoHoy,
            'grafica' => $grafica,
            'porSucursal' => $porSucursal,
            'equipos' => Device::when($suc, fn ($q) => $q->where('sucursal_id', $suc))->orderBy('nombre')->get(),
            'ultimasChecadas' => Checada::with(['empleado', 'device'])->deSucursal($suc, $dev)->latest('fecha_hora')->limit(8)->get(),
            'sinHorario' => Empleado::activos()->deSucursal($suc, $dev)->whereNull('horario_id')->count(),
            'sinSucursal' => $suc ? 0 : Empleado::activos()->whereNull('sucursal_id')->count(),
            'noRegistrados' => Checada::deSucursal($suc, $dev)->whereNull('empleado_id')->distinct('numero_empleado')->count('numero_empleado'),
            'retardosHoy' => Asistencia::with('empleado')->deSucursal($suc, $dev)->where('fecha', $hoy)->where('estatus', 'retardo')->orderByDesc('minutos_retardo')->limit(6)->get(),
            'incidenciasActivas' => Incidencia::with(['empleado', 'tipo'])->deSucursal($suc, $dev)->where('fecha_inicio', '<=', $hoy)->where('fecha_fin', '>=', $hoy)->limit(6)->get(),
        ]);
    }
}
