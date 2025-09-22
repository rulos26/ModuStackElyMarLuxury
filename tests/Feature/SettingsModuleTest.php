<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AppSetting;
use App\Helpers\AppConfigHelper;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /**
     * Test que verifica que las rutas de configuración requieren autenticación
     */
    public function test_settings_routes_require_authentication()
    {
        $response = $this->get('/admin/settings');
        $response->assertRedirect('/login');
    }

    /**
     * Test que verifica que las rutas de configuración requieren permisos
     */
    public function test_settings_routes_require_permissions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/admin/settings');
        $response->assertStatus(403);
    }

    /**
     * Test que verifica que el superadmin puede acceder a la configuración
     */
    public function test_superadmin_can_access_settings()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $response = $this->get('/admin/settings');
        $response->assertStatus(200);
    }

    /**
     * Test que verifica que se pueden actualizar las configuraciones
     */
    public function test_can_update_settings()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $settingsData = [
            'app_name' => 'Mi Aplicación',
            'app_icon' => 'fas fa-fw fa-home',
            'app_logo' => 'data:image/svg+xml;base64,test',
            'app_title_prefix' => 'Mi',
            'app_title_postfix' => 'App',
        ];

        $response = $this->put('/admin/settings', $settingsData);
        $response->assertRedirect('/admin/settings');

        // Verificar que se guardaron en la base de datos
        $this->assertDatabaseHas('app_settings', [
            'key' => 'app_name',
            'value' => 'Mi Aplicación'
        ]);
    }

    /**
     * Test que verifica que se puede restaurar la configuración por defecto
     */
    public function test_can_reset_settings()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $response = $this->post('/admin/settings/reset');
        $response->assertRedirect('/admin/settings');

        // Verificar que se restauraron los valores por defecto
        $this->assertDatabaseHas('app_settings', [
            'key' => 'app_name',
            'value' => 'AdminLTE 3'
        ]);
    }

    /**
     * Test que verifica la validación de iconos
     */
    public function test_icon_validation()
    {
        $user = User::where('email', 'root@admin.com')->first();
        $this->actingAs($user);

        $settingsData = [
            'app_name' => 'Mi Aplicación',
            'app_icon' => 'invalid-icon',
            'app_logo' => 'data:image/svg+xml;base64,test',
            'app_title_prefix' => '',
            'app_title_postfix' => '',
        ];

        $response = $this->put('/admin/settings', $settingsData);
        $response->assertSessionHasErrors(['app_icon']);
    }

    /**
     * Test que verifica el modelo AppSetting
     */
    public function test_app_setting_model()
    {
        // Test getValue
        $value = AppSetting::getValue('app_name');
        $this->assertEquals('AdminLTE 3', $value);

        // Test setValue
        AppSetting::setValue('test_key', 'test_value');
        $this->assertDatabaseHas('app_settings', [
            'key' => 'test_key',
            'value' => 'test_value'
        ]);

        // Test getAllAsArray
        $allSettings = AppSetting::getAllAsArray();
        $this->assertIsArray($allSettings);
        $this->assertArrayHasKey('app_name', $allSettings);
    }

    /**
     * Test que verifica el helper AppConfigHelper
     */
    public function test_app_config_helper()
    {
        // Test getAppName
        $appName = AppConfigHelper::getAppName();
        $this->assertEquals('AdminLTE 3', $appName);

        // Test getAppIcon
        $appIcon = AppConfigHelper::getAppIcon();
        $this->assertEquals('fas fa-fw fa-tachometer-alt', $appIcon);

        // Test getAppLogo
        $appLogo = AppConfigHelper::getAppLogo();
        $this->assertStringContainsString('data:image/svg+xml;base64,', $appLogo);
    }

    /**
     * Test que verifica que las vistas usan el extends correcto
     */
    public function test_settings_views_use_correct_extends()
    {
        $settingsViewPath = resource_path('views/admin/settings/index.blade.php');
        $this->assertTrue(file_exists($settingsViewPath), 'La vista de configuración no existe');

        $content = file_get_contents($settingsViewPath);
        $this->assertStringContainsString("@extends('adminlte::page')", $content,
            'La vista de configuración no usa @extends(\'adminlte::page\')');
    }

    /**
     * Test que verifica la validación de iconos válidos
     */
    public function test_valid_icons()
    {
        $validIcons = [
            'fas fa-fw fa-tachometer-alt',
            'fas fa-fw fa-users',
            'fas fa-fw fa-cog',
            'fas fa-fw fa-home',
        ];

        foreach ($validIcons as $icon) {
            $this->assertTrue(AppSetting::isValidIcon($icon), "El icono {$icon} debería ser válido");
        }

        $invalidIcons = [
            'invalid-icon',
            'fas fa-invalid',
            'not-an-icon',
        ];

        foreach ($invalidIcons as $icon) {
            $this->assertFalse(AppSetting::isValidIcon($icon), "El icono {$icon} no debería ser válido");
        }
    }
}
