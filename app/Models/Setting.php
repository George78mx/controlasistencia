<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['clave', 'valor', 'descripcion'];

    public const DEFAULTS = [
        'empresa_nombre'        => ['Mi Empresa', 'Nombre de la empresa (aparece en reportes)'],
        'intervalo_minutos'     => ['5', 'Cada cuántos minutos se consultan los equipos'],
        'margen_minutos'        => ['60', 'Minutos hacia atrás que se vuelven a revisar en cada consulta (traslape)'],
        'dias_carga_inicial'    => ['7', 'Días hacia atrás que se descargan la primera vez que se conecta un equipo'],
        'timeout_segundos'      => ['20', 'Tiempo máximo de espera de respuesta del equipo'],
        'retardos_por_falta'    => ['3', 'Número de retardos que equivalen a una falta (0 = no aplica)'],
        'ventana_salida_minutos'=> ['60', 'Minutos antes de la salida programada desde los que una checada ya se toma como salida el mismo día'],
        'checada_minima_minutos'=> ['5', 'Checadas repetidas dentro de estos minutos se consideran la misma'],
    ];

    public static function get(string $clave, $default = null)
    {
        $all = Cache::rememberForever('settings.all', fn () => static::pluck('valor', 'clave')->all());
        return $all[$clave] ?? (static::DEFAULTS[$clave][0] ?? $default);
    }

    public static function int(string $clave, int $default = 0): int
    {
        return (int) static::get($clave, $default);
    }

    public static function put(string $clave, $valor): void
    {
        static::updateOrCreate(['clave' => $clave], [
            'valor' => $valor,
            'descripcion' => static::DEFAULTS[$clave][1] ?? null,
        ]);
        Cache::forget('settings.all');
    }
}
