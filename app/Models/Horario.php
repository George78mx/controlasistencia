<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $fillable = ['nombre', 'descripcion', 'tolerancia_minutos', 'minutos_para_falta', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    public function dias() { return $this->hasMany(HorarioDia::class)->orderBy('dia_semana'); }
    public function empleados() { return $this->hasMany(Empleado::class); }

    public function dia(int $isoDia): ?HorarioDia
    {
        return $this->dias->firstWhere('dia_semana', $isoDia);
    }

    public function resumen(): string
    {
        $lab = $this->dias->where('laborable', true);
        if ($lab->isEmpty()) return 'Sin días laborables';
        $grupos = $lab->groupBy(fn ($d) => substr($d->entrada, 0, 5).'–'.substr($d->salida, 0, 5));
        return $grupos->map(function ($dias, $horas) {
            $n = $dias->pluck('dia_semana')->map(fn ($d) => mb_substr(self::DIAS[$d], 0, 3))->implode(', ');
            return "$n $horas";
        })->implode(' · ');
    }
}
