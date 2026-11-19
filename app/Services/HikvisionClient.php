<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente ISAPI de solo lectura para terminales Hikvision (MinMoe).
 * Únicamente consulta información: no borra ni modifica nada en el equipo.
 */
class HikvisionClient
{
    public function __construct(private Device $device) {}

    private function http(): PendingRequest
    {
        return Http::withDigestAuth($this->device->usuario, (string) $this->device->password)
            ->timeout(Setting::int('timeout_segundos', 20))
            ->connectTimeout(8)
            ->withoutVerifying()
            ->acceptJson()
            ->baseUrl($this->device->baseUrl());
    }

    private function revisar($res, string $accion): array
    {
        if ($res->status() === 401) {
            throw new RuntimeException("$accion: usuario o contraseña incorrectos (HTTP 401).");
        }
        if ($res->failed()) {
            $msg = $res->json('subStatusCode') ?: $res->json('statusString') ?: mb_substr(strip_tags($res->body()), 0, 200);
            throw new RuntimeException("$accion: HTTP {$res->status()} {$msg}");
        }
        return $res->json() ?? [];
    }

    /** GET /ISAPI/System/deviceInfo (responde en XML). */
    public function deviceInfo(): array
    {
        $res = $this->http()->accept('application/xml')->get('/ISAPI/System/deviceInfo');
        if ($res->status() === 401) throw new RuntimeException('Usuario o contraseña incorrectos (HTTP 401).');
        if ($res->failed()) throw new RuntimeException("deviceInfo: HTTP {$res->status()}");

        $xml = @simplexml_load_string($res->body());
        if (!$xml) {
            return $res->json() ?? [];
        }
        $arr = json_decode(json_encode($xml), true);
        return [
            'deviceName'      => $arr['deviceName'] ?? null,
            'model'           => $arr['model'] ?? null,
            'serialNumber'    => $arr['serialNumber'] ?? null,
            'macAddress'      => $arr['macAddress'] ?? null,
            'firmwareVersion' => trim(($arr['firmwareVersion'] ?? '').' '.($arr['firmwareReleasedDate'] ?? '')),
        ];
    }

    public function capacidadesEventos(): array
    {
        return $this->revisar($this->http()->get('/ISAPI/AccessControl/AcsEvent/capabilities', ['format' => 'json']), 'AcsEvent/capabilities');
    }

    /**
     * Descarga todos los eventos de control de acceso (major 5) del rango, paginando de 30 en 30.
     * @return array<int, array> lista de eventos (InfoList)
     */
    public function eventos(CarbonInterface $desde, CarbonInterface $hasta, int $minor = 0): array
    {
        $eventos = [];
        $pos = 0;
        $searchId = (string) \Illuminate\Support\Str::uuid();
        $vueltas = 0;

        do {
            $data = $this->revisar($this->http()->post('/ISAPI/AccessControl/AcsEvent?format=json', [
                'AcsEventCond' => [
                    'searchID'             => $searchId,
                    'searchResultPosition' => $pos,
                    'maxResults'           => 30,
                    'major'                => 5,
                    'minor'                => $minor,
                    'startTime'            => $desde->format('Y-m-d\TH:i:sP'),
                    'endTime'              => $hasta->format('Y-m-d\TH:i:sP'),
                ],
            ]), 'AcsEvent');

            $bloque = $data['AcsEvent'] ?? [];
            $lista = $bloque['InfoList'] ?? [];
            $eventos = array_merge($eventos, $lista);
            $estado = $bloque['responseStatusStrg'] ?? 'OK';
            $num = (int) ($bloque['numOfMatches'] ?? count($lista));
            $pos += $num;
            $vueltas++;
        } while ($estado === 'MORE' && $num > 0 && $vueltas < 2000);

        return $eventos;
    }

    /** Lista de usuarios dados de alta en la terminal (UserInfo/Search). */
    public function usuarios(): array
    {
        $usuarios = [];
        $pos = 0;
        $searchId = (string) \Illuminate\Support\Str::uuid();
        $vueltas = 0;
        do {
            $data = $this->revisar($this->http()->post('/ISAPI/AccessControl/UserInfo/Search?format=json', [
                'UserInfoSearchCond' => [
                    'searchID' => $searchId,
                    'searchResultPosition' => $pos,
                    'maxResults' => 30,
                ],
            ]), 'UserInfo/Search');
            $bloque = $data['UserInfoSearch'] ?? [];
            $lista = $bloque['UserInfo'] ?? [];
            $usuarios = array_merge($usuarios, $lista);
            $num = (int) ($bloque['numOfMatches'] ?? count($lista));
            $pos += $num;
            $vueltas++;
        } while (($bloque['responseStatusStrg'] ?? 'OK') === 'MORE' && $num > 0 && $vueltas < 500);

        return $usuarios;
    }
}
