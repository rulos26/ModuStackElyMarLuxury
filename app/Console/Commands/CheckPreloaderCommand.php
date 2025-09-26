<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPreloaderCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'preloader:check';

    /**
     * The console command description.
     */
    protected $description = 'Verificar configuración del preloader';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando configuración del preloader...');

        // Verificar configuración del preloader
        $preloaderEnabled = config('adminlte.preloader.enabled');
        $preloaderMode = config('adminlte.preloader.mode');
        $preloaderPath = config('adminlte.preloader.img.path');
        $preloaderAlt = config('adminlte.preloader.img.alt');
        $preloaderWidth = config('adminlte.preloader.img.width');
        $preloaderHeight = config('adminlte.preloader.img.height');

        $this->line("📊 Estado del preloader:");
        $this->line("   ✅ Habilitado: " . ($preloaderEnabled ? 'Sí' : 'No'));
        $this->line("   🎭 Modo: {$preloaderMode}");
        $this->line("   📁 Ruta: {$preloaderPath}");
        $this->line("   🏷️  Alt: {$preloaderAlt}");
        $this->line("   📏 Dimensiones: {$preloaderWidth}x{$preloaderHeight}");

        // Verificar si el archivo existe
        if ($preloaderPath && !str_starts_with($preloaderPath, 'data:')) {
            $fullPath = public_path($preloaderPath);
            $exists = file_exists($fullPath);

            $this->line("   📂 Archivo existe: " . ($exists ? 'Sí' : 'No'));

            if ($exists) {
                $size = filesize($fullPath);
                $this->line("   📊 Tamaño: " . number_format($size / 1024, 1) . " KB");
            }
        }

        // Verificar configuración de logo principal
        $logoPath = config('adminlte.logo_img');
        $this->line("\n📊 Logo principal:");
        $this->line("   📁 Ruta: {$logoPath}");

        // Verificar si son iguales
        if ($preloaderPath === $logoPath) {
            $this->info("✅ El preloader usa el mismo logo que el principal");
        } else {
            $this->warn("⚠️  El preloader NO usa el mismo logo que el principal");
        }

        return 0;
    }
}



