<?php

namespace App\Helpers;

use App\Models\AppSetting;

class ViewHelper
{
    /**
     * Obtener logo seguro para vistas (sin usar facades)
     */
    public static function getLogoForView(): string
    {
        try {
            // Obtener configuración directamente de la base de datos
            $logoSetting = AppSetting::where('key', 'app_logo')->first();

            if ($logoSetting && $logoSetting->value) {
                $logoPath = $logoSetting->value;

                // Si es una URL completa, convertir a ruta relativa
                if (str_starts_with($logoPath, 'http')) {
                    $parsedUrl = parse_url($logoPath);
                    return $parsedUrl['path'] ?? $logoPath;
                }

                return $logoPath;
            }

        } catch (\Exception $e) {
            // Si hay error, usar logo por defecto
        }

        // Logo por defecto
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzMiIGhlaWdodD0iMzMiIHZpZXdCb3g9IjAgMCAzMyAzMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMzIiBoZWlnaHQ9IjMzIiByeD0iNCIgZmlsbD0iIzAwN2JmZiIvPgo8dGV4dCB4PSIxNi41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEwIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TDwvdGV4dD4KPC9zdmc+';
    }

    /**
     * Obtener nombre de la aplicación seguro para vistas
     */
    public static function getAppNameForView(): string
    {
        try {
            $nameSetting = AppSetting::where('key', 'app_name')->first();
            return $nameSetting ? $nameSetting->value : 'AdminLTE 3';
        } catch (\Exception $e) {
            return 'AdminLTE 3';
        }
    }
}

