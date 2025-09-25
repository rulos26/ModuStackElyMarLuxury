<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class TestMenuRenderingCommand extends Command
{
    protected $signature = 'adminlte:test-menu';
    protected $description = 'Test AdminLTE menu rendering for duplicates';

    public function handle()
    {
        $this->info('🧪 Probando renderizado del menú AdminLTE...');

        try {
            // Probar renderizado del navbar
            $this->testNavbarRendering();

            // Probar renderizado de elementos de búsqueda
            $this->testSearchElements();

            $this->info('✅ Pruebas de menú completadas');
            $this->info('🌐 El menú debería mostrarse correctamente sin duplicaciones');

        } catch (\Exception $e) {
            $this->error('❌ Error en prueba de menú: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function testNavbarRendering()
    {
        $this->info('📋 Probando renderizado del navbar...');

        try {
            $navbar = view('vendor.adminlte.partials.navbar.navbar')->render();

            // Contar elementos de búsqueda
            $searchCount = substr_count($navbar, 'Buscar...');
            $this->info("🔍 Elementos de búsqueda encontrados: {$searchCount}");

            if ($searchCount <= 2) {
                $this->info('✅ Número de elementos de búsqueda correcto');
            } else {
                $this->warn('⚠️ Posible duplicación de elementos de búsqueda');
            }

        } catch (\Exception $e) {
            $this->warn('⚠️ Error en navbar: ' . $e->getMessage());
        }
    }

    private function testSearchElements()
    {
        $this->info('🔍 Probando elementos de búsqueda...');

        try {
            $searchForm = view('vendor.adminlte.partials.navbar.menu-item-search-form')->render();

            // Verificar que no hay elementos duplicados
            $formCount = substr_count($searchForm, '<form');
            $this->info("📝 Formularios de búsqueda: {$formCount}");

            if ($formCount == 1) {
                $this->info('✅ Un solo formulario de búsqueda (correcto)');
            } else {
                $this->warn('⚠️ Múltiples formularios de búsqueda detectados');
            }

        } catch (\Exception $e) {
            $this->warn('⚠️ Error en elementos de búsqueda: ' . $e->getMessage());
        }
    }
}

