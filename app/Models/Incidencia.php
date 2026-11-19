<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $fillable = ['empleado_id', 'tipo_incidencia_id', 'fecha_inicio', 'fecha_fin', 'folio', 'motivo', 'documento', 'user_id'];
    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function empleado() { return $this->belongsTo(Empleado::class)->withTrashed(); }
    public function tipo() { return $this->belongsTo(TipoIncidencia::class, 'tipo_incidencia_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }

    public function getDiasAttribute(): int
    {
        return $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;
    }
}
