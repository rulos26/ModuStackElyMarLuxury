<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FooterService;
use App\Models\AppSetting;

class DebugFooterFormCommand extends Command
{
    protected $signature = 'debug:footer-form';
    protected $description = 'Debug del formulario del footer para ver qué datos se están recibiendo.';

    public function handle()
    {
        $this->info('🔍 Debug del formulario del footer...');

        // Simular datos como si vinieran del formulario
        $requestData = [
            'footer_type' => 'traditional',
            'footer_company_name' => 'Ely Mar Luxury v',
            'footer_company_url' => 'https://rulossoluciones.com/modustackelymarluxury',
            'footer_show_copyright' => '1',
            'footer_show_version' => '1',
            'footer_version_text' => '1.0.03',
            'footer_left_text' => 'Versión 1.0.2',
            'footer_center_text' => '',
            'footer_layout' => 'traditional',
            'footer_custom_html' => '',
            'footer_use_custom_html' => '0',
            'footer_show_center_text' => '0',
        ];

        $this->line('');
        $this->info('📝 Datos simulados del formulario:');
        foreach ($requestData as $key => $value) {
            $this->line("  {$key}: {$value}");
        }

        $this->line('');
        $this->info('🔄 Procesando datos como lo haría el controlador...');

        // Simular el procesamiento del controlador
        $footerService = app(FooterService::class);

        $useCustomHtml = $requestData['footer_type'] === 'custom';
        $showCenterText = $requestData['footer_layout'] === 'center';

        $footerData = [
            'use_custom_html' => $useCustomHtml,
            'custom_html' => $useCustomHtml ? $requestData['footer_custom_html'] : '',
            'company_name' => $requestData['footer_company_name'] ?? 'Ely Mar Luxury',
            'company_url' => $requestData['footer_company_url'] ?? '#',
            'show_copyright' => (bool) $requestData['footer_show_copyright'],
            'show_version' => (bool) $requestData['footer_show_version'],
            'version_text' => $requestData['footer_version_text'] ?? '1.0.0',
            'left_text' => $requestData['footer_left_text'] ?? '',
            'center_text' => $showCenterText ? $requestData['footer_center_text'] : '',
            'show_center_text' => $showCenterText,
        ];

        $this->line('');
        $this->info('📊 Datos procesados para el FooterService:');
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
        $this->info('🔍 Verificando datos guardados en BD:');

        $footerSettings = AppSetting::where('key', 'like', 'footer_%')->get();
        foreach ($footerSettings as $setting) {
            $this->line("  {$setting->key}: {$setting->value} ({$setting->type})");
        }

        $this->line('');
        $this->info('🎨 Generando HTML del footer:');
        $footerInfo = $footerService->getFooterInfo();
        $this->line($footerInfo['html_preview']);

        return 0;
    }
}
