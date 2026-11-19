<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSyncLog extends Model
{
    protected $fillable = ['device_id', 'tipo', 'estado', 'rango_desde', 'rango_hasta', 'eventos_recibidos', 'eventos_nuevos', 'duracion_ms', 'mensaje'];
    protected $casts = ['rango_desde' => 'datetime', 'rango_hasta' => 'datetime'];

    public function device() { return $this->belongsTo(Device::class); }
}
