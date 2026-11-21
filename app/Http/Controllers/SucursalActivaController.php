<?php

namespace App\Http\Controllers;

use App\Support\SucursalActiva;
use Illuminate\Http\Request;

class SucursalActivaController extends Controller
{
    public function cambiar(Request $request)
    {
        $data = $request->validate(['sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id']]);
        SucursalActiva::cambiar($data['sucursal_id'] ?? null);

        // Regresa a la misma página quitando filtros que dependen de la sucursal anterior
        $anterior = url()->previous();
        $partes = parse_url($anterior);
        parse_str($partes['query'] ?? '', $query);
        unset($query['device_id'], $query['sucursal_id'], $query['page'], $query['empleado_id']);
        $destino = strtok($anterior, '?').($query ? '?'.http_build_query($query) : '');

        return redirect($destino);
    }
}
