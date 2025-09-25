<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JeroenNoten\LaravelAdminLte\AdminLte;

class TestAdminLteCommand extends Command
{
    protected $signature = 'adminlte:test';
    protected $description = 'Test AdminLte configuration and variable availability';

    public function handle()
    {
        $this->info('🔧 Probando configuración de AdminLTE...');

        try {
            // Verificar si AdminLte está disponible
            $adminlte = app(AdminLte::class);
            $this->info('✅ AdminLte instancia creada correctamente');

            // Verificar métodos disponibles
            if (method_exists($adminlte, 'menu')) {
                $this->info('✅ Método menu() disponible');

                // Probar menús
                $navbarLeft = $adminlte->menu('navbar-left');
                $navbarRight = $adminlte->menu('navbar-right');

                $this->info("📋 Menú navbar-left: " . count($navbarLeft) . " elementos");
                $this->info("📋 Menú navbar-right: " . count($navbarRight) . " elementos");
            } else {
                $this->warn('⚠️ Método menu() no disponible');
            }

            // Verificar configuración
            $config = config('adminlte');
            if ($config) {
                $this->info('✅ Configuración de AdminLTE cargada');
                $this->info("🎨 Tema: " . ($config['classes_topnav'] ?? 'No definido'));
            } else {
                $this->warn('⚠️ Configuración de AdminLTE no encontrada');
            }

            $this->info("\n🌐 La variable \$adminlte debería estar disponible en todas las vistas.");

        } catch (\Exception $e) {
            $this->error('❌ Error al probar AdminLTE: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

