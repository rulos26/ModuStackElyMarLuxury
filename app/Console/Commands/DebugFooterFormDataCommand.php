<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FooterService;
use App\Models\AppSetting;

class DebugFooterFormDataCommand extends Command
{
    protected $signature = 'debug:footer-form-data';
    protected $description = 'Debug de los datos del formulario del footer.';

    public function handle()
    {
        $this->info('🔍 Debug de datos del formulario del footer...');

        // 1. Verificar datos en BD
        $this->line('');
        $this->info('1️⃣ Datos en la base de datos:');
        $footerSettings = AppSetting::where('key', 'like', 'footer_%')->get();
        foreach ($footerSettings as $setting) {
            $this->line("   {$setting->key}: {$setting->value} ({$setting->type})");
        }

        // 2. Verificar FooterService
        $this->line('');
        $this->info('2️⃣ Datos desde FooterService:');
        $footerService = app(FooterService::class);
        $footerConfig = $footerService->getFooterConfig();

        foreach ($footerConfig as $key => $value) {
            $this->line("   {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }

        // 3. Simular datos que debería enviar el formulario
        $this->line('');
        $this->info('3️⃣ Datos que debería enviar el formulario:');

        $expectedFormData = [
            'footer_type' => 'traditional',
            'footer_company_name' => $footerConfig['company_name'],
            'footer_company_url' => $footerConfig['company_url'],
            'footer_show_copyright' => $footerConfig['show_copyright'] ? '1' : '0',
            'footer_show_version' => $footerConfig['show_version'] ? '1' : '0',
            'footer_version_text' => $footerConfig['version_text'],
            'footer_left_text' => $footerConfig['left_text'],
            'footer_center_text' => $footerConfig['center_text'],
            'footer_layout' => $footerConfig['show_center_text'] ? 'center' : 'traditional',
            'footer_custom_html' => $footerConfig['custom_html'],
            'footer_use_custom_html' => $footerConfig['use_custom_html'] ? '1' : '0',
            'footer_show_center_text' => $footerConfig['show_center_text'] ? '1' : '0',
        ];

        foreach ($expectedFormData as $key => $value) {
            $this->line("   {$key}: {$value}");
        }

        // 4. Verificar si hay diferencias
        $this->line('');
        $this->info('4️⃣ Verificando consistencia:');

        $issues = [];
        if ($footerConfig['company_name'] !== $expectedFormData['footer_company_name']) {
            $issues[] = "company_name no coincide";
        }
        if ($footerConfig['company_url'] !== $expectedFormData['footer_company_url']) {
            $issues[] = "company_url no coincide";
        }
        if ($footerConfig['version_text'] !== $expectedFormData['footer_version_text']) {
            $issues[] = "version_text no coincide";
        }
        if ($footerConfig['left_text'] !== $expectedFormData['footer_left_text']) {
            $issues[] = "left_text no coincide";
        }

        if (empty($issues)) {
            $this->line("   ✅ Todos los datos son consistentes");
        } else {
            $this->line("   ❌ Problemas encontrados:");
            foreach ($issues as $issue) {
                $this->line("      - {$issue}");
            }
        }

        // 5. Verificar campos del formulario
        $this->line('');
        $this->info('5️⃣ Campos del formulario que deberían tener valores:');
        $this->line("   footer_company_name: '{$footerConfig['company_name']}'");
        $this->line("   footer_company_url: '{$footerConfig['company_url']}'");
        $this->line("   footer_version_text: '{$footerConfig['version_text']}'");
        $this->line("   footer_left_text: '{$footerConfig['left_text']}'");
        $this->line("   footer_show_copyright: " . ($footerConfig['show_copyright'] ? 'checked' : 'unchecked'));
        $this->line("   footer_show_version: " . ($footerConfig['show_version'] ? 'checked' : 'unchecked'));

        return 0;
    }
}
