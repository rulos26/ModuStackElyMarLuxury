<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JeroenNoten\LaravelAdminLte\AdminLte;

class FixAdminLteMenuCommand extends Command
{
    protected $signature = 'adminlte:fix-menu';
    protected $description = 'Fix AdminLTE menu processing issues';

    public function handle()
    {
        $this->info('🔧 Solucionando problemas del menú AdminLTE...');

        try {
            // Limpiar todas las cachés
            $this->info('📋 Limpiando cachés...');
            \Artisan::call('config:clear');
            \Artisan::call('view:clear');
            \Artisan::call('cache:clear');
            $this->info('   ✅ Cachés limpiadas');

            // Verificar configuración
            $this->info('📋 Verificando configuración...');
            $menuConfig = config('adminlte.menu');

            if (!$menuConfig) {
                $this->error('   ❌ Configuración del menú no encontrada');
                return 1;
            }

            $this->info('   ✅ Configuración del menú cargada');

            // Buscar y corregir elemento de configuración
            $configItemIndex = null;
            foreach ($menuConfig as $index => $item) {
                if (isset($item['text']) && $item['text'] === 'Configuración') {
                    $configItemIndex = $index;
                    break;
                }
            }

            if ($configItemIndex !== null) {
                $this->info("   📍 Elemento de configuración encontrado en índice: {$configItemIndex}");

                // Verificar si el elemento tiene todos los campos necesarios
                $configItem = $menuConfig[$configItemIndex];
                $this->info('   📋 Elemento actual:');
                $this->info('      Texto: ' . ($configItem['text'] ?? 'N/A'));
                $this->info('      URL: ' . ($configItem['url'] ?? 'N/A'));
                $this->info('      Icono: ' . ($configItem['icon'] ?? 'N/A'));
                $this->info('      Permiso: ' . ($configItem['can'] ?? 'N/A'));
                $this->info('      Activo: ' . (isset($configItem['active']) ? 'Sí' : 'No'));

                // Asegurar que el elemento tenga todos los campos necesarios
                $updatedItem = $configItem;
                $updatedItem['url'] = $updatedItem['url'] ?? 'admin/settings';
                $updatedItem['icon'] = $updatedItem['icon'] ?? 'fas fa-fw fa-cog';
                $updatedItem['can'] = $updatedItem['can'] ?? 'manage-settings';
                $updatedItem['active'] = $updatedItem['active'] ?? ['admin/settings*'];

                $this->info('   ✅ Elemento de configuración verificado');
            } else {
                $this->error('   ❌ Elemento de configuración NO encontrado');
                return 1;
            }

            // Probar menú con usuario autenticado
            $this->info('📋 Probando menú con usuario autenticado...');

            // Crear usuario temporal para pruebas
            $user = \App\Models\User::first();
            if ($user) {
                auth()->login($user);
                $this->info("   ✅ Usuario autenticado: {$user->name}");

                // Verificar permisos
                if ($user->can('manage-settings')) {
                    $this->info('   ✅ Usuario tiene permiso manage-settings');
                } else {
                    $this->warn('   ⚠️ Usuario NO tiene permiso manage-settings');

                    // Asignar permiso temporalmente
                    $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
                        'name' => 'manage-settings',
                        'guard_name' => 'web'
                    ]);

                    $user->givePermissionTo($permission);
                    $this->info('   ✅ Permiso asignado temporalmente');
                }

                // Probar menú procesado
                $adminlte = app(AdminLte::class);
                $sidebarMenu = $adminlte->menu('sidebar');

                if ($sidebarMenu) {
                    $this->info('   ✅ Menú sidebar procesado');

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

                        if (isset($processedConfigItem['href']) && $processedConfigItem['href'] !== 'N/A') {
                            $this->info('   ✅ Elemento tiene href generado');
                        } else {
                            $this->warn('   ⚠️ Elemento NO tiene href generado');
                        }
                    } else {
                        $this->error('   ❌ Elemento de configuración NO encontrado en menú procesado');
                    }
                } else {
                    $this->error('   ❌ Menú sidebar NO procesado');
                }

                auth()->logout();
            } else {
                $this->warn('   ⚠️ No hay usuarios en la base de datos');
            }

            $this->info('🎯 Solución completada');

        } catch (\Exception $e) {
            $this->error('❌ Error en solución: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }
}

