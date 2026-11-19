<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $fillable = [
        'empleado_id', 'fecha', 'horario_id', 'entrada_programada', 'salida_programada', 'entrada', 'salida',
        'num_checadas', 'minutos_retardo', 'minutos_salida_anticipada', 'minutos_trabajados', 'estatus',
        'falta_por_retardo', 'incidencia_id', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'entrada_programada' => 'datetime',
        'salida_programada' => 'datetime',
        'entrada' => 'datetime',
        'salida' => 'datetime',
        'falta_por_retardo' => 'boolean',
    ];

    public const ESTATUS = [
        'asistencia'  => ['Asistencia', 'success'],
        'retardo'     => ['Retardo', 'warning'],
        'falta'       => ['Falta', 'danger'],
        'justificada' => ['Justificada', 'info'],
        'descanso'    => ['Descanso', 'secondary'],
        'festivo'     => ['Festivo', 'primary'],
        'pendiente'   => ['Pendiente', 'light'],
        'sin_horario' => ['Sin horario', 'dark'],
    ];

    public function empleado() { return $this->belongsTo(Empleado::class)->withTrashed(); }
    public function horario() { return $this->belongsTo(Horario::class); }
    public function incidencia() { return $this->belongsTo(Incidencia::class); }

    public function getEstatusTextoAttribute(): string
    {
        $t = self::ESTATUS[$this->estatus][0] ?? $this->estatus;
        if ($this->estatus === 'justificada' && $this->incidencia?->tipo) $t = $this->incidencia->tipo->nombre;
        if ($this->falta_por_retardo) $t .= ' (por retardo)';
        return $t;
    }

    public function getEstatusColorAttribute(): string
    {
        return self::ESTATUS[$this->estatus][1] ?? 'secondary';
    }

    public static function minutosAHoras(int $min): string
    {
        return sprintf('%d:%02d', intdiv($min, 60), $min % 60);
    }
}
