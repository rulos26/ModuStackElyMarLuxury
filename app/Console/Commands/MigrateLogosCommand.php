<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppSetting;
use App\Services\LogoService;
use Illuminate\Support\Facades\Storage;

class MigrateLogosCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logos:migrate {--force : Forzar migración incluso si ya existen archivos}';

    /**
     * The console command description.
     */
    protected $description = 'Migrar logos de base de datos a archivos del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando migración de logos...');

        // Obtener configuración actual del logo
        $logoSetting = AppSetting::where('key', 'app_logo')->first();

        if (!$logoSetting || !$logoSetting->value) {
            $this->warn('⚠️  No se encontró logo en la base de datos');
            return;
        }

        $logoValue = $logoSetting->value;

        // Verificar si es un data URL (base64)
        if (!str_starts_with($logoValue, 'data:')) {
            $this->info('✅ El logo ya está en formato de ruta de archivo');
            return;
        }

        // Verificar si ya existe un archivo de logo
        $currentLogoPath = LogoService::getCurrentLogoPath();
        if ($currentLogoPath && !$this->option('force')) {
            $this->warn('⚠️  Ya existe un logo en el sistema de archivos. Usa --force para sobrescribir');
            return;
        }

        try {
            // Crear directorio si no existe
            LogoService::ensureLogoDirectory();

            // Extraer datos de la data URL
            $logoData = $this->extractLogoData($logoValue);

            if (!$logoData) {
                $this->error('❌ No se pudieron extraer los datos del logo');
                return;
            }

            // Determinar extensión basada en el MIME type
            $extension = $this->getExtensionFromMimeType($logoData['mime_type']);

            // Crear nombre de archivo
            $filename = 'app-logo.' . $extension;
            $filePath = 'logos/' . $filename;

            // Guardar archivo
            Storage::disk('public')->put($filePath, $logoData['data']);

            // Actualizar configuración en base de datos
            $newLogoPath = Storage::disk('public')->url($filePath);
            AppSetting::setValue('app_logo', $newLogoPath, 'string', 'Logo de la aplicación');

            $this->info("✅ Logo migrado exitosamente:");
            $this->line("   📁 Archivo: storage/app/public/{$filePath}");
            $this->line("   🌐 URL: {$newLogoPath}");
            $this->line("   📊 Tamaño: " . number_format(strlen($logoData['data']) / 1024, 1) . " KB");

        } catch (\Exception $e) {
            $this->error("❌ Error durante la migración: " . $e->getMessage());
        }
    }

    /**
     * Extraer datos del logo desde data URL
     */
    private function extractLogoData(string $dataUrl): ?array
    {
        // Formato: data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...
        if (!preg_match('/^data:([^;]+);base64,(.+)$/', $dataUrl, $matches)) {
            return null;
        }

        $mimeType = $matches[1];
        $base64Data = $matches[2];
        $data = base64_decode($base64Data);

        if ($data === false) {
            return null;
        }

        return [
            'mime_type' => $mimeType,
            'data' => $data
        ];
    }

    /**
     * Obtener extensión de archivo basada en MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp'
        ];

        return $mimeToExt[$mimeType] ?? 'png';
    }
}



