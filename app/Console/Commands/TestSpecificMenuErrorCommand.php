<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class TestSpecificMenuErrorCommand extends Command
{
    protected $signature = 'adminlte:test-specific-error';
    protected $description = 'Test specific AdminLTE menu error for submenu_class';

    public function handle()
    {
        $this->info('🔍 Probando error específico de submenu_class...');

        try {
            // Probar el archivo específico que causaba el error
            $item = [
                'href' => '#',
                'method' => 'GET',
                'text' => 'Test Item',
                'input_name' => 'search',
                'class' => '',
                'icon' => 'fas fa-test',
                'icon_color' => '',
                'label' => '',
                'label_color' => 'primary',
                'target' => '',
                'id' => '',
                'data-compiled' => '',
                'submenu' => [],
                'submenu_class' => '',
                'shift' => ''
            ];

            $this->info('📋 Probando menu-item-treeview-menu...');
            $content = view('vendor.adminlte.partials.sidebar.menu-item-treeview-menu', compact('item'))->render();
            $this->info('✅ menu-item-treeview-menu - OK');

            // Probar sin submenu_class
            unset($item['submenu_class']);
            $this->info('📋 Probando sin submenu_class...');
            $content = view('vendor.adminlte.partials.sidebar.menu-item-treeview-menu', compact('item'))->render();
            $this->info('✅ Sin submenu_class - OK');

            // Probar sin submenu
            unset($item['submenu']);
            $this->info('📋 Probando sin submenu...');
            $content = view('vendor.adminlte.partials.sidebar.menu-item-treeview-menu', compact('item'))->render();
            $this->info('✅ Sin submenu - OK');

            $this->info('🎉 ¡Todos los tests específicos pasaron!');
            $this->info('🌐 El error de submenu_class está resuelto');

        } catch (\Exception $e) {
            $this->error('❌ Error encontrado: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }
}



