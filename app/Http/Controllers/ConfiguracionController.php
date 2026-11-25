<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function edit()
    {
        $valores = collect(Setting::DEFAULTS)->mapWithKeys(fn ($v, $k) => [$k => Setting::get($k)]);
        return view('configuracion', compact('valores'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'empresa_nombre' => ['required', 'string', 'max:150'],
            'intervalo_minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            'margen_minutos' => ['required', 'integer', 'min:0', 'max:1440'],
            'dias_carga_inicial' => ['required', 'integer', 'min:0', 'max:365'],
            'timeout_segundos' => ['required', 'integer', 'min:5', 'max:120'],
            'retardos_por_falta' => ['required', 'integer', 'min:0', 'max:30'],
            'ventana_salida_minutos' => ['required', 'integer', 'min:0', 'max:720'],
            'checada_minima_minutos' => ['required', 'integer', 'min:0', 'max:120'],
        ]);
        foreach ($data as $k => $v) Setting::put($k, $v);
        return back()->with('ok', 'Configuración guardada. Los cambios aplican desde la siguiente consulta a los equipos.');
    }
}
