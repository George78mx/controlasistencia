<?php

namespace App\Console\Commands;

use App\Models\Empleado;
use App\Services\CalculadoraAsistencia;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalcularAsistencia extends Command
{
    protected $signature = 'asistencia:calcular
        {--desde= : Fecha inicial (por defecto ayer)}
        {--hasta= : Fecha final (por defecto hoy)}
        {--empleado= : Número de empleado}';

    protected $description = 'Calcula asistencias, retardos y faltas a partir de las checadas descargadas';

    public function handle(CalculadoraAsistencia $calc): int
    {
        $desde = Carbon::parse($this->option('desde') ?: now()->subDay()->toDateString());
        $hasta = Carbon::parse($this->option('hasta') ?: now()->toDateString());
        $empleados = $this->option('empleado')
            ? Empleado::with('horario.dias')->where('numero_empleado', $this->option('empleado'))->get()
            : null;

        $n = $calc->calcularRango($desde, $hasta, $empleados);
        $this->info("Se calcularon $n registros del {$desde->toDateString()} al {$hasta->toDateString()}.");
        return self::SUCCESS;
    }
}
