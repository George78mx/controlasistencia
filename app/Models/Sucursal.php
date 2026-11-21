<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';
    protected $fillable = ['nombre', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function equipos() { return $this->hasMany(Device::class); }
    public function empleados() { return $this->hasMany(Empleado::class); }
}
