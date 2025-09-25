<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FooterService;

class TestFooterSaveCommand extends Command
{
    protected $signature = 'test:footer-save';
    protected $description = 'Simula el guardado de configuración del footer para probar.';

    public function handle()
    {
        $this->info('🧪 Probando guardado de configuración del footer...');

        $footerService = app(FooterService::class);

        // Simular datos como los que vienen del formulario
        $footerData = [
            'use_custom_html' => false,
            'custom_html' => '',
            'company_name' => 'Ely Mar Luxury v',
            'company_url' => 'https://rulossoluciones.com/modustackelymarluxury',
            'show_copyright' => true,
            'show_version' => true,
            'version_text' => '1.0.3',
            'left_text' => 'Versión 1.0.2',
            'center_text' => '',
            'show_center_text' => false,
        ];

        $this->line('📝 Datos a guardar:');
        foreach ($footerData as $key => $value) {
            $this->line("  {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }

        $this->line('');
        $this->info('💾 Guardando en base de datos...');

        $result = $footerService->updateFooterConfig($footerData);

        if ($result) {
            $this->info('✅ Configuración guardada exitosamente');
        } else {
            $this->error('❌ Error al guardar la configuración');
            return 1;
        }

        $this->line('');
        $this->info('🔍 Verificando configuración guardada...');

        $footerInfo = $footerService->getFooterInfo();
        $config = $footerInfo['config'];

        $this->line('📋 Configuración actual:');
        $this->line("  Nombre empresa: {$config['company_name']}");
        $this->line("  URL empresa: {$config['company_url']}");
        $this->line("  Versión: {$config['version_text']}");
        $this->line("  Texto izquierda: {$config['left_text']}");
        $this->line("  Mostrar copyright: " . ($config['show_copyright'] ? 'Sí' : 'No'));
        $this->line("  Mostrar versión: " . ($config['show_version'] ? 'Sí' : 'No'));

        $this->line('');
        $this->info('🎨 HTML generado:');
        $this->line($footerInfo['html_preview']);

        $this->line('');
        $this->info('✅ Prueba completada. Ahora verifica el footer en la página web.');

        return 0;
    }
}
