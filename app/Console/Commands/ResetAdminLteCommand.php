<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ResetAdminLteCommand extends Command
{
    protected $signature = 'adminlte:reset';
    protected $description = 'Reset AdminLTE configuration and clear all caches';

    public function handle()
    {
        $this->info('🔄 Reiniciando configuración de AdminLTE...');

        try {
            // Limpiar todas las cachés
            $this->info('📋 Limpiando cachés...');
            \Artisan::call('config:clear');
            \Artisan::call('view:clear');
            \Artisan::call('cache:clear');
            \Artisan::call('route:clear');
            $this->info('   ✅ Cachés limpiadas');

            // Verificar archivos de configuración
            $this->info('📋 Verificando archivos de configuración...');

            $configFile = 'config/adminlte.php';
            if (File::exists($configFile)) {
                $this->info('   ✅ Archivo de configuración existe');

                // Verificar contenido del archivo
                $content = File::get($configFile);
                if (str_contains($content, 'Configuración')) {
                    $this->info('   ✅ Elemento de configuración encontrado en archivo');
                } else {
                    $this->warn('   ⚠️ Elemento de configuración NO encontrado en archivo');
                }
            } else {
                $this->error('   ❌ Archivo de configuración NO existe');
                return 1;
            }

            // Verificar vistas de AdminLTE
            $this->info('📋 Verificando vistas de AdminLTE...');
            $adminLteViews = [
                'resources/views/vendor/adminlte/partials/sidebar/menu-item-link.blade.php',
                'resources/views/vendor/adminlte/partials/sidebar/menu-item-treeview-menu.blade.php',
            ];

            foreach ($adminLteViews as $view) {
                if (File::exists($view)) {
                    $this->info("   ✅ {$view} existe");
                } else {
                    $this->warn("   ⚠️ {$view} NO existe");
                }
            }

            // Verificar middleware
            $this->info('📋 Verificando middleware...');
            $middleware = app('router')->getMiddleware();

            $requiredMiddleware = ['adminlte', 'adminlte.menu'];
            foreach ($requiredMiddleware as $mw) {
                if (isset($middleware[$mw])) {
                    $this->info("   ✅ Middleware {$mw} registrado");
                } else {
                    $this->warn("   ⚠️ Middleware {$mw} NO registrado");
                }
            }

            // Verificar service providers
            $this->info('📋 Verificando service providers...');
            $providers = config('app.providers');

            $requiredProviders = [
                'JeroenNoten\\LaravelAdminLte\\AdminLteServiceProvider',
                'App\\Providers\\AdminLteServiceProvider'
            ];

            foreach ($requiredProviders as $provider) {
                if (in_array($provider, $providers)) {
                    $this->info("   ✅ Provider {$provider} registrado");
                } else {
                    $this->warn("   ⚠️ Provider {$provider} NO registrado");
                }
            }

            // Probar configuración básica
            $this->info('📋 Probando configuración básica...');

            try {
                $adminlte = app(\JeroenNoten\LaravelAdminLte\AdminLte::class);
                $this->info('   ✅ Instancia de AdminLTE creada');

                $menu = $adminlte->menu('sidebar');
                if ($menu) {
                    $this->info('   ✅ Menú sidebar generado');
                    $this->info('   📊 Número de elementos: ' . count($menu));
                } else {
                    $this->error('   ❌ Menú sidebar NO generado');
                }

            } catch (\Exception $e) {
                $this->error('   ❌ Error creando instancia de AdminLTE: ' . $e->getMessage());
            }

            $this->info('🎯 Reinicio completado');
            $this->info('💡 Si el problema persiste, puede ser necesario reinstalar AdminLTE');

        } catch (\Exception $e) {
            $this->error('❌ Error en reinicio: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}



