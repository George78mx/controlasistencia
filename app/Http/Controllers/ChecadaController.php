<?php

namespace App\Http\Controllers;

use App\Models\Checada;
use App\Models\Device;
use Illuminate\Http\Request;

class ChecadaController extends Controller
{
    public function index(Request $request)
    {
        $desde = $request->desde ?: now()->toDateString();
        $hasta = $request->hasta ?: now()->toDateString();

        $checadas = Checada::with(['empleado', 'device'])
            ->whereBetween('fecha_hora', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->when($request->device_id, fn ($q, $v) => $q->where('device_id', $v))
            ->when($request->q, fn ($q, $v) => $q->where(fn ($q) => $q->where('numero_empleado', $v)
                ->orWhereHas('empleado', fn ($q) => $q->buscar($v))))
            ->when($request->boolean('sin_empleado'), fn ($q) => $q->whereNull('empleado_id'))
            ->orderByDesc('fecha_hora')
            ->paginate(50)->withQueryString();

        return view('checadas.index', [
            'checadas' => $checadas,
            'equipos' => Device::orderBy('nombre')->get(),
            'desde' => $desde, 'hasta' => $hasta,
        ]);
    }
}
