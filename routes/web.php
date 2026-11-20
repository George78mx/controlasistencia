<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ChecadaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\HikvisionPushController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\ImportacionEmpleadosController;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Recepción de eventos en tiempo real (HTTP Listening del equipo)
Route::post('hikvision/eventos/{token}', [HikvisionPushController::class, 'recibir'])->name('hikvision.push');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'form'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('perfil', [PerfilController::class, 'edit'])->name('perfil');
    Route::put('perfil', [PerfilController::class, 'update'])->name('perfil.update');

    // Equipos
    Route::middleware('permission:equipos.ver')->group(function () {
        Route::get('equipos', [DeviceController::class, 'index'])->name('equipos.index');
        Route::get('equipos/{equipo}', [DeviceController::class, 'show'])->name('equipos.show')->whereNumber('equipo');
    });
    Route::middleware('permission:equipos.gestionar')->group(function () {
        Route::get('equipos/crear', [DeviceController::class, 'create'])->name('equipos.create');
        Route::post('equipos', [DeviceController::class, 'store'])->name('equipos.store');
        Route::get('equipos/{equipo}/editar', [DeviceController::class, 'edit'])->name('equipos.edit');
        Route::put('equipos/{equipo}', [DeviceController::class, 'update'])->name('equipos.update');
        Route::delete('equipos/{equipo}', [DeviceController::class, 'destroy'])->name('equipos.destroy');
        Route::post('equipos/{equipo}/token', [DeviceController::class, 'regenerarToken'])->name('equipos.token');
    });
    Route::middleware('permission:equipos.sincronizar')->group(function () {
        Route::post('equipos/{equipo}/probar', [DeviceController::class, 'probar'])->name('equipos.probar');
        Route::post('equipos/{equipo}/sincronizar', [DeviceController::class, 'sincronizar'])->name('equipos.sincronizar');
        Route::post('equipos/{equipo}/usuarios', [DeviceController::class, 'usuarios'])->name('equipos.usuarios');
        Route::post('equipos/{equipo}/importar', [DeviceController::class, 'importar'])->name('equipos.importar');
    });

    // Empleados
    Route::middleware('permission:empleados.gestionar')->group(function () {
        Route::get('empleados/importar', [ImportacionEmpleadosController::class, 'form'])->name('empleados.importar');
        Route::post('empleados/importar', [ImportacionEmpleadosController::class, 'importar'])->name('empleados.importar.procesar');
        Route::get('empleados/plantilla', [ImportacionEmpleadosController::class, 'plantilla'])->name('empleados.plantilla');
        Route::get('empleados/crear', [EmpleadoController::class, 'create'])->name('empleados.create');
        Route::post('empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::get('empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
        Route::put('empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
    });
    Route::delete('empleados/{empleado}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy')->middleware('permission:empleados.eliminar');
    Route::middleware('permission:empleados.ver')->group(function () {
        Route::get('empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
        Route::get('empleados/{empleado}', [EmpleadoController::class, 'show'])->name('empleados.show');
    });

    Route::get('checadas', [ChecadaController::class, 'index'])->name('checadas.index')->middleware('permission:checadas.ver');

    Route::get('asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index')->middleware('permission:asistencia.ver');
    Route::post('asistencia/recalcular', [AsistenciaController::class, 'recalcular'])->name('asistencia.recalcular')->middleware('permission:asistencia.recalcular');

    // Incidencias
    Route::middleware('permission:incidencias.gestionar')->group(function () {
        Route::get('incidencias/crear', [IncidenciaController::class, 'create'])->name('incidencias.create');
        Route::post('incidencias', [IncidenciaController::class, 'store'])->name('incidencias.store');
        Route::get('incidencias/{incidencia}/editar', [IncidenciaController::class, 'edit'])->name('incidencias.edit');
        Route::put('incidencias/{incidencia}', [IncidenciaController::class, 'update'])->name('incidencias.update');
        Route::delete('incidencias/{incidencia}', [IncidenciaController::class, 'destroy'])->name('incidencias.destroy');
    });
    Route::middleware('permission:incidencias.ver')->group(function () {
        Route::get('incidencias', [IncidenciaController::class, 'index'])->name('incidencias.index');
        Route::get('incidencias/{incidencia}/documento', [IncidenciaController::class, 'documento'])->name('incidencias.documento');
    });

    // Reportes
    Route::middleware('permission:reportes.ver')->group(function () {
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/{tipo}', [ReporteController::class, 'ver'])->name('reportes.ver')
            ->whereIn('tipo', array_keys(ReporteController::TIPOS));
    });

    // Catálogos
    Route::middleware('permission:catalogos.gestionar')->group(function () {
        Route::resource('horarios', HorarioController::class)->except('show');
        Route::get('catalogos/{catalogo}', [CatalogoController::class, 'index'])->name('catalogos.index');
        Route::post('catalogos/{catalogo}', [CatalogoController::class, 'store'])->name('catalogos.store');
        Route::put('catalogos/{catalogo}/{id}', [CatalogoController::class, 'update'])->name('catalogos.update');
        Route::delete('catalogos/{catalogo}/{id}', [CatalogoController::class, 'destroy'])->name('catalogos.destroy');
    });

    Route::resource('usuarios', UserController::class)->except('show')->middleware('permission:usuarios.gestionar')
        ->parameters(['usuarios' => 'usuario']);
    Route::resource('roles', RoleController::class)->except('show')->middleware('permission:roles.gestionar')
        ->parameters(['roles' => 'role']);

    Route::get('configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit')->middleware('permission:configuracion.gestionar');
    Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update')->middleware('permission:configuracion.gestionar');
});
