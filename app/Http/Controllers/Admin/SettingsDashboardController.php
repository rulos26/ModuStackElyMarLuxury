<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppSetting;
use App\Helpers\AppConfigHelper;

class SettingsDashboardController extends Controller
{
    /**
     * Display the settings dashboard
     */
    public function index()
    {
        $this->authorize('manage-settings');
        
        $settings = AppSetting::all()->keyBy('key');
        
        return view('admin.settings.dashboard', compact('settings'));
    }

    /**
     * Display a specific settings section
     */
    public function section($section)
    {
        $this->authorize('manage-settings');
        
        // Validar sección
        $validSections = ['general', 'appearance', 'security', 'notifications', 'advanced'];
        
        if (!in_array($section, $validSections)) {
            abort(404, 'Sección no encontrada');
        }
        
        $settings = AppSetting::all()->keyBy('key');
        
        return view("admin.settings.sections.{$section}", compact('settings'));
    }

    /**
     * Update settings for a specific section
     */
    public function updateSection(Request $request, $section)
    {
        $this->authorize('manage-settings');
        
        $validSections = ['general', 'appearance', 'security', 'notifications', 'advanced'];
        
        if (!in_array($section, $validSections)) {
            abort(404, 'Sección no encontrada');
        }
        
        // Validar según la sección
        $validationRules = $this->getValidationRules($section);
        
        $request->validate($validationRules);
        
        // Actualizar configuraciones según la sección
        $this->updateSettingsBySection($request, $section);
        
        // Limpiar caché
        AppConfigHelper::clearCache();
        
        return redirect()
            ->route('admin.settings.section', $section)
            ->with('success', "Configuración de {$this->getSectionName($section)} actualizada exitosamente.");
    }

    /**
     * Get validation rules for each section
     */
    private function getValidationRules($section)
    {
        $rules = [
            'general' => [
                'app_name' => 'required|string|max:255',
                'app_description' => 'nullable|string|max:500',
                'app_version' => 'nullable|string|max:50',
                'app_author' => 'nullable|string|max:255',
                'app_url' => 'nullable|url|max:255',
            ],
            'appearance' => [
                'app_logo' => 'nullable|string',
                'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'app_icon' => 'required|string|max:255',
                'app_title_prefix' => 'nullable|string|max:255',
                'app_title_postfix' => 'nullable|string|max:255',
                'theme_color' => 'nullable|string|max:7',
                'sidebar_style' => 'nullable|in:light,dark',
            ],
            'security' => [
                'session_timeout' => 'nullable|integer|min:5|max:480',
                'max_login_attempts' => 'nullable|integer|min:3|max:10',
                'password_min_length' => 'nullable|integer|min:6|max:20',
                'require_2fa' => 'nullable|boolean',
                'allow_registration' => 'nullable|boolean',
            ],
            'notifications' => [
                'email_notifications' => 'nullable|boolean',
                'push_notifications' => 'nullable|boolean',
                'notification_sound' => 'nullable|boolean',
                'email_smtp_host' => 'nullable|string|max:255',
                'email_smtp_port' => 'nullable|integer|min:1|max:65535',
                'email_smtp_user' => 'nullable|string|max:255',
                'email_smtp_encryption' => 'nullable|in:tls,ssl',
            ],
            'advanced' => [
                'debug_mode' => 'nullable|boolean',
                'maintenance_mode' => 'nullable|boolean',
                'cache_driver' => 'nullable|in:file,redis,memcached',
                'queue_driver' => 'nullable|in:sync,database,redis',
                'backup_frequency' => 'nullable|in:daily,weekly,monthly',
                'log_level' => 'nullable|in:debug,info,warning,error',
            ]
        ];

        return $rules[$section] ?? [];
    }

    /**
     * Update settings based on section
     */
    private function updateSettingsBySection(Request $request, $section)
    {
        switch ($section) {
            case 'general':
                $this->updateGeneralSettings($request);
                break;
            case 'appearance':
                $this->updateAppearanceSettings($request);
                break;
            case 'security':
                $this->updateSecuritySettings($request);
                break;
            case 'notifications':
                $this->updateNotificationSettings($request);
                break;
            case 'advanced':
                $this->updateAdvancedSettings($request);
                break;
        }
    }

