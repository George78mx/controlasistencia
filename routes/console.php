<?php

use Illuminate\Support\Facades\Schedule;

// Se revisa cada minuto; cada equipo solo se consulta cuando se cumple su intervalo
// (configurable en Configuración o por equipo).
Schedule::command('hikvision:sincronizar')->everyMinute()->withoutOverlapping(15)->runInBackground();

// Actualiza faltas del día (pendientes que pasan a falta) y cierra el día anterior.
Schedule::command('asistencia:calcular')->everyFifteenMinutes()->withoutOverlapping(30);

// Recalcula la última semana por si se registraron incidencias o checadas tardías.
Schedule::command('asistencia:calcular --desde='.now()->subDays(7)->toDateString())->dailyAt('02:30');
