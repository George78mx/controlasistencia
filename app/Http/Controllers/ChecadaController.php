<?php

namespace App\Http\Controllers;

use App\Models\Checada;
use App\Models\Device;
use App\Support\SucursalActiva;
use Illuminate\Http\Request;

class ChecadaController extends Controller
{
    public function index(Request $request)
    {
        $desde = $request->desde ?: now()->toDateString();
        $hasta = $request->hasta ?: now()->toDateString();

        $checadas = Checada::with(['empleado', 'device'])
            ->whereBetween('fecha_hora', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->deSucursal(SucursalActiva::id(), $request->integer('device_id') ?: null)
            ->when($request->q, fn ($q, $v) => $q->where(fn ($q) => $q->where('numero_empleado', $v)
                ->orWhereHas('empleado', fn ($q) => $q->buscar($v))))
            ->when($request->boolean('sin_empleado'), fn ($q) => $q->whereNull('empleado_id'))
            ->orderByDesc('fecha_hora')
            ->paginate(50)->withQueryString();

        return view('checadas.index', [
            'checadas' => $checadas,
            'equipos' => Device::when(SucursalActiva::id(), fn ($q, $s) => $q->where('sucursal_id', $s))->orderBy('nombre')->get(),
            'desde' => $desde, 'hasta' => $hasta,
        ]);
    }
}
