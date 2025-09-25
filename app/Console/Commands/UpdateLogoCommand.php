<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\ViewHelper;

class UpdateLogoCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logo:update';

    /**
     * The console command description.
     */
    protected $description = 'Actualizar configuración del logo en AdminLTE';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Actualizando configuración del logo...');

        try {
            $logoPath = ViewHelper::getLogoForView();
            $appName = ViewHelper::getAppNameForView();

            // Actualizar configuración
            config([
                'adminlte.logo_img' => $logoPath,
                'adminlte.logo_img_alt' => $appName,
                'adminlte.auth_logo.img.path' => $logoPath,
                'adminlte.preloader.img.path' => $logoPath,
                'adminlte.preloader.img.alt' => $appName . ' Preloader',
            ]);

            $this->info("✅ Logo actualizado:");
            $this->line("   📁 Ruta: {$logoPath}");
            $this->line("   🏷️  Nombre: {$appName}");

            // Limpiar caché
            $this->call('config:clear');
            $this->call('cache:clear');

            $this->info("✅ Caché limpiado");

        } catch (\Exception $e) {
            $this->error("❌ Error al actualizar logo: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
