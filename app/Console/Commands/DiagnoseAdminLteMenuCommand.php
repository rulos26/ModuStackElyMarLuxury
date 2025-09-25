<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JeroenNoten\LaravelAdminLte\AdminLte;

class DiagnoseAdminLteMenuCommand extends Command
{
    protected $signature = 'adminlte:diagnose-menu-config';
    protected $description = 'Diagnose AdminLTE menu configuration and routing';

    public function handle()
    {
        $this->info('🔍 Diagnosticando configuración del menú AdminLTE...');

        try {
            $adminlte = app(AdminLte::class);

            // Verificar configuración del menú
            $this->info('📋 Configuración del menú:');
            $menuConfig = config('adminlte.menu');

            if ($menuConfig) {
                $this->info('   ✅ Configuración del menú cargada');
                $this->info('   📊 Número de elementos: ' . count($menuConfig));

                // Buscar elemento de configuración
                $configItem = null;
                foreach ($menuConfig as $item) {
                    if (isset($item['text']) && $item['text'] === 'Configuración') {
                        $configItem = $item;
                        break;
                    }
                }

                if ($configItem) {
                    $this->info('   📋 Elemento de configuración encontrado:');
                    $this->info('      Texto: ' . ($configItem['text'] ?? 'N/A'));
                    $this->info('      URL: ' . ($configItem['url'] ?? 'N/A'));
                    $this->info('      Icono: ' . ($configItem['icon'] ?? 'N/A'));
                    $this->info('      Permiso: ' . ($configItem['can'] ?? 'N/A'));
                } else {
                    $this->warn('   ⚠️ Elemento de configuración NO encontrado');
                }
            } else {
                $this->error('   ❌ Configuración del menú NO cargada');
            }

            // Verificar menú procesado
            $this->info('📋 Menú procesado:');
            $sidebarMenu = $adminlte->menu('sidebar');

            if ($sidebarMenu) {
                $this->info('   ✅ Menú sidebar procesado');
                $this->info('   📊 Número de elementos: ' . count($sidebarMenu));

                // Buscar elemento de configuración en el menú procesado
                $processedConfigItem = null;
                foreach ($sidebarMenu as $item) {
                    if (isset($item['text']) && $item['text'] === 'Configuración') {
                        $processedConfigItem = $item;
                        break;
                    }
                }

                if ($processedConfigItem) {
                    $this->info('   📋 Elemento de configuración procesado:');
                    $this->info('      Texto: ' . ($processedConfigItem['text'] ?? 'N/A'));
                    $this->info('      URL: ' . ($processedConfigItem['url'] ?? 'N/A'));
                    $this->info('      Href: ' . ($processedConfigItem['href'] ?? 'N/A'));
                    $this->info('      Clase: ' . ($processedConfigItem['class'] ?? 'N/A'));
                } else {
                    $this->warn('   ⚠️ Elemento de configuración NO encontrado en menú procesado');
                }
            } else {
                $this->error('   ❌ Menú sidebar NO procesado');
            }

            // Verificar rutas
            $this->info('📋 Verificando rutas:');
            $routes = [
                'admin.settings.dashboard',
                'admin.settings.section',
                'admin.settings.update.section'
            ];

            foreach ($routes as $routeName) {
                if (\Route::has($routeName)) {
                    $this->info("   ✅ Ruta '{$routeName}' existe");

                    try {
                        $url = route($routeName, ['section' => 'appearance']);
                        $this->info("      URL: {$url}");
                    } catch (\Exception $e) {
                        try {
                            $url = route($routeName);
                            $this->info("      URL: {$url}");
                        } catch (\Exception $e2) {
                            $this->warn("      ⚠️ Error generando URL: " . $e2->getMessage());
                        }
                    }
                } else {
                    $this->error("   ❌ Ruta '{$routeName}' NO existe");
                }
            }

            $this->info('🎯 Diagnóstico completado');

        } catch (\Exception $e) {
            $this->error('❌ Error en diagnóstico: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }
}
