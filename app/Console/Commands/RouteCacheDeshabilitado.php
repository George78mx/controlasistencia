<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Reemplaza a "route:cache" de Laravel.
 *
 * La plataforma se publica en una subcarpeta (/controlasistencia). Con las rutas en caché,
 * Laravel recorta la "/" final de la URL y la página principal falla con
 * "The GET method is not supported for route /. Supported methods: HEAD".
 * Por eso aquí nunca se guardan las rutas en caché; "php artisan optimize" sigue
 * guardando configuración, eventos y vistas.
 */
class RouteCacheDeshabilitado extends Command
{
    protected $signature = 'route:cache';

    protected $description = 'Deshabilitado en este proyecto: borra la caché de rutas en lugar de crearla';

    public function handle(): int
    {
        $this->callSilent('route:clear');
        $this->components->info('Caché de rutas deshabilitada en este proyecto (se publica en una subcarpeta).');

        return self::SUCCESS;
    }
}
