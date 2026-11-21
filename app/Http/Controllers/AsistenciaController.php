<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Services\CalculadoraAsistencia;
use App\Support\SucursalActiva;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $fecha = Carbon::parse($request->fecha ?: now()->toDateString());
        $suc = SucursalActiva::id();
        $dev = $request->integer('device_id') ?: null;

        $registros = Asistencia::with(['empleado.departamento', 'empleado.puesto', 'incidencia.tipo'])
            ->where('fecha', $fecha->toDateString())
            ->deSucursal($suc, $dev)
            ->when($request->estatus, fn ($q, $v) => $q->where('estatus', $v))
            ->when($request->departamento_id, fn ($q, $v) => $q->whereHas('empleado', fn ($q) => $q->where('departamento_id', $v)))
            ->when($request->q, fn ($q, $v) => $q->whereHas('empleado', fn ($q) => $q->buscar($v)))
            ->get()
            ->sortBy(fn ($a) => $a->empleado?->nombre_completo)->values();

        $conteo = Asistencia::deSucursal($suc, $dev)->where('fecha', $fecha->toDateString())->selectRaw('estatus, count(*) total')->groupBy('estatus')->pluck('total', 'estatus');

        return view('asistencia.index', [
            'fecha' => $fecha,
            'registros' => $registros,
            'conteo' => $conteo,
            'departamentos' => Departamento::orderBy('nombre')->get(),
        ]);
    }

    public function recalcular(Request $request, CalculadoraAsistencia $calc)
    {
        $data = $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
        ]);
        $desde = Carbon::parse($data['desde']);
        $hasta = Carbon::parse($data['hasta']);
        if ($desde->diffInDays($hasta) > 120) {
            return back()->with('error', 'El rango máximo para recalcular es de 120 días.');
        }
        @set_time_limit(600);
        $n = $calc->calcularRango($desde, $hasta);
        return back()->with('ok', "Se recalcularon $n registros de asistencia.");
    }
}
