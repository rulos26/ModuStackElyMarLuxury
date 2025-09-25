<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ShowFooterDatabaseCommand extends Command
{
    protected $signature = 'show:footer-database';
    protected $description = 'Muestra cómo se almacenan las configuraciones del footer en la base de datos.';

    public function handle()
    {
        $this->info('🗄️ Estructura de Base de Datos para Configuraciones del Footer');
        $this->line('');

        // Mostrar estructura de la tabla
        $this->info('📋 Estructura de la tabla app_settings:');
        $columns = Schema::getColumnListing('app_settings');
        foreach ($columns as $column) {
            $this->line("  - {$column}");
        }

        $this->line('');
        $this->info('🔍 Configuraciones del footer en la base de datos:');

        $footerSettings = AppSetting::where('key', 'like', 'footer_%')->get();

        if ($footerSettings->isEmpty()) {
            $this->warn('⚠️ No hay configuraciones del footer guardadas aún.');
            $this->line('Las configuraciones se crean automáticamente cuando el usuario las configura.');
        } else {
            foreach ($footerSettings as $setting) {
                $this->line("✅ {$setting->key} = {$setting->value} ({$setting->type})");
            }
        }

        $this->line('');
        $this->info('📊 Ejemplo de cómo se guardan los datos:');
        $this->line('');

        $examples = [
            'footer_company_name' => 'Ely Mar Luxury',
            'footer_company_url' => 'https://www.ejemplo.com',
            'footer_show_copyright' => '1',
            'footer_show_version' => '1',
            'footer_version_text' => '1.0.0',
            'footer_left_text' => 'Texto personalizado',
            'footer_center_text' => '',
            'footer_custom_html' => '<footer>HTML personalizado</footer>',
            'footer_use_custom_html' => '0',
            'footer_show_center_text' => '0',
        ];

        foreach ($examples as $key => $value) {
            $type = in_array($key, ['footer_show_copyright', 'footer_show_version', 'footer_use_custom_html', 'footer_show_center_text']) ? 'boolean' : 'string';
            $this->line("INSERT INTO app_settings (key, value, type, description) VALUES ('{$key}', '{$value}', '{$type}', 'Configuración del footer');");
        }

        $this->line('');
        $this->info('🔄 Flujo de datos:');
        $this->line('1. Usuario configura footer en la interfaz');
        $this->line('2. Controlador procesa los datos');
        $this->line('3. FooterService::updateFooterConfig() guarda en BD');
        $this->line('4. AppSetting::setValue() crea/actualiza registros');
        $this->line('5. FooterService::getFooterConfig() lee desde BD');
        $this->line('6. Vista renderiza footer dinámico');

        $this->line('');
        $this->info('💾 Persistencia garantizada:');
        $this->line('✅ Todos los cambios se guardan permanentemente');
        $this->line('✅ Cache automático para mejor rendimiento');
        $this->line('✅ Valores por defecto si no hay configuración');
        $this->line('✅ Tipos de datos correctos (string, boolean)');

        return 0;
    }
}
