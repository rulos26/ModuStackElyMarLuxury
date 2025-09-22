<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Obtener un valor de configuración por su clave
     */
    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        // Convertir el valor según el tipo
        switch ($setting->type) {
            case 'boolean':
                return (bool) $setting->value;
            case 'integer':
                return (int) $setting->value;
            case 'json':
                return json_decode($setting->value, true);
            default:
                return $setting->value;
        }
    }

    /**
     * Establecer un valor de configuración
     */
    public static function setValue($key, $value, $type = 'string', $description = null)
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            $setting->update([
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]);
        } else {
            static::create([
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]);
        }
    }

    /**
     * Obtener todas las configuraciones como array asociativo
     */
    public static function getAllAsArray()
    {
        $settings = static::all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = self::getValue($setting->key);
        }

        return $result;
    }

    /**
     * Validar si un icono es válido (FontAwesome)
     */
    public static function isValidIcon($icon)
    {
        // Lista de iconos FontAwesome válidos
        $validIcons = [
            'fas fa-fw fa-tachometer-alt',
            'fas fa-fw fa-users',
            'fas fa-fw fa-user-tag',
            'fas fa-fw fa-key',
            'fas fa-fw fa-cog',
            'fas fa-fw fa-home',
            'fas fa-fw fa-chart-bar',
            'fas fa-fw fa-file',
            'fas fa-fw fa-envelope',
            'fas fa-fw fa-bell',
            'fas fa-fw fa-search',
            'fas fa-fw fa-plus',
            'fas fa-fw fa-edit',
            'fas fa-fw fa-trash',
            'fas fa-fw fa-eye',
            'fas fa-fw fa-save',
            'fas fa-fw fa-arrow-left',
            'fas fa-fw fa-arrow-right',
            'fas fa-fw fa-chevron-down',
            'fas fa-fw fa-chevron-up',
            'fas fa-fw fa-bars',
            'fas fa-fw fa-times',
            'fas fa-fw fa-check',
            'fas fa-fw fa-exclamation',
            'fas fa-fw fa-info',
            'fas fa-fw fa-warning',
            'fas fa-fw fa-question',
            'fas fa-fw fa-star',
            'fas fa-fw fa-heart',
            'fas fa-fw fa-thumbs-up',
            'fas fa-fw fa-thumbs-down',
        ];

        return in_array($icon, $validIcons);
    }
}
