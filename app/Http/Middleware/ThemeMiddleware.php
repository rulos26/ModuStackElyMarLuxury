<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AppSetting;

class ThemeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo aplicar a respuestas HTML
        if ($response->headers->get('Content-Type') &&
            str_contains($response->headers->get('Content-Type'), 'text/html')) {

            $content = $response->getContent();

            // Obtener configuraciones de tema
            $themeColor = AppSetting::getValue('theme_color', '#007bff');
            $sidebarStyle = AppSetting::getValue('sidebar_style', 'light');

            // Generar CSS dinámico
            $dynamicCSS = $this->generateDynamicCSS($themeColor, $sidebarStyle);

            // Inyectar CSS en el head
            $content = $this->injectCSS($content, $dynamicCSS);

            $response->setContent($content);
        }

        return $response;
    }

    /**
     * Generate dynamic CSS based on theme settings
     */
    private function generateDynamicCSS($themeColor, $sidebarStyle)
    {
        $css = "
        <style id='dynamic-theme-css'>
        :root {
            --primary-color: {$themeColor};
            --primary-rgb: " . $this->hexToRgb($themeColor) . ";
        }

        .main-header {
            background-color: {$themeColor} !important;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        .btn-primary {
            background-color: {$themeColor} !important;
            border-color: {$themeColor} !important;
        }

        .btn-primary:hover {
            background-color: " . $this->darkenColor($themeColor, 10) . " !important;
            border-color: " . $this->darkenColor($themeColor, 10) . " !important;
        }

        .card-primary .card-header {
            background-color: {$themeColor} !important;
        }

        .nav-pills .nav-link.active {
            background-color: {$themeColor} !important;
        }

        .sidebar {
            background-color: " . ($sidebarStyle === 'dark' ? '#343a40' : '#f8f9fa') . " !important;
        }

        .sidebar .nav-link {
            color: " . ($sidebarStyle === 'dark' ? '#ffffff' : '#333333') . " !important;
        }

        .sidebar .nav-link:hover {
            background-color: " . ($sidebarStyle === 'dark' ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)') . " !important;
        }

        .sidebar .nav-link.active {
            background-color: {$themeColor} !important;
            color: #ffffff !important;
        }
        </style>";

        return $css;
    }

    /**
     * Convert hex color to RGB
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r}, {$g}, {$b}";
    }

    /**
     * Darken a color by percentage
     */
    private function darkenColor($hex, $percent)
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));

        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Inject CSS into HTML content
     */
    private function injectCSS($content, $css)
    {
        // Buscar el tag </head> y reemplazarlo
        if (strpos($content, '</head>') !== false) {
            $content = str_replace('</head>', $css . '</head>', $content);
        } else {
            // Si no hay </head>, buscar <body> y agregar antes
            if (strpos($content, '<body') !== false) {
                $content = str_replace('<body', $css . '<body', $content);
            }
        }

        return $content;
    }
}



