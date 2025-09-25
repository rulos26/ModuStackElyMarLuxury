<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppSetting;

class FixLogoCommand extends Command
{
    protected $signature = 'fix:logo';
    protected $description = 'Fix logo configuration';

    public function handle()
    {
        $this->info('Verificando configuración del logo...');

        // Verificar configuración actual
        $logoSetting = AppSetting::where('key', 'app_logo')->first();

        if ($logoSetting) {
            $this->info("Logo actual en BD: {$logoSetting->value}");
        } else {
            $this->info("No hay logo configurado en la BD");
        }

        // Configurar el logo correcto
        $logoPath = '/storage/logos/app-logo.jpeg';

        if ($logoSetting) {
            $logoSetting->update(['value' => $logoPath]);
            $this->info("Logo actualizado en BD: {$logoPath}");
        } else {
            AppSetting::create([
                'key' => 'app_logo',
                'value' => $logoPath,
                'type' => 'string',
                'description' => 'Logo de la aplicación'
            ]);
            $this->info("Logo creado en BD: {$logoPath}");
        }

        // Limpiar caché
        $this->call('config:clear');
        $this->call('cache:clear');

        $this->info('Caché limpiado. El logo debería funcionar ahora.');
        return 0;
    }
}
