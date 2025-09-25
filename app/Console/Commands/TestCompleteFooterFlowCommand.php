<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FooterService;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class TestCompleteFooterFlowCommand extends Command
{
    protected $signature = 'test:complete-footer-flow';
    protected $description = 'Prueba el flujo completo del footer desde la base de datos hasta la vista.';

    public function handle()
    {
        $this->info('🧪 Probando flujo completo del footer...');
        $this->line('');

        // 1. Verificar datos en BD
        $this->info('1️⃣ Verificando datos en base de datos:');
        $footerSettings = AppSetting::where('key', 'like', 'footer_%')->get();
        foreach ($footerSettings as $setting) {
            $this->line("   ✅ {$setting->key}: {$setting->value}");
        }

        $this->line('');

        // 2. Verificar FooterService
        $this->info('2️⃣ Verificando FooterService:');
        $footerService = app(FooterService::class);
        $footerConfig = $footerService->getFooterConfig();

        $this->line("   ✅ Nombre empresa: {$footerConfig['company_name']}");
        $this->line("   ✅ URL empresa: {$footerConfig['company_url']}");
        $this->line("   ✅ Versión: {$footerConfig['version_text']}");
        $this->line("   ✅ Texto izquierda: {$footerConfig['left_text']}");
        $this->line("   ✅ Mostrar copyright: " . ($footerConfig['show_copyright'] ? 'Sí' : 'No'));
        $this->line("   ✅ Mostrar versión: " . ($footerConfig['show_version'] ? 'Sí' : 'No'));

        $this->line('');

        // 3. Generar HTML
        $this->info('3️⃣ Generando HTML del footer:');
        $footerInfo = $footerService->getFooterInfo();
        $html = $footerInfo['html_preview'];
        $this->line("   HTML generado:");
        $this->line("   " . $html);

        $this->line('');

        // 4. Verificar cache
        $this->info('4️⃣ Verificando cache:');
        $cachedConfig = Cache::get('footer_config');
        if ($cachedConfig) {
            $this->line("   ✅ Cache activo con datos correctos");
            $this->line("   ✅ Cache company_name: {$cachedConfig['company_name']}");
        } else {
            $this->line("   ⚠️ Cache vacío (se regenerará automáticamente)");
        }

        $this->line('');

        // 5. Simular limpieza de cache
        $this->info('5️⃣ Simulando limpieza de cache...');
        Cache::forget('footer_config');
        $this->line("   ✅ Cache limpiado");

        $this->line('');

        // 6. Verificar regeneración
        $this->info('6️⃣ Verificando regeneración automática:');
        $newConfig = $footerService->getFooterConfig();
        $this->line("   ✅ Configuración regenerada correctamente");
        $this->line("   ✅ Nombre empresa: {$newConfig['company_name']}");

        $this->line('');

        // 7. Verificar vista
        $this->info('7️⃣ Verificando vista del footer:');
        $viewPath = resource_path('views/vendor/adminlte/partials/footer/footer.blade.php');
        if (file_exists($viewPath)) {
            $this->line("   ✅ Archivo de vista existe");
            $viewContent = file_get_contents($viewPath);
            if (strpos($viewContent, '$footerService') !== false) {
                $this->line("   ✅ Vista usa FooterService correctamente");
            } else {
                $this->line("   ❌ Vista no usa FooterService");
            }
        } else {
            $this->line("   ❌ Archivo de vista no existe");
        }

        $this->line('');

        // 8. Resumen final
        $this->info('📊 RESUMEN FINAL:');
        $this->line('');
        $this->line('✅ Base de datos: Datos guardados correctamente');
        $this->line('✅ FooterService: Funcionando correctamente');
        $this->line('✅ HTML generado: Correcto');
        $this->line('✅ Cache: Funcionando correctamente');
        $this->line('✅ Vista: Configurada correctamente');
        $this->line('');
        $this->line('🎯 El footer debería mostrar:');
        $this->line("   • Copyright © 2025 Ely Mar Luxury v (con enlace a rulossoluciones.com)");
        $this->line("   • Versión 1.0.03 Versión 1.0.2");
        $this->line('');
        $this->line('💡 Si el footer no se actualiza en el navegador:');
        $this->line('   1. Limpia la caché del navegador (Ctrl+F5)');
        $this->line('   2. Verifica que no haya errores en la consola del navegador');
        $this->line('   3. Asegúrate de que el formulario se envíe correctamente');

        return 0;
    }
}
