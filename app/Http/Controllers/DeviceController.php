<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Empleado;
use App\Services\HikvisionClient;
use App\Services\SincronizadorChecadas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class DeviceController extends Controller
{
    public function index()
    {
        $equipos = Device::withCount(['checadas', 'checadas as checadas_hoy_count' => fn ($q) => $q->whereDate('fecha_hora', today())])
            ->orderBy('nombre')->get();
        return view('equipos.index', compact('equipos'));
    }

    public function show(Device $equipo)
    {
        return view('equipos.show', [
            'equipo' => $equipo,
            'logs' => $equipo->logs()->paginate(15),
            'checadas' => $equipo->checadas()->with('empleado')->latest('fecha_hora')->limit(15)->get(),
            'usuarios' => session('usuarios_equipo'),
        ]);
    }

    public function create()
    {
        return view('equipos.form', ['equipo' => new Device(['puerto' => 80, 'activo' => true, 'usuario' => 'admin'])]);
    }

    public function store(Request $request)
    {
        $equipo = Device::create($this->validar($request, true));
        return redirect()->route('equipos.show', $equipo)->with('ok', 'Equipo registrado. Usa "Probar conexión" para validar el acceso.');
    }

    public function edit(Device $equipo)
    {
        return view('equipos.form', compact('equipo'));
    }

    public function update(Request $request, Device $equipo)
    {
        $data = $this->validar($request, false);
        if (empty($data['password'])) unset($data['password']);
        $equipo->update($data);
        return redirect()->route('equipos.show', $equipo)->with('ok', 'Equipo actualizado.');
    }

    public function destroy(Device $equipo)
    {
        if ($equipo->checadas()->exists()) {
            $equipo->update(['activo' => false]);
            return redirect()->route('equipos.index')->with('ok', 'El equipo tiene checadas registradas; se desactivó en lugar de eliminarse.');
        }
        $equipo->delete();
        return redirect()->route('equipos.index')->with('ok', 'Equipo eliminado.');
    }

    public function regenerarToken(Device $equipo)
    {
        $equipo->forceFill(['token_push' => Str::random(40)])->save();
        return back()->with('ok', 'Se generó una nueva URL de eventos. Actualízala en el equipo.');
    }

    public function probar(Device $equipo)
    {
        try {
            $info = (new HikvisionClient($equipo))->deviceInfo();
            $equipo->forceFill([
                'modelo' => $info['model'] ?? $equipo->modelo,
                'numero_serie' => $info['serialNumber'] ?? $equipo->numero_serie,
                'firmware' => $info['firmwareVersion'] ?? $equipo->firmware,
                'mac' => $info['macAddress'] ?? $equipo->mac,
                'en_linea' => true,
                'ultima_conexion_at' => now(),
                'ultimo_error' => null,
            ])->save();
            $equipo->logs()->create(['tipo' => 'prueba', 'estado' => 'ok', 'mensaje' => 'Conexión correcta: '.($info['model'] ?? 'equipo').' '.($info['serialNumber'] ?? '')]);
            return back()->with('ok', 'Conexión correcta con '.($info['model'] ?? 'el equipo').'.');
        } catch (Throwable $e) {
            $equipo->forceFill(['en_linea' => false, 'ultimo_error' => $e->getMessage()])->save();
            $equipo->logs()->create(['tipo' => 'prueba', 'estado' => 'error', 'mensaje' => mb_substr($e->getMessage(), 0, 1000)]);
            return back()->with('error', 'No fue posible conectar: '.$e->getMessage());
        }
    }

    public function sincronizar(Request $request, Device $equipo, SincronizadorChecadas $sync)
    {
        $data = $request->validate(['desde' => ['nullable', 'date'], 'hasta' => ['nullable', 'date', 'after_or_equal:desde']]);
        @set_time_limit(600);
        $desde = !empty($data['desde']) ? Carbon::parse($data['desde'])->startOfDay() : null;
        $hasta = !empty($data['hasta']) ? Carbon::parse($data['hasta'])->endOfDay()->min(now()) : null;
        $log = $sync->sincronizar($equipo, 'manual', $desde, $hasta);

        return back()->with($log->estado === 'ok' ? 'ok' : 'error',
            $log->estado === 'ok'
                ? "Descarga terminada: {$log->eventos_recibidos} eventos leídos, {$log->eventos_nuevos} checadas nuevas."
                : 'Error al descargar: '.$log->mensaje);
    }

    /** Consulta (solo lectura) los usuarios registrados en la terminal. */
    public function usuarios(Device $equipo)
    {
        try {
            $usuarios = collect((new HikvisionClient($equipo))->usuarios())->map(fn ($u) => [
                'employeeNo' => (string) ($u['employeeNo'] ?? ''),
                'name' => $u['name'] ?? '',
                'userType' => $u['userType'] ?? '',
                'numOfFace' => $u['numOfFace'] ?? 0,
                'numOfFP' => $u['numOfFP'] ?? 0,
                'numOfCard' => $u['numOfCard'] ?? 0,
            ])->filter(fn ($u) => $u['employeeNo'] !== '')->values()->all();

            $existentes = Empleado::withTrashed()->whereIn('numero_empleado', array_column($usuarios, 'employeeNo'))->pluck('numero_empleado')->all();
            foreach ($usuarios as &$u) $u['existe'] = in_array($u['employeeNo'], $existentes, true);

            return redirect()->route('equipos.show', $equipo)->with('usuarios_equipo', $usuarios)
                ->with('ok', count($usuarios).' usuarios encontrados en el equipo.');
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible leer los usuarios: '.$e->getMessage());
        }
    }

    public function importar(Request $request, Device $equipo)
    {
        $data = $request->validate(['usuarios' => ['required', 'array'], 'usuarios.*' => ['string']]);
        $n = 0;
        foreach ($data['usuarios'] as $item) {
            [$num, $nombre] = array_pad(explode('|', $item, 2), 2, '');
            if (Empleado::withTrashed()->where('numero_empleado', $num)->exists()) continue;
            $partes = preg_split('/\s+/', trim($nombre ?: "Empleado $num"));
            Empleado::create([
                'numero_empleado' => $num,
                'nombre' => array_shift($partes) ?: "Empleado $num",
                'apellido_paterno' => array_shift($partes),
                'apellido_materno' => $partes ? implode(' ', $partes) : null,
                'device_id' => $equipo->id,
                'activo' => true,
            ]);
            $n++;
        }
        return redirect()->route('equipos.show', $equipo)->with('ok', "Se importaron $n empleados. Completa sus datos y asígnales un horario.");
    }

    private function validar(Request $request, bool $nuevo): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'ip' => ['required', 'string', 'max:100'],
            'puerto' => ['required', 'integer', 'min:1', 'max:65535'],
            'usuario' => ['required', 'string', 'max:64'],
            'password' => [$nuevo ? 'required' : 'nullable', 'string', 'max:128'],
            'intervalo_minutos' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['usar_https'] = $request->boolean('usar_https');
        $data['activo'] = $request->boolean('activo');
        return $data;
    }
}
