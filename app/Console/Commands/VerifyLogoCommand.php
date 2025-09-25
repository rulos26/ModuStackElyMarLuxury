<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifyLogoCommand extends Command
{
    protected $signature = 'verify:logo';
    protected $description = 'Verify logo configuration';

    public function handle()
    {
        $this->info('🔍 Verificando configuración del logo...');

        // 1. Verificar archivo físico
        $logoPath = '/storage/logos/app-logo.jpeg';
        $filePath = public_path($logoPath);

        if (file_exists($filePath)) {
            $this->info("✅ Archivo de logo existe: {$filePath}");
            $this->info("   📁 Tamaño: " . number_format(filesize($filePath) / 1024, 1) . " KB");
        } else {
            $this->error("❌ Archivo de logo NO existe: {$filePath}");
        }

        // 2. Verificar configuración en BD
        $logoSetting = \App\Models\AppSetting::where('key', 'app_logo')->first();
        if ($logoSetting && $logoSetting->value) {
            $this->info("✅ Logo configurado en BD: {$logoSetting->value}");
        } else {
            $this->error("❌ Logo NO configurado en BD");
        }

        // 3. Verificar ViewHelper
        $viewHelperLogo = \App\Helpers\ViewHelper::getLogoForView();
        $this->info("✅ Logo desde ViewHelper: {$viewHelperLogo}");

        // 4. Verificar configuración de AdminLTE
        $adminLteLogo = config('adminlte.logo_img');
        $this->info("✅ Logo desde AdminLTE config: {$adminLteLogo}");

        // 5. Verificar que la URL sea accesible
        $fullUrl = url($logoPath);
        $this->info("🌐 URL completa del logo: {$fullUrl}");

        $this->info('Verificación completada.');
        return 0;
    }
}
