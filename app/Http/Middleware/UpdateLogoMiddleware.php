<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLogoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Actualizar configuración de logo antes de renderizar la vista
        $this->updateLogoConfig();

        return $next($request);
    }

    /**
     * Actualizar configuración del logo
     */
    private function updateLogoConfig(): void
    {
        try {
            $logoPath = \App\Helpers\ViewHelper::getLogoForView();
            $appName = \App\Helpers\ViewHelper::getAppNameForView();

            // Actualizar configuración de AdminLTE
            config([
                'adminlte.logo_img' => $logoPath,
                'adminlte.logo_img_alt' => $appName,
                'adminlte.auth_logo.img.path' => $logoPath,
                // Actualizar preloader
                'adminlte.preloader.img.path' => $logoPath,
                'adminlte.preloader.img.alt' => $appName . ' Preloader',
            ]);

        } catch (\Exception $e) {
            // Silenciar errores para no afectar la aplicación
        }
    }
}
