<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioDia extends Model
{
    public $timestamps = false;
    protected $fillable = ['horario_id', 'dia_semana', 'laborable', 'entrada', 'salida'];
    protected $casts = ['laborable' => 'boolean'];

    public function horario() { return $this->belongsTo(Horario::class); }

    /** El turno termina al día siguiente (turno nocturno). */
    public function cruzaMedianoche(): bool
    {
        return $this->entrada && $this->salida && $this->salida <= $this->entrada;
    }
}
