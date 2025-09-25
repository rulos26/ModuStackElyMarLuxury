<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FooterService;
use App\Models\AppSetting;

class VerifyFooterCommand extends Command
{
    protected $signature = 'verify:footer';
    protected $description = 'Verifica la configuración del footer en el sistema.';

    public function handle()
    {
        $this->info('🔍 Verificando configuración del footer...');

        $footerService = app(FooterService::class);
        $footerInfo = $footerService->getFooterInfo();

        $this->line('');
        $this->info('📋 Configuración actual del footer:');
        $this->line('');

        // Mostrar configuración
        $config = $footerInfo['config'];
        $this->line("✅ Usar HTML personalizado: " . ($config['use_custom_html'] ? 'Sí' : 'No'));
        $this->line("✅ Mostrar copyright: " . ($config['show_copyright'] ? 'Sí' : 'No'));
        $this->line("✅ Mostrar versión: " . ($config['show_version'] ? 'Sí' : 'No'));
        $this->line("✅ Mostrar texto centrado: " . ($config['show_center_text'] ? 'Sí' : 'No'));
        $this->line("✅ Nombre de empresa: {$config['company_name']}");
        $this->line("✅ URL de empresa: {$config['company_url']}");
        $this->line("✅ Texto de versión: {$config['version_text']}");
        $this->line("✅ Texto izquierda: " . ($config['left_text'] ?: 'Vacío'));
        $this->line("✅ Texto centrado: " . ($config['center_text'] ?: 'Vacío'));

        if ($config['use_custom_html']) {
            $this->line("✅ HTML personalizado: " . (strlen($config['custom_html']) > 0 ? 'Configurado (' . strlen($config['custom_html']) . ' caracteres)' : 'Vacío'));
        }

        $this->line('');
        $this->info('🎨 HTML generado del footer:');
        $this->line('');
        $this->line($footerInfo['html_preview']);

        $this->line('');
        $this->info('📊 Estadísticas:');
        $this->line("✅ Footer personalizado: " . ($footerInfo['is_customized'] ? 'Sí' : 'No'));
        $this->line("✅ Tiene HTML personalizado: " . ($footerInfo['has_custom_html'] ? 'Sí' : 'No'));

        $this->line('');
        $this->info('Verificación completada.');
        return 0;
    }
}