    /**
     * Update general settings
     */
    private function updateGeneralSettings(Request $request)
    {
        AppSetting::setValue('app_name', $request->app_name, 'string', 'Nombre de la aplicación');
        AppSetting::setValue('app_description', $request->app_description, 'string', 'Descripción de la aplicación');
        AppSetting::setValue('app_version', $request->app_version, 'string', 'Versión de la aplicación');
        AppSetting::setValue('app_author', $request->app_author, 'string', 'Autor de la aplicación');
        AppSetting::setValue('app_url', $request->app_url, 'string', 'URL de la aplicación');
    }

    /**
     * Update appearance settings
     */
    private function updateAppearanceSettings(Request $request)
    {
        // Manejar logo
        $logoValue = $request->app_logo;
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $imageData = file_get_contents($file->getPathname());
            $logoValue = 'data:' . $file->getMimeType() . ';base64,' . base64_encode($imageData);
        }

        AppSetting::setValue('app_logo', $logoValue, 'string', 'Logo de la aplicación');
        AppSetting::setValue('app_icon', $request->app_icon, 'string', 'Icono de la aplicación');
        AppSetting::setValue('app_title_prefix', $request->app_title_prefix, 'string', 'Prefijo del título');
        AppSetting::setValue('app_title_postfix', $request->app_title_postfix, 'string', 'Sufijo del título');
        AppSetting::setValue('theme_color', $request->theme_color, 'string', 'Color del tema');
        AppSetting::setValue('sidebar_style', $request->sidebar_style, 'string', 'Estilo del sidebar');
    }

    /**
     * Update security settings
     */
    private function updateSecuritySettings(Request $request)
    {
        AppSetting::setValue('session_timeout', $request->session_timeout, 'integer', 'Tiempo de sesión (minutos)');
        AppSetting::setValue('max_login_attempts', $request->max_login_attempts, 'integer', 'Máximo intentos de login');
        AppSetting::setValue('password_min_length', $request->password_min_length, 'integer', 'Longitud mínima de contraseña');
        AppSetting::setValue('require_2fa', $request->boolean('require_2fa'), 'boolean', 'Requerir autenticación de dos factores');
        AppSetting::setValue('allow_registration', $request->boolean('allow_registration'), 'boolean', 'Permitir registro');
    }

    /**
     * Update notification settings
     */
    private function updateNotificationSettings(Request $request)
    {
        AppSetting::setValue('email_notifications', $request->boolean('email_notifications'), 'boolean', 'Notificaciones por email');
        AppSetting::setValue('push_notifications', $request->boolean('push_notifications'), 'boolean', 'Notificaciones push');
        AppSetting::setValue('notification_sound', $request->boolean('notification_sound'), 'boolean', 'Sonido de notificaciones');
        AppSetting::setValue('email_smtp_host', $request->email_smtp_host, 'string', 'Servidor SMTP');
        AppSetting::setValue('email_smtp_port', $request->email_smtp_port, 'integer', 'Puerto SMTP');
        AppSetting::setValue('email_smtp_user', $request->email_smtp_user, 'string', 'Usuario SMTP');
        AppSetting::setValue('email_smtp_encryption', $request->email_smtp_encryption, 'string', 'Encriptación SMTP');
    }

    /**
     * Update advanced settings
     */
    private function updateAdvancedSettings(Request $request)
    {
        AppSetting::setValue('debug_mode', $request->boolean('debug_mode'), 'boolean', 'Modo debug');
        AppSetting::setValue('maintenance_mode', $request->boolean('maintenance_mode'), 'boolean', 'Modo mantenimiento');
        AppSetting::setValue('cache_driver', $request->cache_driver, 'string', 'Driver de caché');
        AppSetting::setValue('queue_driver', $request->queue_driver, 'string', 'Driver de colas');
        AppSetting::setValue('backup_frequency', $request->backup_frequency, 'string', 'Frecuencia de respaldo');
        AppSetting::setValue('log_level', $request->log_level, 'string', 'Nivel de logs');
    }

    /**
     * Get section display name
     */
    private function getSectionName($section)
    {
        $names = [
            'general' => 'General',
            'appearance' => 'Apariencia',
            'security' => 'Seguridad',
            'notifications' => 'Notificaciones',
            'advanced' => 'Avanzado'
        ];

        return $names[$section] ?? ucfirst($section);
    }
}
