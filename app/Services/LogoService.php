<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogoService
{
    /**
     * Directorio donde se almacenan los logos
     */
    const LOGO_DIRECTORY = 'logos';

    /**
     * Nombre fijo del archivo de logo
     */
    const LOGO_FILENAME = 'app-logo';

    /**
     * Subir y reemplazar el logo de la aplicación
     */
    public static function uploadLogo(UploadedFile $file): string
    {
        // Validar el archivo
        self::validateLogo($file);

        // Obtener la extensión del archivo
        $extension = $file->getClientOriginalExtension();

        // Crear el nombre del archivo con extensión
        $filename = self::LOGO_FILENAME . '.' . $extension;

        // Ruta completa del archivo
        $filePath = self::LOGO_DIRECTORY . '/' . $filename;

        // Eliminar logo anterior si existe
        self::deleteOldLogo();

        // Subir el nuevo archivo
        $uploadedPath = $file->storeAs(self::LOGO_DIRECTORY, $filename, 'public');

        if (!$uploadedPath) {
            throw new \Exception('Error al subir el archivo del logo');
        }

        // Copiar también al directorio público para servidores compartidos
        self::copyToPublicDirectory($filename);

        // Retornar la ruta relativa (sin dominio)
        return '/storage/' . $uploadedPath;
    }

    /**
     * Obtener la ruta del logo actual
     */
    public static function getCurrentLogoPath(): ?string
    {
        $logoDirectory = storage_path('app/public/' . self::LOGO_DIRECTORY);

        if (!is_dir($logoDirectory)) {
            return null;
        }

        // Buscar archivos que empiecen con app-logo
        $files = glob($logoDirectory . '/app-logo.*');

        if (empty($files)) {
            return null;
        }

        // Retornar la ruta relativa del primer archivo encontrado
        $filename = basename($files[0]);
        return '/storage/' . self::LOGO_DIRECTORY . '/' . $filename;
    }

    /**
     * Eliminar logo anterior
     */
    public static function deleteOldLogo(): void
    {
        // Eliminar de storage
        $storageDirectory = storage_path('app/public/' . self::LOGO_DIRECTORY);
        if (is_dir($storageDirectory)) {
            $files = glob($storageDirectory . '/app-logo.*');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        // Eliminar de directorio público
        $publicDirectory = public_path(self::LOGO_DIRECTORY);
        if (is_dir($publicDirectory)) {
            $files = glob($publicDirectory . '/app-logo.*');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Validar el archivo de logo
     */
    private static function validateLogo(UploadedFile $file): void
    {
        // Validar tipo MIME
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('El archivo debe ser una imagen válida (JPEG, PNG, GIF, SVG)');
        }

        // Validar tamaño (2MB máximo)
        if ($file->getSize() > 2048 * 1024) {
            throw new \Exception('El archivo no puede ser mayor a 2MB');
        }

        // Validar dimensiones para imágenes raster
        if (in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'])) {
            $imageInfo = getimagesize($file->getPathname());

            if (!$imageInfo) {
                throw new \Exception('El archivo no es una imagen válida');
            }

            // Validar dimensiones máximas (opcional)
            if ($imageInfo[0] > 2000 || $imageInfo[1] > 2000) {
                throw new \Exception('Las dimensiones de la imagen no pueden ser mayores a 2000x2000 píxeles');
            }
        }
    }

    /**
     * Crear directorio de logos si no existe
     */
    public static function ensureLogoDirectory(): void
    {
        $logoDirectory = storage_path('app/public/' . self::LOGO_DIRECTORY);

        if (!is_dir($logoDirectory)) {
            mkdir($logoDirectory, 0755, true);
        }
    }

    /**
     * Obtener información del logo actual
     */
    public static function getLogoInfo(): array
    {
        $logoPath = self::getCurrentLogoPath();

        if (!$logoPath) {
            return [
                'exists' => false,
                'path' => null,
                'size' => null,
                'modified' => null
            ];
        }

        $filePath = storage_path('app/public/' . self::LOGO_DIRECTORY . '/' . basename($logoPath));

        return [
            'exists' => true,
            'path' => $logoPath,
            'size' => file_exists($filePath) ? filesize($filePath) : null,
            'modified' => file_exists($filePath) ? filemtime($filePath) : null
        ];
    }

    /**
     * Copiar archivo al directorio público para servidores compartidos
     */
    private static function copyToPublicDirectory(string $filename): void
    {
        $storagePath = storage_path('app/public/' . self::LOGO_DIRECTORY . '/' . $filename);
        $publicPath = public_path(self::LOGO_DIRECTORY . '/' . $filename);

        // Crear directorio público si no existe
        $publicDirectory = public_path(self::LOGO_DIRECTORY);
        if (!is_dir($publicDirectory)) {
            mkdir($publicDirectory, 0755, true);
        }

        // Copiar archivo
        if (file_exists($storagePath)) {
            copy($storagePath, $publicPath);
        }
    }
}
