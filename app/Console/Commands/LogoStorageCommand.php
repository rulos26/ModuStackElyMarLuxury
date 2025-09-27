<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LogoStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logo:storage {action} {--file=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manejar almacenamiento de logos para servidores compartidos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'setup':
                $this->setupLogoStorage();
                break;
            case 'upload':
                $this->uploadLogo();
                break;
            case 'copy-to-public':
                $this->copyToPublic();
                break;
            case 'status':
                $this->checkStatus();
                break;
            default:
                $this->error('Acción no válida. Use: setup, upload, copy-to-public, status');
        }
    }

    /**
     * Configurar almacenamiento de logos
     */
    private function setupLogoStorage()
    {
        $this->info('Configurando almacenamiento de logos...');

        // Crear directorio en storage/app/public/logos
        $storagePath = storage_path('app/public/logos');
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
            $this->info('✅ Directorio storage/app/public/logos creado');
        } else {
            $this->info('✅ Directorio storage/app/public/logos ya existe');
        }

        // Crear directorio en public/logos (para servidores compartidos)
        $publicPath = public_path('logos');
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
            $this->info('✅ Directorio public/logos creado');
        } else {
            $this->info('✅ Directorio public/logos ya existe');
        }

        // Crear logo por defecto si no existe
        $defaultLogoPath = storage_path('app/public/logos/app-logo.svg');
        if (!file_exists($defaultLogoPath)) {
            $defaultLogoSvg = '<svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect width="50" height="50" rx="8" fill="#007bff"/>
<text x="25" y="30" font-family="Arial, sans-serif" font-size="16" fill="white" text-anchor="middle" font-weight="bold">EM</text>
</svg>';
            file_put_contents($defaultLogoPath, $defaultLogoSvg);
            $this->info('✅ Logo por defecto creado');
        }

        // Copiar logo a public
        $this->copyToPublic();

        $this->info('🎉 Configuración completada');
        $this->info('📁 Logos se guardan en: storage/app/public/logos');
        $this->info('🌐 Logos se sirven desde: public/logos');
        $this->info('🔗 URL del logo: ' . url('storage/logos/app-logo.svg'));
    }

    /**
     * Subir logo
     */
    private function uploadLogo()
    {
        $file = $this->option('file');

        if (!$file) {
            $this->error('Especifique el archivo con --file=ruta/al/logo.png');
            return;
        }

        if (!file_exists($file)) {
            $this->error('El archivo no existe: ' . $file);
            return;
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg'];

        if (!in_array(strtolower($extension), $allowedExtensions)) {
            $this->error('Extensión no válida. Use: ' . implode(', ', $allowedExtensions));
            return;
        }

        // Copiar archivo a storage
        $storagePath = storage_path('app/public/logos/app-logo.' . $extension);
        copy($file, $storagePath);
        $this->info('✅ Logo guardado en storage: ' . $storagePath);

        // Copiar a public
        $this->copyToPublic();

        // Actualizar configuración si es necesario
        $this->info('✅ Logo subido exitosamente');
        $this->info('🔗 URL del logo: ' . url('storage/logos/app-logo.' . $extension));
    }

    /**
     * Copiar logos de storage a public
     */
    private function copyToPublic()
    {
        $storagePath = storage_path('app/public/logos');
        $publicPath = public_path('logos');

        if (!is_dir($storagePath)) {
            $this->error('Directorio storage/app/public/logos no existe');
            return;
        }

        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        // Copiar todos los archivos
        $files = glob($storagePath . '/*');
        foreach ($files as $file) {
            $filename = basename($file);
            $publicFile = $publicPath . '/' . $filename;
            copy($file, $publicFile);
            $this->info("📋 Copiado: $filename");
        }

        $this->info('✅ Logos copiados a public/logos');
    }

    /**
     * Verificar estado del almacenamiento
     */
    private function checkStatus()
    {
        $this->info('🔍 Verificando estado del almacenamiento de logos...');

        // Verificar directorios
        $storagePath = storage_path('app/public/logos');
        $publicPath = public_path('logos');

        $this->info('📁 storage/app/public/logos: ' . (is_dir($storagePath) ? '✅ Existe' : '❌ No existe'));
        $this->info('📁 public/logos: ' . (is_dir($publicPath) ? '✅ Existe' : '❌ No existe'));

        // Verificar archivos
        if (is_dir($storagePath)) {
            $files = glob($storagePath . '/*');
            $this->info('📋 Archivos en storage: ' . count($files));
            foreach ($files as $file) {
                $this->info('  - ' . basename($file));
            }
        }

        if (is_dir($publicPath)) {
            $files = glob($publicPath . '/*');
            $this->info('📋 Archivos en public: ' . count($files));
            foreach ($files as $file) {
                $this->info('  - ' . basename($file));
            }
        }

        // Verificar enlace simbólico
        $symlinkPath = public_path('storage');
        $this->info('🔗 Enlace simbólico: ' . (is_link($symlinkPath) ? '✅ Existe' : '❌ No existe'));

        // Verificar ruta web
        $this->info('🌐 Ruta web configurada: ' . url('storage/logos/app-logo.svg'));
    }
}
