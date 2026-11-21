<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use App\Models\Sucursal;
use App\Support\SucursalActiva;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_MX.UTF-8', 'es_ES.UTF-8', 'Spanish_Mexico', 'es');
        Paginator::useBootstrapFive();

        Route::model('equipo', Device::class);
        Route::model('usuario', User::class);
        Route::model('role', Role::class);

        // El rol Administrador siempre tiene todos los permisos
        Gate::before(fn ($user) => $user->hasRole('Administrador') ? true : null);

        // Selector de sucursal en la barra superior
        View::composer('layouts.app', function ($view) {
            $view->with('sucursalesMenu', Sucursal::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']));
            $view->with('sucursalActiva', SucursalActiva::modelo());
        });
    }
}
