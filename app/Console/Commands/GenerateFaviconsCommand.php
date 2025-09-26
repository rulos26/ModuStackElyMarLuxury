<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FaviconService;
use Illuminate\Http\UploadedFile;

class GenerateFaviconsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'favicons:generate {image_path} {--force : Forzar generación incluso si ya existen}';

    /**
     * The console command description.
     */
    protected $description = 'Generar favicons desde una imagen';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $imagePath = $this->argument('imagePath');
        $force = $this->option('force');

        $this->info('🔄 Generando favicons...');

        // Verificar que el archivo existe
        if (!file_exists($imagePath)) {
            $this->error("❌ El archivo no existe: {$imagePath}");
            return 1;
        }

        // Verificar si ya existen favicons
        $faviconInfo = FaviconService::getFaviconInfo();
        if ($faviconInfo['exists'] && !$force) {
            $this->warn('⚠️  Ya existen favicons. Usa --force para sobrescribir');
            return 0;
        }

        try {
            // Crear un UploadedFile simulado
            $file = new UploadedFile(
                $imagePath,
                basename($imagePath),
                mime_content_type($imagePath),
                null,
                true
            );

            // Generar favicons
            $generatedFiles = FaviconService::uploadFavicon($file);

            $this->info("✅ Favicons generados exitosamente:");
            $this->line("   📁 Directorio: public/favicons/");
            $this->line("   📊 Archivos: " . count($generatedFiles));
            $this->line("   📏 Tamaños: " . implode(', ', array_keys($generatedFiles)));

            // Mostrar información de archivos generados
            $faviconInfo = FaviconService::getFaviconInfo();
            if ($faviconInfo['exists']) {
                $this->line("   💾 Tamaño total: " . number_format($faviconInfo['total_size'] / 1024, 1) . " KB");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error al generar favicons: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}



