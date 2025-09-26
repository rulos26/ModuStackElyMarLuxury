<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JeroenNoten\LaravelAdminLte\AdminLte;
use JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter;
use JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter;

class TestMenuProcessingCommand extends Command
{
    protected $signature = 'adminlte:test-menu-processing';
    protected $description = 'Test AdminLTE menu processing step by step';

    public function handle()
    {
        $this->info('🧪 Probando procesamiento del menú AdminLTE...');

        try {
            $adminlte = app(AdminLte::class);

            // Obtener configuración original
            $menuConfig = config('adminlte.menu');
            $this->info('📋 Configuración original:');
            $this->info('   Elementos: ' . count($menuConfig));

            // Buscar elemento de configuración
            $configItem = null;
            foreach ($menuConfig as $index => $item) {
                if (isset($item['text']) && $item['text'] === 'Configuración') {
                    $configItem = $item;
                    $this->info("   📍 Elemento encontrado en índice: {$index}");
                    break;
                }
            }

            if ($configItem) {
                $this->info('   📋 Elemento de configuración:');
                $this->info('      Texto: ' . ($configItem['text'] ?? 'N/A'));
                $this->info('      URL: ' . ($configItem['url'] ?? 'N/A'));
                $this->info('      Icono: ' . ($configItem['icon'] ?? 'N/A'));
                $this->info('      Permiso: ' . ($configItem['can'] ?? 'N/A'));

                // Probar filtros individualmente
                $this->info('📋 Probando filtros:');

                // HrefFilter
                $hrefFilter = new HrefFilter();
                $processedItem = $hrefFilter->transform($configItem, $adminlte);
                $this->info('   HrefFilter:');
                $this->info('      Href: ' . ($processedItem['href'] ?? 'N/A'));
                $this->info('      URL: ' . ($processedItem['url'] ?? 'N/A'));

                // ClassesFilter
                $classesFilter = new ClassesFilter();
                $processedItem = $classesFilter->transform($processedItem, $adminlte);
                $this->info('   ClassesFilter:');
                $this->info('      Clase: ' . ($processedItem['class'] ?? 'N/A'));

                // Verificar si el usuario tiene permisos
                $this->info('📋 Verificando permisos:');
                if (isset($configItem['can'])) {
                    $permission = $configItem['can'];
                    $this->info("   Permiso requerido: {$permission}");

                    // Verificar si el usuario actual tiene el permiso
                    $user = auth()->user();
                    if ($user) {
                        $hasPermission = $user->can($permission);
                        $this->info("   Usuario actual: {$user->name}");
                        $this->info("   Tiene permiso: " . ($hasPermission ? 'Sí' : 'No'));

                        if (!$hasPermission) {
                            $this->warn('   ⚠️ El usuario NO tiene el permiso requerido');
                        }
                    } else {
                        $this->warn('   ⚠️ No hay usuario autenticado');
                    }
                } else {
                    $this->info('   ✅ No se requiere permiso específico');
                }

            } else {
                $this->error('   ❌ Elemento de configuración NO encontrado');
            }

            // Verificar menú final
            $this->info('📋 Menú final procesado:');
            $sidebarMenu = $adminlte->menu('sidebar');

            if ($sidebarMenu) {
                $this->info('   ✅ Menú sidebar procesado');
                $this->info('   📊 Número de elementos: ' . count($sidebarMenu));

                // Mostrar todos los elementos del menú
                foreach ($sidebarMenu as $index => $item) {
                    $text = $item['text'] ?? 'Sin texto';
                    $url = $item['url'] ?? 'N/A';
                    $href = $item['href'] ?? 'N/A';
                    $class = $item['class'] ?? 'N/A';

                    $this->info("   [{$index}] {$text}");
                    $this->info("       URL: {$url}");
                    $this->info("       Href: {$href}");
                    $this->info("       Clase: {$class}");
                }
            } else {
                $this->error('   ❌ Menú sidebar NO procesado');
            }

            $this->info('🎯 Prueba de procesamiento completada');

        } catch (\Exception $e) {
            $this->error('❌ Error en prueba de procesamiento: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }
}



