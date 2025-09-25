<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\AppConfigHelper;

class LogoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar el logo dinámicamente después de que Laravel esté inicializado
        $this->app->booted(function () {
            $this->configureAdminLteLogo();
        });
    }

    /**
     * Configurar el logo de AdminLTE dinámicamente
     */
    private function configureAdminLteLogo(): void
    {
        try {
            $logoPath = \App\Helpers\ViewHelper::getLogoForView();
            $appName = \App\Helpers\ViewHelper::getAppNameForView();

            // Actualizar la configuración de AdminLTE
            config([
                'adminlte.logo_img' => $logoPath,
                'adminlte.logo_img_alt' => $appName,
                'adminlte.auth_logo.img.path' => $logoPath,
                'adminlte.preloader.img.path' => $logoPath,
                'adminlte.preloader.img.alt' => $appName . ' Preloader',
            ]);

        } catch (\Exception $e) {
            // Si hay error, mantener configuración por defecto
            \Log::warning('Error al configurar logo dinámico: ' . $e->getMessage());
        }
    }
}
