<?php

namespace App\Support;

use App\Models\Sucursal;

/**
 * Sucursal elegida en el selector de la barra superior. Se guarda en la sesión del usuario
 * y filtra tablero, asistencia, checadas, empleados, incidencias y reportes.
 * null = todas las sucursales.
 */
class SucursalActiva
{
    public const CLAVE = 'sucursal_activa';

    private static ?Sucursal $cache = null;
    private static bool $leida = false;

    public static function id(): ?int
    {
        return self::modelo()?->id;
    }

    public static function modelo(): ?Sucursal
    {
        if (!self::$leida) {
            self::$leida = true;
            $id = session(self::CLAVE);
            self::$cache = $id ? Sucursal::find($id) : null;
            if ($id && !self::$cache) session()->forget(self::CLAVE);
        }
        return self::$cache;
    }

    public static function cambiar(?int $id): void
    {
        $id ? session([self::CLAVE => $id]) : session()->forget(self::CLAVE);
        self::$leida = false;
    }
}
