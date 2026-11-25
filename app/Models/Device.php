<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;

class Device extends Model
{
    protected $fillable = [
        'sucursal_id', 'nombre', 'ubicacion', 'ip', 'puerto', 'usar_https', 'usuario', 'password', 'activo',
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

    public function sucursal() { return $this->belongsTo(Sucursal::class); }
    public function checadas() { return $this->hasMany(Checada::class); }
    public function logs() { return $this->hasMany(DeviceSyncLog::class)->latest(); }
    public function empleados() { return $this->hasMany(Empleado::class); }

    /** Guarda la contraseña cifrada con la APP_KEY actual sin leer la anterior. */
    public function guardarPassword(string $password): void
    {
        $cifrada = Crypt::encryptString($password);
        static::whereKey($this->id)->update(['password' => $cifrada]);
        $this->setRawAttributes(array_merge($this->getAttributes(), ['password' => $cifrada]), true);
    }

    /** Contraseña en claro; si se cifró con otra APP_KEY explica cómo corregirlo. */
    public function passwordDescifrada(): string
    {
        try {
            return (string) $this->password;
        } catch (DecryptException) {
            throw new RuntimeException('La contraseña de este equipo se guardó con otra llave (APP_KEY), por ejemplo al copiar la base de datos de otra instalación. Edita el equipo y vuelve a capturar su contraseña.');
        }
    }

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
