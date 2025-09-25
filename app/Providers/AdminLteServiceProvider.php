<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\AdminLte;

class AdminLteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // No registrar AdminLte aquí, dejar que el service provider oficial lo haga
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Compartir la variable $adminlte con todas las vistas
        view()->composer('*', function ($view) {
            if (app()->bound(AdminLte::class)) {
                $view->with('adminlte', app(AdminLte::class));
            }
        });
    }
}
