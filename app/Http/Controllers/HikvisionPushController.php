<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\SincronizadorChecadas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recibe eventos en tiempo real enviados por el equipo (HTTP Listening / httpHosts).
 * URL: /hikvision/eventos/{token}. El equipo puede mandar JSON directo o multipart con el campo "event_log".
 */
class HikvisionPushController extends Controller
{
    public function recibir(Request $request, string $token, SincronizadorChecadas $sync)
    {
        $device = Device::where('token_push', $token)->first();
        if (!$device) return response('Not found', 404);

        $payload = null;
        foreach (['event_log', 'AccessControllerEvent'] as $campo) {
            if ($request->has($campo) && is_string($request->input($campo))) {
                $payload = json_decode($request->input($campo), true);
                break;
            }
        }
        if (!$payload) {
            foreach ($request->allFiles() as $file) {
                if (str_contains((string) $file->getMimeType(), 'json') || str_ends_with($file->getClientOriginalName(), '.json')) {
                    $payload = json_decode($file->get(), true);
                    break;
                }
            }
        }
        $payload ??= json_decode($request->getContent(), true);

        if (!is_array($payload)) return response('OK');

        $ev = $payload['AccessControllerEvent'] ?? null;
        if (!$ev) return response('OK'); // heartbeat u otro tipo de evento

        $evento = array_merge($ev, ['time' => $payload['dateTime'] ?? ($ev['time'] ?? null)]);
        try {
            $checada = $sync->guardarEvento($device, $evento, 'push');
            $device->forceFill(['en_linea' => true, 'ultima_conexion_at' => now()])->save();
            if ($checada?->empleado_id) {
                $sync->recalcularAfectados([$checada->empleado_id => [$checada->fecha_hora->toDateString() => true]]);
            }
        } catch (\Throwable $e) {
            Log::warning('Evento Hikvision no procesado: '.$e->getMessage());
        }
        return response('OK');
    }
}
