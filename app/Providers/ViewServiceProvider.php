<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Helpers\AppConfigHelper;

class ViewServiceProvider extends ServiceProvider
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
        // Compartir configuración de la aplicación con todas las vistas
        View::composer('*', function ($view) {
            $view->with([
                'appConfig' => [
                    'name' => AppConfigHelper::getAppName(),
                    'logo' => AppConfigHelper::getAppLogo(),
                    'icon' => AppConfigHelper::getAppIcon(),
                    'title_prefix' => AppConfigHelper::getTitlePrefix(),
                    'title_postfix' => AppConfigHelper::getTitlePostfix(),
                ]
            ]);
        });
    }
}
