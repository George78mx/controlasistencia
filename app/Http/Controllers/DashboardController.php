<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Checada;
use App\Models\Device;
use App\Models\Empleado;
use App\Models\Incidencia;
use App\Services\CalculadoraAsistencia;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(CalculadoraAsistencia $calc)
    {
        // Mantiene al día el cálculo de hoy (máximo una vez cada 2 minutos)
        Cache::remember('dashboard-calculo-hoy', 120, function () use ($calc) {
            $calc->calcularRango(now(), now());
            return true;
        });

        $hoy = now()->toDateString();
        $conteoHoy = Asistencia::where('fecha', $hoy)->selectRaw('estatus, count(*) as total')->groupBy('estatus')->pluck('total', 'estatus');

        $desde = now()->subDays(13)->toDateString();
        $serie = Asistencia::whereBetween('fecha', [$desde, $hoy])
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

        return view('dashboard', [
            'totalEmpleados' => Empleado::activos()->count(),
            'conteoHoy' => $conteoHoy,
            'grafica' => $grafica,
            'equipos' => Device::orderBy('nombre')->get(),
            'ultimasChecadas' => Checada::with(['empleado', 'device'])->latest('fecha_hora')->limit(8)->get(),
            'sinHorario' => Empleado::activos()->whereNull('horario_id')->count(),
            'noRegistrados' => Checada::whereNull('empleado_id')->distinct('numero_empleado')->count('numero_empleado'),
            'retardosHoy' => Asistencia::with('empleado')->where('fecha', $hoy)->where('estatus', 'retardo')->orderByDesc('minutos_retardo')->limit(6)->get(),
            'incidenciasActivas' => Incidencia::with(['empleado', 'tipo'])->where('fecha_inicio', '<=', $hoy)->where('fecha_fin', '>=', $hoy)->limit(6)->get(),
        ]);
    }
}
