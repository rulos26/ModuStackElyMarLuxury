<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FooterService
{
    /**
     * Obtener configuración del footer
     */
    public function getFooterConfig(): array
    {
        return Cache::remember('footer_config', 3600, function () {
            return [
                'left_text' => AppSetting::getValue('footer_left_text', 'Versión 1.0.0'),
                'right_text' => AppSetting::getValue('footer_right_text', 'Copyright © ' . date('Y') . ' <a href="#">Ely Mar Luxury</a>.'),
                'center_text' => AppSetting::getValue('footer_center_text', ''),
                'show_version' => AppSetting::getValue('footer_show_version', true),
                'show_copyright' => AppSetting::getValue('footer_show_copyright', true),
                'show_center_text' => AppSetting::getValue('footer_show_center_text', false),
                'company_name' => AppSetting::getValue('footer_company_name', 'Ely Mar Luxury'),
                'company_url' => AppSetting::getValue('footer_company_url', '#'),
                'version_text' => AppSetting::getValue('footer_version_text', '1.0.0'),
                'custom_html' => AppSetting::getValue('footer_custom_html', ''),
                'use_custom_html' => AppSetting::getValue('footer_use_custom_html', false),
            ];
        });
    }

    /**
     * Actualizar configuración del footer
     */
    public function updateFooterConfig(array $data): bool
    {
        try {
            $configs = [
                'footer_left_text' => $data['left_text'] ?? '',
                'footer_right_text' => $data['right_text'] ?? '',
                'footer_center_text' => $data['center_text'] ?? '',
                'footer_show_version' => $data['show_version'] ?? true,
                'footer_show_copyright' => $data['show_copyright'] ?? true,
                'footer_show_center_text' => $data['show_center_text'] ?? false,
                'footer_company_name' => $data['company_name'] ?? 'Ely Mar Luxury',
                'footer_company_url' => $data['company_url'] ?? '#',
                'footer_version_text' => $data['version_text'] ?? '1.0.0',
                'footer_custom_html' => $data['custom_html'] ?? '',
                'footer_use_custom_html' => $data['use_custom_html'] ?? false,
            ];

            foreach ($configs as $key => $value) {
                AppSetting::setValue($key, $value, $this->getSettingType($key));
            }

            // Limpiar caché
            Cache::forget('footer_config');

            Log::info('Configuración del footer actualizada');
            return true;

        } catch (\Exception $e) {
            Log::error('Error actualizando configuración del footer: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el tipo de configuración
     */
    private function getSettingType(string $key): string
    {
        $booleanKeys = [
            'footer_show_version',
            'footer_show_copyright',
            'footer_show_center_text',
            'footer_use_custom_html',
        ];

        return in_array($key, $booleanKeys) ? 'boolean' : 'string';
    }

    /**
     * Generar HTML del footer
     */
    public function generateFooterHtml(): string
    {
        $config = $this->getFooterConfig();

        if ($config['use_custom_html'] && !empty($config['custom_html'])) {
            return $config['custom_html'];
        }

        $html = '<footer class="main-footer">';

        if ($config['show_center_text'] && !empty($config['center_text'])) {
            $html .= '<div class="text-center">';
            $html .= '<strong>' . $config['center_text'] . '</strong>';
            $html .= '</div>';
        } else {
            // Layout tradicional con left y right
            $html .= '<div class="float-right d-none d-sm-inline">';
            if ($config['show_copyright']) {
                $companyUrl = $config['company_url'] !== '#' ? $config['company_url'] : '#';
                $html .= '<strong>Copyright &copy; ' . date('Y') . ' <a href="' . $companyUrl . '">' . $config['company_name'] . '</a>.</strong>';
                $html .= ' Todos los derechos reservados.';
            }
            $html .= '</div>';

            $html .= '<div class="float-left d-none d-sm-inline">';
            if ($config['show_version']) {
                $html .= '<strong>Versión</strong> ' . $config['version_text'];
            }
            if (!empty($config['left_text'])) {
                $html .= ' ' . $config['left_text'];
            }
            $html .= '</div>';
        }

        $html .= '<div class="clearfix"></div>';
        $html .= '</footer>';

        return $html;
    }

    /**
     * Resetear configuración del footer a valores por defecto
     */
    public function resetToDefaults(): bool
    {
        try {
            $defaults = [
                'footer_left_text' => '',
                'footer_right_text' => '',
                'footer_center_text' => '',
                'footer_show_version' => true,
                'footer_show_copyright' => true,
                'footer_show_center_text' => false,
                'footer_company_name' => 'Ely Mar Luxury',
                'footer_company_url' => '#',
                'footer_version_text' => '1.0.0',
                'footer_custom_html' => '',
                'footer_use_custom_html' => false,
            ];

            foreach ($defaults as $key => $value) {
                AppSetting::setValue($key, $value, $this->getSettingType($key));
            }

            Cache::forget('footer_config');

            Log::info('Configuración del footer restablecida a valores por defecto');
            return true;

        } catch (\Exception $e) {
            Log::error('Error restableciendo configuración del footer: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener información del footer actual
     */
    public function getFooterInfo(): array
    {
        $config = $this->getFooterConfig();

        return [
            'config' => $config,
            'html_preview' => $this->generateFooterHtml(),
            'has_custom_html' => !empty($config['custom_html']),
            'is_customized' => $this->isFooterCustomized($config),
        ];
    }

    /**
     * Verificar si el footer está personalizado
     */
    private function isFooterCustomized(array $config): bool
    {
        $defaults = [
            'left_text' => '',
            'right_text' => '',
            'center_text' => '',
            'show_version' => true,
            'show_copyright' => true,
            'show_center_text' => false,
            'company_name' => 'Ely Mar Luxury',
            'company_url' => '#',
            'version_text' => '1.0.0',
            'custom_html' => '',
            'use_custom_html' => false,
        ];

        foreach ($defaults as $key => $defaultValue) {
            if ($config[$key] !== $defaultValue) {
                return true;
            }
        }

        return false;
    }
}
