<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero_empleado', 'nombre', 'apellido_paterno', 'apellido_materno', 'rfc', 'curp', 'nss',
        'fecha_nacimiento', 'genero', 'email', 'telefono', 'direccion', 'departamento_id', 'sucursal_id', 'puesto_id',
        'horario_id', 'device_id', 'fecha_ingreso', 'fecha_baja', 'activo', 'contacto_emergencia',
        'telefono_emergencia', 'foto', 'notas',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_baja' => 'date',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Vincula las checadas que llegaron antes de dar de alta al empleado
        static::saved(function (Empleado $e) {
            \Illuminate\Support\Facades\Cache::forget("empleado-num-{$e->numero_empleado}");
            Checada::where("numero_empleado", $e->numero_empleado)->whereNull("empleado_id")->update(["empleado_id" => $e->id]);
        });
    }

    public function departamento() { return $this->belongsTo(Departamento::class); }
    public function sucursal() { return $this->belongsTo(Sucursal::class); }
    public function puesto() { return $this->belongsTo(Puesto::class); }
    public function horario() { return $this->belongsTo(Horario::class); }
    public function device() { return $this->belongsTo(Device::class); }
    public function checadas() { return $this->hasMany(Checada::class); }
    public function asistencias() { return $this->hasMany(Asistencia::class); }
    public function incidencias() { return $this->hasMany(Incidencia::class); }

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->nombre, $this->apellido_paterno, $this->apellido_materno])));
    }

    public function getInicialesAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->nombre, 0, 1).mb_substr($this->apellido_paterno ?? '', 0, 1));
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? asset('storage/'.$this->foto) : null;
    }

    public function scopeActivos($q) { return $q->where('activo', true); }

    /** Filtra por sucursal y/o equipo principal (null = sin filtro). */
    public function scopeDeSucursal($q, $sucursalId = null, $deviceId = null)
    {
        return $q->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
                 ->when($deviceId, fn ($q) => $q->where('device_id', $deviceId));
    }

    public function scopeBuscar($q, ?string $texto)
    {
        if (!$texto) return $q;
        return $q->where(function ($q) use ($texto) {
            $q->where('numero_empleado', 'like', "%$texto%")
              ->orWhere('nombre', 'like', "%$texto%")
              ->orWhere('apellido_paterno', 'like', "%$texto%")
              ->orWhere('apellido_materno', 'like', "%$texto%")
              ->orWhere('rfc', 'like', "%$texto%")
              ->orWhere('curp', 'like', "%$texto%");
        });
    }

    /** ¿Estaba laborando (dado de alta) en la fecha dada? */
    public function laboraEn($fecha): bool
    {
        $f = $fecha instanceof \Carbon\Carbon ? $fecha->toDateString() : $fecha;
        if ($this->fecha_ingreso && $f < $this->fecha_ingreso->toDateString()) return false;
        if ($this->fecha_baja && $f > $this->fecha_baja->toDateString()) return false;
        return true;
    }
}
