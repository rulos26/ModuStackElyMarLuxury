<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class DiagnoseUrlHashCommand extends Command
{
    protected $signature = 'admin:diagnose-url-hash';
    protected $description = 'Diagnose URL hash issues in admin settings';

    public function handle()
    {
        $this->info('🔍 Diagnosticando problemas de URL con hash...');

        try {
            // Verificar configuración de URL
            $this->info('📋 Configuración de URL:');
            $this->info('   APP_URL: ' . config('app.url'));
            $this->info('   URL::to(): ' . URL::to('/'));

            // Probar generación de URLs específicas
            $this->info('📋 URLs de configuración:');

            $urls = [
                'admin.settings.dashboard' => route('admin.settings.dashboard'),
                'admin.settings.section' => route('admin.settings.section', 'appearance'),
                'admin.settings.update.section' => route('admin.settings.update.section', 'appearance'),
            ];

            foreach ($urls as $name => $url) {
                $this->info("   {$name}: {$url}");

                if (str_contains($url, '#')) {
                    $this->warn("   ⚠️ Contiene '#' - posible problema");
                } else {
                    $this->info("   ✅ URL limpia");
                }

                // Verificar si la URL es válida
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->info("   ✅ URL válida");
                } else {
                    $this->error("   ❌ URL inválida");
                }
            }

            // Verificar si hay problemas con el middleware
            $this->info('📋 Verificando middleware:');
            $middleware = app('router')->getMiddleware();
            if (isset($middleware['ip.access'])) {
                $this->info('   ✅ Middleware ip.access registrado');
            } else {
                $this->warn('   ⚠️ Middleware ip.access no encontrado');
            }

            if (isset($middleware['auth'])) {
                $this->info('   ✅ Middleware auth registrado');
            } else {
                $this->warn('   ⚠️ Middleware auth no encontrado');
            }

            $this->info('🎯 Diagnóstico completado');

        } catch (\Exception $e) {
            $this->error('❌ Error en diagnóstico: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}



