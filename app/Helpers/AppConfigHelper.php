<?php

namespace App\Helpers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class AppConfigHelper
{
    /**
     * Obtener configuración de la aplicación con caché
     */
    public static function getAppConfig()
    {
        return Cache::remember('app_settings', 3600, function () {
            return AppSetting::getAllAsArray();
        });
    }

    /**
     * Obtener nombre de la aplicación
     */
    public static function getAppName()
    {
        $config = self::getAppConfig();
        return $config['app_name'] ?? 'AdminLTE 3';
    }

    /**
     * Obtener logo de la aplicación
     */
    public static function getAppLogo()
    {
        $config = self::getAppConfig();
        $logoPath = $config['app_logo'] ?? null;

        // Si es una ruta de archivo, convertir a ruta relativa
        if ($logoPath && !str_starts_with($logoPath, 'data:')) {
            // Si es una URL completa, extraer solo la ruta relativa
            if (str_starts_with($logoPath, 'http')) {
                $parsedUrl = parse_url($logoPath);
                return $parsedUrl['path'] ?? $logoPath;
            }
            return $logoPath;
        }

        // Si es data URL o no existe, usar logo por defecto
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzMiIGhlaWdodD0iMzMiIHZpZXdCb3g9IjAgMCAzMyAzMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMzIiBoZWlnaHQ9IjMzIiByeD0iNCIgZmlsbD0iIzAwN2JmZiIvPgo8dGV4dCB4PSIxNi41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEwIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TDwvdGV4dD4KPC9zdmc+';
    }

    /**
     * Obtener icono de la aplicación
     */
    public static function getAppIcon()
    {
        $config = self::getAppConfig();
        return $config['app_icon'] ?? 'fas fa-fw fa-tachometer-alt';
    }

    /**
     * Obtener prefijo del título
     */
    public static function getTitlePrefix()
    {
        $config = self::getAppConfig();
        return $config['app_title_prefix'] ?? '';
    }

    /**
     * Obtener postfijo del título
     */
    public static function getTitlePostfix()
    {
        $config = self::getAppConfig();
        return $config['app_title_postfix'] ?? '';
    }

    /**
     * Limpiar caché de configuración
     */
    public static function clearCache()
    {
        Cache::forget('app_settings');
    }
}
