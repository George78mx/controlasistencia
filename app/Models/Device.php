<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Device extends Model
{
    protected $fillable = [
        'nombre', 'ubicacion', 'ip', 'puerto', 'usar_https', 'usuario', 'password', 'activo',
        'intervalo_minutos', 'modelo', 'numero_serie', 'firmware', 'mac', 'en_linea',
        'ultima_conexion_at', 'ultima_sincronizacion_at', 'descargado_hasta', 'ultimo_error', 'notas',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'encrypted',
        'activo' => 'boolean',
        'usar_https' => 'boolean',
        'en_linea' => 'boolean',
        'ultima_conexion_at' => 'datetime',
        'ultima_sincronizacion_at' => 'datetime',
        'descargado_hasta' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Device $d) {
            $d->token_push = $d->token_push ?: Str::random(40);
        });
    }

    public function checadas() { return $this->hasMany(Checada::class); }
    public function logs() { return $this->hasMany(DeviceSyncLog::class)->latest(); }
    public function empleados() { return $this->hasMany(Empleado::class); }

    public function baseUrl(): string
    {
        $esquema = $this->usar_https ? 'https' : 'http';
        $puerto = (!$this->usar_https && $this->puerto == 80) || ($this->usar_https && $this->puerto == 443) ? '' : ':'.$this->puerto;
        return "{$esquema}://{$this->ip}{$puerto}";
    }

    public function intervalo(): int
    {
        return max(1, $this->intervalo_minutos ?: Setting::int('intervalo_minutos', 5));
    }

    public function tocaSincronizar(): bool
    {
        if (!$this->activo) return false;
        if (!$this->ultima_sincronizacion_at) return true;
        return $this->ultima_sincronizacion_at->copy()->addMinutes($this->intervalo())->subSeconds(20)->isPast();
    }

    public function proximaSincronizacion()
    {
        return $this->ultima_sincronizacion_at?->copy()->addMinutes($this->intervalo());
    }
}
