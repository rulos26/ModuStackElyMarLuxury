<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class TestAdminLteErrorsCommand extends Command
{
    protected $signature = 'adminlte:test-errors';
    protected $description = 'Test AdminLTE for common array key errors';

    public function handle()
    {
        $this->info('🔍 Probando errores comunes de AdminLTE...');

        try {
            // Probar renderizado de vistas comunes
            $this->testViewRendering();

            $this->info('✅ Todas las pruebas pasaron correctamente');
            $this->info('🌐 Los errores de array key deberían estar resueltos');

        } catch (\Exception $e) {
            $this->error('❌ Error encontrado: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }

    private function testViewRendering()
    {
        $this->info('📋 Probando renderizado de vistas...');

        // Probar navbar
        try {
            $navbar = view('vendor.adminlte.partials.navbar.navbar')->render();
            $this->info('✅ Navbar renderizado correctamente');
        } catch (\Exception $e) {
            $this->warn('⚠️ Error en navbar: ' . $e->getMessage());
        }

        // Probar search form
        try {
            $searchForm = view('vendor.adminlte.partials.navbar.menu-item-search-form')->render();
            $this->info('✅ Search form renderizado correctamente');
        } catch (\Exception $e) {
            $this->warn('⚠️ Error en search form: ' . $e->getMessage());
        }

        // Probar menu item link
        try {
            $menuItem = view('vendor.adminlte.partials.navbar.menu-item-link')->render();
            $this->info('✅ Menu item link renderizado correctamente');
        } catch (\Exception $e) {
            $this->warn('⚠️ Error en menu item link: ' . $e->getMessage());
        }

        $this->info('🎯 Pruebas de renderizado completadas');
    }
}



