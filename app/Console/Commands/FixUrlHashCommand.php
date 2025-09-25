<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixUrlHashCommand extends Command
{
    protected $signature = 'admin:fix-url-hash';
    protected $description = 'Fix URL hash issues in admin settings views';

    public function handle()
    {
        $this->info('🔧 Solucionando problemas de URL con hash...');

        try {
            // Verificar archivos de vista que pueden tener problemas
            $viewFiles = [
                'resources/views/admin/settings/sections/appearance.blade.php',
                'resources/views/admin/settings/sections/general.blade.php',
                'resources/views/admin/settings/sections/security.blade.php',
                'resources/views/admin/settings/sections/notifications.blade.php',
                'resources/views/admin/settings/sections/advanced.blade.php',
            ];

            $fixed = 0;

            foreach ($viewFiles as $file) {
                if (File::exists($file)) {
                    $this->info("📋 Verificando: {$file}");

                    $content = File::get($file);
                    $originalContent = $content;

                    // Buscar y corregir enlaces problemáticos
                    $patterns = [
                        // Enlaces con href="#" que deberían tener rutas
                        '/href="#"/' => 'href="{{ route(\'admin.settings.dashboard\') }}"',
                        // Enlaces con href="" que deberían tener rutas
                        '/href=""/' => 'href="{{ route(\'admin.settings.dashboard\') }}"',
                        // Enlaces con href="#" en botones
                        '/href="#" class="btn/' => 'href="{{ route(\'admin.settings.dashboard\') }}" class="btn',
                    ];

                    foreach ($patterns as $pattern => $replacement) {
                        $content = preg_replace($pattern, $replacement, $content);
                    }

                    if ($content !== $originalContent) {
                        File::put($file, $content);
                        $this->info("   ✅ Archivo corregido");
                        $fixed++;
                    } else {
                        $this->info("   ✅ Archivo sin problemas");
                    }
                } else {
                    $this->warn("   ⚠️ Archivo no encontrado: {$file}");
                }
            }

            if ($fixed > 0) {
                $this->info("🎉 Se corrigieron {$fixed} archivos");
            } else {
                $this->info("✅ No se encontraron problemas que corregir");
            }

            $this->info('🌐 Los enlaces deberían funcionar correctamente ahora');

        } catch (\Exception $e) {
            $this->error('❌ Error al corregir archivos: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

