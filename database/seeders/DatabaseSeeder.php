<?php

namespace Database\Seeders;

use App\Models\DiaFestivo;
use App\Models\Departamento;
use App\Models\Horario;
use App\Models\Puesto;
use App\Models\Setting;
use App\Models\TipoIncidencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public const PERMISOS = [
        'dashboard.ver'          => 'Ver tablero',
        'empleados.ver'          => 'Ver empleados',
        'empleados.gestionar'    => 'Crear y editar empleados',
        'empleados.eliminar'     => 'Eliminar empleados',
        'equipos.ver'            => 'Ver equipos',
        'equipos.gestionar'      => 'Crear y editar equipos',
        'equipos.sincronizar'    => 'Descargar checadas manualmente',
        'checadas.ver'           => 'Ver checadas',
        'asistencia.ver'         => 'Ver asistencia',
        'asistencia.recalcular'  => 'Recalcular asistencia',
        'incidencias.ver'        => 'Ver incidencias',
        'incidencias.gestionar'  => 'Registrar incidencias',
        'reportes.ver'           => 'Ver reportes',
        'reportes.exportar'      => 'Exportar reportes a PDF y Excel',
        'catalogos.gestionar'    => 'Administrar catálogos (departamentos, puestos, horarios, festivos)',
        'usuarios.gestionar'     => 'Administrar usuarios',
        'roles.gestionar'        => 'Administrar roles y permisos',
        'configuracion.gestionar'=> 'Cambiar la configuración',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (array_keys(self::PERMISOS) as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $todos = array_keys(self::PERMISOS);
        $roles = [
            'Administrador'     => $todos,
            'Recursos Humanos'  => array_values(array_diff($todos, ['usuarios.gestionar', 'roles.gestionar', 'configuracion.gestionar', 'equipos.gestionar'])),
            'Supervisor'        => ['dashboard.ver', 'empleados.ver', 'equipos.ver', 'checadas.ver', 'asistencia.ver', 'incidencias.ver', 'incidencias.gestionar', 'reportes.ver', 'reportes.exportar'],
            'Consulta'          => ['dashboard.ver', 'empleados.ver', 'checadas.ver', 'asistencia.ver', 'incidencias.ver', 'reportes.ver'],
        ];
        foreach ($roles as $nombre => $permisos) {
            Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web'])->syncPermissions($permisos);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@empresa.com'],
            ['name' => 'Administrador', 'password' => 'Admin123*', 'activo' => true]
        );
        $admin->assignRole('Administrador');

        foreach (Setting::DEFAULTS as $clave => [$valor, $desc]) {
            Setting::firstOrCreate(['clave' => $clave], ['valor' => $valor, 'descripcion' => $desc]);
        }

        foreach ([
            ['VAC', 'Vacaciones', '#0ea5e9', true],
            ['INC', 'Incapacidad IMSS', '#ef4444', true],
            ['PCG', 'Permiso con goce', '#22c55e', true],
            ['PSG', 'Permiso sin goce', '#f59e0b', false],
            ['COM', 'Comisión / trabajo externo', '#8b5cf6', true],
            ['CAP', 'Capacitación', '#14b8a6', true],
            ['JUS', 'Falta justificada', '#64748b', true],
            ['OLV', 'Olvido de checada', '#a855f7', true],
        ] as [$clave, $nombre, $color, $goce]) {
            TipoIncidencia::firstOrCreate(['clave' => $clave], ['nombre' => $nombre, 'color' => $color, 'con_goce' => $goce]);
        }

        if (!Horario::exists()) {
            $h = Horario::create(['nombre' => 'Oficina L-V 9:00 a 18:00', 'tolerancia_minutos' => 10, 'minutos_para_falta' => 30]);
            foreach (range(1, 7) as $d) {
                $h->dias()->create(['dia_semana' => $d, 'laborable' => $d <= 5, 'entrada' => $d <= 5 ? '09:00' : null, 'salida' => $d <= 5 ? '18:00' : null]);
            }
            $h2 = Horario::create(['nombre' => 'Operativo L-S 8:00 a 16:00', 'tolerancia_minutos' => 10, 'minutos_para_falta' => 30]);
            foreach (range(1, 7) as $d) {
                $h2->dias()->create(['dia_semana' => $d, 'laborable' => $d <= 6, 'entrada' => $d <= 6 ? '08:00' : null, 'salida' => $d <= 6 ? '16:00' : null]);
            }
        }

        foreach (['Administración', 'Recursos Humanos', 'Operaciones', 'Ventas', 'Sistemas'] as $d) {
            Departamento::firstOrCreate(['nombre' => $d]);
        }
        foreach (['Gerente', 'Coordinador', 'Auxiliar administrativo', 'Operador', 'Vendedor'] as $p) {
            Puesto::firstOrCreate(['nombre' => $p]);
        }

        // Días de descanso obligatorio (Art. 74 LFT)
        foreach ([now()->year, now()->year + 1] as $y) {
            $dias = [
                Carbon::create($y, 1, 1)->toDateString()  => 'Año Nuevo',
                Carbon::parse("first monday of february $y")->toDateString() => 'Día de la Constitución',
                Carbon::parse("third monday of march $y")->toDateString()    => 'Natalicio de Benito Juárez',
                Carbon::create($y, 5, 1)->toDateString()  => 'Día del Trabajo',
                Carbon::create($y, 9, 16)->toDateString() => 'Día de la Independencia',
                Carbon::parse("third monday of november $y")->toDateString() => 'Día de la Revolución',
                Carbon::create($y, 12, 25)->toDateString() => 'Navidad',
            ];
            foreach ($dias as $fecha => $desc) {
                DiaFestivo::firstOrCreate(['fecha' => $fecha], ['descripcion' => $desc]);
            }
        }
    }
}
