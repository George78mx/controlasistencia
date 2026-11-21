<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checada extends Model
{
    protected $fillable = ['device_id', 'empleado_id', 'numero_empleado', 'fecha_hora', 'serial_no', 'tipo', 'metodo', 'major', 'minor', 'origen', 'raw'];
    protected $casts = ['fecha_hora' => 'datetime', 'raw' => 'array'];

    public const TIPOS = [
        'checkIn' => 'Entrada', 'checkOut' => 'Salida', 'breakOut' => 'Salida a comer',
        'breakIn' => 'Regreso de comer', 'overtimeIn' => 'Entrada T. extra', 'overtimeOut' => 'Salida T. extra',
    ];

    public function device() { return $this->belongsTo(Device::class); }

    /** Filtra por el equipo donde se checó, o por los equipos de una sucursal. */
    public function scopeDeSucursal($q, $sucursalId = null, $deviceId = null)
    {
        return $q->when($deviceId, fn ($q) => $q->where('device_id', $deviceId))
                 ->when($sucursalId && !$deviceId, fn ($q) => $q->whereIn('device_id', Device::where('sucursal_id', $sucursalId)->select('id')));
    }
    public function empleado() { return $this->belongsTo(Empleado::class); }

    public function getTipoTextoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ($this->tipo ?: '—');
    }

    public function getMetodoTextoAttribute(): string
    {
        $m = strtolower((string) $this->metodo);
        return match (true) {
            $m === '' => '—',
            str_contains($m, 'face') && !str_contains($m, 'or') => 'Rostro',
            str_contains($m, 'fp') && !str_contains($m, 'or') => 'Huella',
            str_contains($m, 'card') && !str_contains($m, 'or') => 'Tarjeta',
            default => $this->metodo,
        };
    }
}
