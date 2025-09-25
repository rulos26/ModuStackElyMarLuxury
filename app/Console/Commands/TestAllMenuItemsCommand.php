<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

class TestAllMenuItemsCommand extends Command
{
    protected $signature = 'adminlte:test-all-menu';
    protected $description = 'Test all AdminLTE menu item files for array key errors';

    public function handle()
    {
        $this->info('🧪 Probando todos los archivos de menú AdminLTE...');

        $menuFiles = [
            'vendor.adminlte.partials.navbar.navbar',
            'vendor.adminlte.partials.navbar.menu-item-search-form',
            'vendor.adminlte.partials.navbar.menu-item-link',
            'vendor.adminlte.partials.navbar.menu-item-dropdown-menu',
            'vendor.adminlte.partials.navbar.dropdown-item-link',
            'vendor.adminlte.partials.sidebar.menu-item-link',
            'vendor.adminlte.partials.sidebar.menu-item-treeview-menu',
        ];

        $errors = 0;

        foreach ($menuFiles as $file) {
            try {
                $this->info("📋 Probando: {$file}");

                // Proporcionar variable $item para archivos que la necesitan
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

                $content = view($file, compact('item'))->render();
                $this->info("✅ {$file} - OK");
            } catch (\Exception $e) {
                $this->error("❌ {$file} - Error: " . $e->getMessage());
                $errors++;
            }
        }

        if ($errors === 0) {
            $this->info('🎉 ¡Todos los archivos de menú funcionan correctamente!');
            $this->info('🌐 No hay errores de array key undefined');
        } else {
            $this->error("⚠️ Se encontraron {$errors} errores");
        }

        return $errors > 0 ? 1 : 0;
    }
}
