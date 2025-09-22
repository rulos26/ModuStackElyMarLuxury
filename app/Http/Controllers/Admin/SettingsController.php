<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-settings');
    }

    /**
     * Display the settings page
     */
    public function index()
    {
        $settings = AppSetting::all();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update the settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
            'app_logo' => 'nullable|string',
            'app_icon' => 'required|string|max:255',
            'app_title_prefix' => 'nullable|string|max:255',
            'app_title_postfix' => 'nullable|string|max:255',
        ]);

        // Validar que el icono sea válido
        if (!AppSetting::isValidIcon($request->app_icon)) {
            return redirect()->back()
                ->withErrors(['app_icon' => 'El icono seleccionado no es válido.'])
                ->withInput();
        }

        // Actualizar configuraciones
        AppSetting::setValue('app_name', $request->app_name, 'string', 'Nombre de la aplicación');
        AppSetting::setValue('app_logo', $request->app_logo, 'string', 'Logo de la aplicación');
        AppSetting::setValue('app_icon', $request->app_icon, 'string', 'Icono de la aplicación');
        AppSetting::setValue('app_title_prefix', $request->app_title_prefix, 'string', 'Prefijo del título');
        AppSetting::setValue('app_title_postfix', $request->app_title_postfix, 'string', 'Postfijo del título');

        // Limpiar caché
        Cache::forget('app_settings');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Configuración actualizada exitosamente.');
    }

    /**
     * Reset settings to default
     */
    public function reset()
    {
        // Restaurar configuraciones por defecto
        AppSetting::setValue('app_name', 'AdminLTE 3', 'string', 'Nombre de la aplicación');
        AppSetting::setValue('app_logo', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzMiIGhlaWdodD0iMzMiIHZpZXdCb3g9IjAgMCAzMyAzMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMzIiBoZWlnaHQ9IjMzIiByeD0iNCIgZmlsbD0iIzAwN2JmZiIvPgo8dGV4dCB4PSIxNi41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEwIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TDwvdGV4dD4KPC9zdmc+', 'string', 'Logo de la aplicación');
        AppSetting::setValue('app_icon', 'fas fa-fw fa-tachometer-alt', 'string', 'Icono de la aplicación');
        AppSetting::setValue('app_title_prefix', '', 'string', 'Prefijo del título');
        AppSetting::setValue('app_title_postfix', '', 'string', 'Postfijo del título');

        // Limpiar caché
        Cache::forget('app_settings');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Configuración restaurada a los valores por defecto.');
    }

    /**
     * Get available icons for the dropdown
     */
    public function getIcons()
    {
        $icons = [
            'fas fa-fw fa-tachometer-alt' => 'Dashboard',
            'fas fa-fw fa-users' => 'Usuarios',
            'fas fa-fw fa-user-tag' => 'Roles',
            'fas fa-fw fa-key' => 'Permisos',
            'fas fa-fw fa-cog' => 'Configuración',
            'fas fa-fw fa-home' => 'Inicio',
            'fas fa-fw fa-chart-bar' => 'Gráficos',
            'fas fa-fw fa-file' => 'Archivos',
            'fas fa-fw fa-envelope' => 'Mensajes',
            'fas fa-fw fa-bell' => 'Notificaciones',
            'fas fa-fw fa-search' => 'Búsqueda',
            'fas fa-fw fa-plus' => 'Agregar',
            'fas fa-fw fa-edit' => 'Editar',
            'fas fa-fw fa-trash' => 'Eliminar',
            'fas fa-fw fa-eye' => 'Ver',
            'fas fa-fw fa-save' => 'Guardar',
            'fas fa-fw fa-arrow-left' => 'Izquierda',
            'fas fa-fw fa-arrow-right' => 'Derecha',
            'fas fa-fw fa-chevron-down' => 'Abajo',
            'fas fa-fw fa-chevron-up' => 'Arriba',
            'fas fa-fw fa-bars' => 'Menú',
            'fas fa-fw fa-times' => 'Cerrar',
            'fas fa-fw fa-check' => 'Verificar',
            'fas fa-fw fa-exclamation' => 'Exclamación',
            'fas fa-fw fa-info' => 'Información',
            'fas fa-fw fa-warning' => 'Advertencia',
            'fas fa-fw fa-question' => 'Pregunta',
            'fas fa-fw fa-star' => 'Estrella',
            'fas fa-fw fa-heart' => 'Corazón',
            'fas fa-fw fa-thumbs-up' => 'Pulgar arriba',
            'fas fa-fw fa-thumbs-down' => 'Pulgar abajo',
        ];

        return response()->json($icons);
    }
}
