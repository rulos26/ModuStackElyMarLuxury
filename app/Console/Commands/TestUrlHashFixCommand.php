<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestUrlHashFixCommand extends Command
{
    protected $signature = 'admin:test-url-hash-fix';
    protected $description = 'Test the URL hash fix implementation';

    public function handle()
    {
        $this->info('🧪 Probando solución de URL hash...');

        try {
            // Verificar que el JavaScript de prevención está implementado
            $appearanceFile = 'resources/views/admin/settings/sections/appearance.blade.php';

            if (File::exists($appearanceFile)) {
                $content = File::get($appearanceFile);

                $this->info('📋 Verificando implementación en appearance.blade.php:');

                // Verificar JavaScript de prevención
                if (str_contains($content, 'a[href="#"]')) {
                    $this->info('   ✅ JavaScript de prevención implementado');
                } else {
                    $this->warn('   ⚠️ JavaScript de prevención NO encontrado');
                }

                if (str_contains($content, 'window.location.hash')) {
                    $this->info('   ✅ Limpieza de hash implementada');
                } else {
                    $this->warn('   ⚠️ Limpieza de hash NO encontrada');
                }

                if (str_contains($content, 'e.preventDefault()')) {
                    $this->info('   ✅ Prevención de enlaces implementada');
                } else {
                    $this->warn('   ⚠️ Prevención de enlaces NO encontrada');
                }

            } else {
                $this->error('❌ Archivo appearance.blade.php no encontrado');
                return 1;
            }

            // Verificar otras secciones
            $sections = ['general', 'security', 'notifications', 'advanced'];

            foreach ($sections as $section) {
                $file = "resources/views/admin/settings/sections/{$section}.blade.php";

                if (File::exists($file)) {
                    $this->info("📋 Verificando {$section}.blade.php:");

                    $content = File::get($file);

                    if (str_contains($content, 'a[href="#"]')) {
                        $this->info('   ✅ JavaScript de prevención implementado');
                    } else {
                        $this->warn('   ⚠️ JavaScript de prevención NO encontrado');
                    }
                } else {
                    $this->warn("   ⚠️ Archivo {$file} no encontrado");
                }
            }

            $this->info('🎯 Verificación completada');
            $this->info('💡 La solución debería prevenir URLs con # en el navegador');

        } catch (\Exception $e) {
            $this->error('❌ Error en verificación: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

