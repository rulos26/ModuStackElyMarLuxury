<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FaviconService
{
    /**
     * Directorio donde se almacenan los favicons
     */
    const FAVICON_DIRECTORY = 'favicons';


    /**
     * Subir archivo de favicon (imagen o .ico)
     */
    public static function uploadFavicon(UploadedFile $file): string
    {
        // Validar el archivo
        self::validateFavicon($file);

        // Crear directorio si no existe
        self::ensureFaviconDirectory();

        // Eliminar favicon anterior
        self::deleteOldFavicons();

        $fileExtension = strtolower($file->getClientOriginalExtension());

        // Si es un archivo .ico, moverlo directamente
        if ($fileExtension === 'ico') {
            $file->move(public_path(self::FAVICON_DIRECTORY), 'favicon.ico');
        } else {
            // Si es una imagen (JPG, PNG, GIF), convertirla a favicon.ico
            self::convertImageToFavicon($file);
        }

        return 'favicon.ico';
    }

    /**
     * Obtener información del favicon actual
     */
    public static function getFaviconInfo(): array
    {
        $faviconPath = public_path(self::FAVICON_DIRECTORY . '/favicon.ico');
        $info = [
            'exists' => file_exists($faviconPath),
            'path' => $faviconPath,
            'size' => 0,
            'modified' => null,
        ];

        if ($info['exists']) {
            $info['size'] = filesize($faviconPath);
            $info['modified'] = filemtime($faviconPath);
        }

        return $info;
    }

    /**
     * Eliminar favicon anterior
     */
    public static function deleteOldFavicons(): void
    {
        $faviconPath = public_path(self::FAVICON_DIRECTORY . '/favicon.ico');

        if (file_exists($faviconPath)) {
            unlink($faviconPath);
        }
    }

    /**
     * Crear directorio de favicons si no existe
     */
    public static function ensureFaviconDirectory(): void
    {
        $faviconDirectory = public_path(self::FAVICON_DIRECTORY);

        if (!is_dir($faviconDirectory)) {
            mkdir($faviconDirectory, 0755, true);
        }
    }

    /**
     * Validar el archivo de favicon
     */
    private static function validateFavicon(UploadedFile $file): void
    {
        // Validar extensión - aceptar JPG, PNG, GIF, ICO
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'ico'];
        $fileExtension = strtolower($file->getClientOriginalExtension());

        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new \Exception('El archivo debe ser una imagen. Formatos soportados: JPG, PNG, GIF, ICO');
        }

        // Validar tipo MIME
        $allowedMimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'image/x-icon', 'image/vnd.microsoft.icon', 'image/ico'
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('El archivo debe ser una imagen válida. Formatos soportados: JPG, PNG, GIF, ICO');
        }

        // Validar tamaño (5MB máximo)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxSize) {
            throw new \Exception('El archivo no puede ser mayor a 5MB');
        }

        // Validar dimensiones mínimas (180x180px) para archivos de imagen
        if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $imageInfo = getimagesize($file->getPathname());

            if (!$imageInfo) {
                throw new \Exception('No se pudo procesar la imagen. Verifique que sea un archivo de imagen válido.');
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($width < 180 || $height < 180) {
                throw new \Exception('La imagen debe tener un tamaño mínimo de 180x180 píxeles');
            }
        }
    }

    /**
     * Convertir imagen a favicon.ico usando funciones nativas de PHP
     */
    private static function convertImageToFavicon(UploadedFile $file): void
    {
        try {
            $sourcePath = $file->getPathname();
            $faviconPath = public_path(self::FAVICON_DIRECTORY . '/favicon.ico');

            // Obtener información de la imagen
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                throw new \Exception('No se pudo leer la información de la imagen');
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            // Crear imagen desde el archivo según el tipo
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                default:
                    throw new \Exception('Tipo de imagen no soportado para conversión');
            }

            if (!$sourceImage) {
                throw new \Exception('No se pudo crear la imagen desde el archivo');
            }

            // Crear imagen de destino 32x32
            $destImage = imagecreatetruecolor(32, 32);

            // Preservar transparencia para PNG
            if ($mimeType === 'image/png') {
                imagealphablending($destImage, false);
                imagesavealpha($destImage, true);
                $transparent = imagecolorallocatealpha($destImage, 0, 0, 0, 127);
                imagefill($destImage, 0, 0, $transparent);
            }

            // Redimensionar y centrar la imagen
            imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, 32, 32, $width, $height);

            // Guardar como PNG primero (más compatible)
            $tempPngPath = sys_get_temp_dir() . '/favicon_temp.png';
            imagepng($destImage, $tempPngPath);

            // Renombrar a .ico (funciona en la mayoría de navegadores)
            if (!rename($tempPngPath, $faviconPath)) {
                // Si falla el rename, copiar el archivo
                copy($tempPngPath, $faviconPath);
                unlink($tempPngPath);
            }

            // Limpiar memoria
            imagedestroy($sourceImage);
            imagedestroy($destImage);

        } catch (\Exception $e) {
            throw new \Exception('Error al convertir la imagen a favicon: ' . $e->getMessage());
        }
    }

}
