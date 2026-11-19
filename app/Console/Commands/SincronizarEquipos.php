<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\SincronizadorChecadas;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SincronizarEquipos extends Command
{
    protected $signature = 'hikvision:sincronizar
        {--equipo= : ID del equipo (por defecto todos los activos)}
        {--forzar : Descargar aunque no se haya cumplido el intervalo}
        {--desde= : Fecha/hora inicial, ej. "2026-11-01 00:00"}
        {--hasta= : Fecha/hora final}';

    protected $description = 'Descarga (solo lectura) las checadas de los equipos Hikvision según el intervalo configurado';

    public function handle(SincronizadorChecadas $sync): int
    {
        $equipos = Device::query()
            ->when($this->option('equipo'), fn ($q, $id) => $q->whereKey($id))
            ->when(!$this->option('equipo'), fn ($q) => $q->where('activo', true))
            ->get();

        $desde = $this->option('desde') ? Carbon::parse($this->option('desde')) : null;
        $hasta = $this->option('hasta') ? Carbon::parse($this->option('hasta')) : null;
        $forzar = $this->option('forzar') || $desde;

        foreach ($equipos as $equipo) {
            if (!$forzar && !$equipo->tocaSincronizar()) {
                $this->line("· {$equipo->nombre}: próxima consulta {$equipo->proximaSincronizacion()?->format('H:i')}");
                continue;
            }
            $log = $sync->sincronizar($equipo, $forzar ? 'manual' : 'programada', $desde, $hasta);
            $this->line(sprintf('%s %s: %s (%d recibidos, %d nuevos)',
                $log->estado === 'ok' ? '✔' : '✖', $equipo->nombre, $log->mensaje, $log->eventos_recibidos, $log->eventos_nuevos));
        }
        return self::SUCCESS;
    }
}
