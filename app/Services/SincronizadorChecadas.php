<?php

namespace App\Services;

use App\Models\Checada;
use App\Models\Device;
use App\Models\DeviceSyncLog;
use App\Models\Empleado;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SincronizadorChecadas
{
    public function __construct(private CalculadoraAsistencia $calculadora) {}

    /**
     * Descarga las checadas de un equipo. Si no se da rango, toma desde lo último descargado
     * menos el margen de traslape, o los días de carga inicial si es la primera vez.
     */
    public function sincronizar(Device $device, string $tipo = 'programada', ?CarbonInterface $desde = null, ?CarbonInterface $hasta = null): DeviceSyncLog
    {
        $lock = Cache::lock("sync-device-{$device->id}", 600);
        if (!$lock->get()) {
            return $device->logs()->create(['tipo' => $tipo, 'estado' => 'omitida', 'mensaje' => 'Ya hay una descarga en curso para este equipo.']);
        }

        $inicio = microtime(true);
        $hasta = $hasta ? Carbon::instance($hasta) : now();
        if (!$desde) {
            $desde = $device->descargado_hasta
                ? $device->descargado_hasta->copy()->subMinutes(Setting::int('margen_minutos', 60))
                : now()->subDays(Setting::int('dias_carga_inicial', 7))->startOfDay();
        }
        $desde = Carbon::instance($desde);

        try {
            $cliente = new HikvisionClient($device);
            $recibidos = 0;
            $nuevos = 0;
            $afectados = []; // [empleado_id][fecha] = true

            // Se consulta por bloques de un día para no saturar al equipo
            $cursor = $desde->copy();
            while ($cursor < $hasta) {
                $fin = $cursor->copy()->endOfDay()->min($hasta);
                $eventos = $cliente->eventos($cursor, $fin);
                $recibidos += count($eventos);
                foreach ($eventos as $e) {
                    $r = $this->guardarEvento($device, $e, 'consulta');
                    if ($r) {
                        $nuevos++;
                        if ($r->empleado_id) $afectados[$r->empleado_id][$r->fecha_hora->toDateString()] = true;
                    }
                }
                $cursor = $fin->copy()->addSecond();
            }

            $device->forceFill([
                'en_linea' => true,
                'ultima_conexion_at' => now(),
                'ultima_sincronizacion_at' => now(),
                // Una descarga manual de fechas pasadas no hace retroceder el avance
                'descargado_hasta' => $device->descargado_hasta && $device->descargado_hasta > $hasta ? $device->descargado_hasta : $hasta,
                'ultimo_error' => null,
            ])->save();

            $this->recalcularAfectados($afectados);

            return $device->logs()->create([
                'tipo' => $tipo, 'estado' => 'ok', 'rango_desde' => $desde, 'rango_hasta' => $hasta,
                'eventos_recibidos' => $recibidos, 'eventos_nuevos' => $nuevos,
                'duracion_ms' => (int) ((microtime(true) - $inicio) * 1000),
                'mensaje' => $nuevos ? "Se guardaron $nuevos checadas nuevas." : 'Sin checadas nuevas.',
            ]);
        } catch (Throwable $ex) {
            $device->forceFill([
                'en_linea' => false,
                'ultima_sincronizacion_at' => now(), // respeta el intervalo aunque falle
                'ultimo_error' => mb_substr($ex->getMessage(), 0, 1000),
            ])->save();

            return $device->logs()->create([
                'tipo' => $tipo, 'estado' => 'error', 'rango_desde' => $desde, 'rango_hasta' => $hasta,
                'duracion_ms' => (int) ((microtime(true) - $inicio) * 1000),
                'mensaje' => mb_substr($ex->getMessage(), 0, 1000),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Guarda un evento ISAPI. Devuelve la checada si es nueva, null si ya existía o no es de empleado.
     */
    public function guardarEvento(Device $device, array $e, string $origen): ?Checada
    {
        $num = trim((string) ($e['employeeNoString'] ?? $e['employeeNo'] ?? ''));
        if ($num === '' || empty($e['time'])) return null;

        $fecha = Carbon::parse($e['time'])->setTimezone(config('app.timezone'));

        $existe = Checada::where('device_id', $device->id)
            ->where('numero_empleado', $num)
            ->where('fecha_hora', $fecha->format('Y-m-d H:i:s'))
            ->exists();
        if ($existe) return null;

        $empleadoId = Cache::remember("empleado-num-$num", 300, fn () => Empleado::withTrashed()->where('numero_empleado', $num)->value('id'));

        try {
            return Checada::create([
            'device_id'       => $device->id,
            'empleado_id'     => $empleadoId,
            'numero_empleado' => $num,
            'fecha_hora'      => $fecha,
            'serial_no'       => $e['serialNo'] ?? null,
            'tipo'            => $e['attendanceStatus'] ?? null,
            'metodo'          => $e['currentVerifyMode'] ?? null,
            'major'           => $e['major'] ?? null,
            'minor'           => $e['minor'] ?? null,
            'origen'          => $origen,
            'raw'             => $e,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return null; // llegó al mismo tiempo por push y por consulta
        }
    }

    public function recalcularAfectados(array $afectados): void
    {
        $hoy = now()->toDateString();
        foreach ($afectados as $empleadoId => $fechas) {
            $empleado = Empleado::with('horario.dias')->find($empleadoId);
            if (!$empleado) continue;
            foreach (array_keys($fechas) as $fecha) {
                if ($fecha > $hoy) continue;
                $this->calculadora->calcularDia($empleado, Carbon::parse($fecha));
                // Una checada de madrugada puede cerrar un turno nocturno del día anterior
                $this->calculadora->calcularDia($empleado, Carbon::parse($fecha)->subDay());
            }
        }
    }
}
