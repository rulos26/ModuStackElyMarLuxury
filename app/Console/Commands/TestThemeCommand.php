<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppSetting;

class TestThemeCommand extends Command
{
    protected $signature = 'theme:test {--color=#007bff : Color del tema} {--sidebar=light : Estilo del sidebar}';
    protected $description = 'Test theme changes by updating settings';

    public function handle()
    {
        $color = $this->option('color');
        $sidebar = $this->option('sidebar');

        $this->info('🎨 Probando cambios de tema...');

        // Actualizar configuraciones
        AppSetting::setValue('theme_color', $color, 'string', 'Color del tema');
        AppSetting::setValue('sidebar_style', $sidebar, 'string', 'Estilo del sidebar');

        $this->info("✅ Color del tema actualizado a: {$color}");
        $this->info("✅ Estilo del sidebar actualizado a: {$sidebar}");

        $this->info("\n🌐 Los cambios se aplicarán automáticamente en la interfaz web.");
        $this->info("Visita: http://localhost:8000/admin/settings/appearance");

        return 0;
    }
}



