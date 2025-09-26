<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class TestSettingsRoutesCommand extends Command
{
    protected $signature = 'admin:test-settings-routes';
    protected $description = 'Test admin settings routes for proper URL generation';

    public function handle()
    {
        $this->info('🔍 Probando rutas de configuración de administración...');

        try {
            // Verificar que las rutas existen
            $routes = [
                'admin.settings.dashboard',
                'admin.settings.section',
                'admin.settings.update.section'
            ];

            foreach ($routes as $routeName) {
                if (Route::has($routeName)) {
                    $this->info("✅ Ruta '{$routeName}' existe");

                    // Probar generación de URL
                    if ($routeName === 'admin.settings.section') {
                        $url = route($routeName, 'appearance');
                        $this->info("   URL: {$url}");

                        if (str_contains($url, '#')) {
                            $this->warn("   ⚠️ URL contiene '#' - posible problema");
                        } else {
                            $this->info("   ✅ URL generada correctamente");
                        }
                    } elseif ($routeName === 'admin.settings.update.section') {
                        $url = route($routeName, 'appearance');
                        $this->info("   URL: {$url}");

                        if (str_contains($url, '#')) {
                            $this->warn("   ⚠️ URL contiene '#' - posible problema");
                        } else {
                            $this->info("   ✅ URL generada correctamente");
                        }
                    } else {
                        $url = route($routeName);
                        $this->info("   URL: {$url}");

                        if (str_contains($url, '#')) {
                            $this->warn("   ⚠️ URL contiene '#' - posible problema");
                        } else {
                            $this->info("   ✅ URL generada correctamente");
                        }
                    }
                } else {
                    $this->error("❌ Ruta '{$routeName}' NO existe");
                }
            }

            $this->info('🎯 Prueba de rutas completada');

        } catch (\Exception $e) {
            $this->error('❌ Error en prueba de rutas: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}



