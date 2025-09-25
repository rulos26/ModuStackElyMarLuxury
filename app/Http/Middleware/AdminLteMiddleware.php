<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JeroenNoten\LaravelAdminLte\AdminLte;

class AdminLteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Crear instancia de AdminLte si no existe
        if (!app()->bound(AdminLte::class)) {
            app()->singleton(AdminLte::class, function () {
                return new AdminLte([]);
            });
        }

        // Compartir la variable con todas las vistas
        view()->share('adminlte', app(AdminLte::class));

        return $next($request);
    }
}
