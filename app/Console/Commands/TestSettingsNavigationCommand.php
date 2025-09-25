<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class TestSettingsNavigationCommand extends Command
{
    protected $signature = 'admin:test-settings-navigation';
    protected $description = 'Test admin settings navigation and URL generation';

    public function handle()
    {
        $this->info('🧪 Probando navegación de configuración...');

        try {
            // Probar todas las secciones de configuración
            $sections = ['general', 'appearance', 'security', 'notifications', 'advanced'];

            foreach ($sections as $section) {
                $this->info("📋 Probando sección: {$section}");

                // Probar ruta de sección
                $sectionUrl = route('admin.settings.section', $section);
                $this->info("   Sección URL: {$sectionUrl}");

                if (str_contains($sectionUrl, '#')) {
                    $this->warn("   ⚠️ URL contiene '#' - problema detectado");
                } else {
                    $this->info("   ✅ URL limpia");
                }

                // Probar ruta de actualización
                $updateUrl = route('admin.settings.update.section', $section);
                $this->info("   Actualización URL: {$updateUrl}");

                if (str_contains($updateUrl, '#')) {
                    $this->warn("   ⚠️ URL contiene '#' - problema detectado");
                } else {
                    $this->info("   ✅ URL limpia");
                }

                // Verificar que las rutas existen
                if (Route::has('admin.settings.section')) {
                    $this->info("   ✅ Ruta de sección existe");
                } else {
                    $this->error("   ❌ Ruta de sección NO existe");
                }

                if (Route::has('admin.settings.update.section')) {
                    $this->info("   ✅ Ruta de actualización existe");
                } else {
                    $this->error("   ❌ Ruta de actualización NO existe");
                }

                $this->info("   ---");
            }

            // Probar navegación entre secciones
            $this->info("📋 Probando navegación entre secciones:");

            $navigationLinks = [
                'General' => route('admin.settings.section', 'general'),
                'Apariencia' => route('admin.settings.section', 'appearance'),
                'Seguridad' => route('admin.settings.section', 'security'),
                'Notificaciones' => route('admin.settings.section', 'notifications'),
                'Avanzado' => route('admin.settings.section', 'advanced'),
            ];

            foreach ($navigationLinks as $name => $url) {
                $this->info("   {$name}: {$url}");

                if (str_contains($url, '#')) {
                    $this->warn("   ⚠️ Contiene '#' - problema detectado");
                } else {
                    $this->info("   ✅ URL limpia");
                }
            }

            $this->info('🎯 Prueba de navegación completada');
            $this->info('💡 Si sigues viendo URLs con #, puede ser un problema del navegador o JavaScript');

        } catch (\Exception $e) {
            $this->error('❌ Error en prueba de navegación: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

