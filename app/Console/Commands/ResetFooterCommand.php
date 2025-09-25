<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FooterService;

class ResetFooterCommand extends Command
{
    protected $signature = 'reset:footer';
    protected $description = 'Resetea la configuración del footer a valores por defecto.';

    public function handle()
    {
        $this->info('🔄 Reseteando configuración del footer...');

        $footerService = app(FooterService::class);

        $result = $footerService->resetToDefaults();

        if ($result) {
            $this->info('✅ Footer reseteado a valores por defecto');

            // Limpiar cache
            \Illuminate\Support\Facades\Cache::forget('footer_config');

            $this->line('');
            $this->info('📋 Configuración actual:');
            $footerInfo = $footerService->getFooterInfo();
            $config = $footerInfo['config'];

            $this->line("  Nombre empresa: {$config['company_name']}");
            $this->line("  URL empresa: {$config['company_url']}");
            $this->line("  Versión: {$config['version_text']}");
            $this->line("  Texto izquierda: " . ($config['left_text'] ?: 'Vacío'));

        } else {
            $this->error('❌ Error al resetear el footer');
            return 1;
        }

        return 0;
    }
}
