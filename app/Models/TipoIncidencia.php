<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIncidencia extends Model
{
    protected $table = 'tipos_incidencia';
    protected $fillable = ['clave', 'nombre', 'color', 'con_goce', 'activo'];
    protected $casts = ['con_goce' => 'boolean', 'activo' => 'boolean'];

    public function incidencias() { return $this->hasMany(Incidencia::class); }
}
