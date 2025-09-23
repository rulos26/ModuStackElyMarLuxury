<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AppSetting;
use App\Helpers\AppConfigHelper;
use App\Models\User;

class ConfigurationBugFixesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar seeders para crear permisos
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Usar el usuario root que ya tiene todos los permisos
        $this->user = User::where('email', 'root@admin.com')->first();
    }

    /**
     * Test que verifica que el nombre de la aplicación se actualiza dinámicamente
     */
    public function test_app_name_updates_dynamically()
    {
        // Configurar nombre de prueba
        AppSetting::setValue('app_name', 'Test App Name', 'string', 'Nombre de prueba');

        // Verificar que AppConfigHelper devuelve el nombre correcto
        $this->assertEquals('Test App Name', AppConfigHelper::getAppName());

        // Limpiar caché
        AppConfigHelper::clearCache();

        // Verificar nuevamente después de limpiar caché
        $this->assertEquals('Test App Name', AppConfigHelper::getAppName());
    }

    /**
     * Test que verifica que el logo se actualiza dinámicamente
     */
    public function test_app_logo_updates_dynamically()
    {
        $testLogo = 'data:image/png;base64,test123';

        // Configurar logo de prueba
        AppSetting::setValue('app_logo', $testLogo, 'string', 'Logo de prueba');

        // Verificar que AppConfigHelper devuelve el logo correcto
        $this->assertEquals($testLogo, AppConfigHelper::getAppLogo());

        // Limpiar caché
        AppConfigHelper::clearCache();

        // Verificar nuevamente después de limpiar caché
        $this->assertEquals($testLogo, AppConfigHelper::getAppLogo());
    }

    /**
     * Test que verifica que el icono se actualiza dinámicamente
     */
    public function test_app_icon_updates_dynamically()
    {
        $testIcon = 'fas fa-fw fa-star';

        // Configurar icono de prueba
        AppSetting::setValue('app_icon', $testIcon, 'string', 'Icono de prueba');

        // Verificar que AppConfigHelper devuelve el icono correcto
        $this->assertEquals($testIcon, AppConfigHelper::getAppIcon());

        // Limpiar caché
        AppConfigHelper::clearCache();

        // Verificar nuevamente después de limpiar caché
        $this->assertEquals($testIcon, AppConfigHelper::getAppIcon());
    }

    /**
     * Test que verifica que los prefijos y postfijos del título funcionan
     */
    public function test_title_prefix_and_postfix_work()
    {
        $prefix = 'Test Prefix';
        $postfix = 'Test Postfix';

        // Configurar prefijo y postfijo
        AppSetting::setValue('app_title_prefix', $prefix, 'string', 'Prefijo de prueba');
        AppSetting::setValue('app_title_postfix', $postfix, 'string', 'Postfijo de prueba');

        // Verificar que AppConfigHelper devuelve los valores correctos
        $this->assertEquals($prefix, AppConfigHelper::getTitlePrefix());
        $this->assertEquals($postfix, AppConfigHelper::getTitlePostfix());
    }

    /**
     * Test que verifica que la página de configuración carga correctamente
     */
    public function test_settings_page_loads_correctly()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.index');
    }

    /**
     * Test que verifica que se puede actualizar la configuración
     */
    public function test_can_update_settings()
    {
        $data = [
            'app_name' => 'Nueva App',
            'app_logo' => 'data:image/png;base64,newlogo123',
            'app_icon' => 'fas fa-fw fa-star',
            'app_title_prefix' => 'Nuevo Prefijo',
            'app_title_postfix' => 'Nuevo Postfijo',
        ];

        $response = $this->actingAs($this->user)
            ->put('/admin/settings', $data);

        $response->assertRedirect('/admin/settings');
        $response->assertSessionHas('success');

        // Verificar que los valores se guardaron
        $this->assertEquals('Nueva App', AppSetting::getValue('app_name'));
        $this->assertEquals('data:image/png;base64,newlogo123', AppSetting::getValue('app_logo'));
        $this->assertEquals('fas fa-fw fa-star', AppSetting::getValue('app_icon'));
    }

    /**
     * Test que verifica que se puede resetear la configuración
     */
    public function test_can_reset_settings()
    {
        // Configurar valores personalizados
        AppSetting::setValue('app_name', 'App Personalizada', 'string', 'Test');

        $response = $this->actingAs($this->user)
            ->post('/admin/settings/reset');

        $response->assertRedirect('/admin/settings');
        $response->assertSessionHas('success');

        // Verificar que se restauraron los valores por defecto
        $this->assertEquals('AdminLTE 3', AppSetting::getValue('app_name'));
    }

    /**
     * Test que verifica que las vistas usan @extends('adminlte::page')
     */
    public function test_views_use_correct_extends()
    {
        $settingsView = file_get_contents(resource_path('views/admin/settings/index.blade.php'));
        $this->assertStringContainsString("@extends('adminlte::page')", $settingsView);

        $homeView = file_get_contents(resource_path('views/home.blade.php'));
        $this->assertStringContainsString("@extends('adminlte::page')", $homeView);
    }

    /**
     * Test que verifica que el ViewServiceProvider está registrado
     */
    public function test_view_service_provider_is_registered()
    {
        $providers = include base_path('bootstrap/providers.php');
        $this->assertContains('App\\Providers\\ViewServiceProvider', $providers);
    }

    /**
     * Test que verifica que el caché se limpia correctamente
     */
    public function test_cache_clearing_works()
    {
        // Configurar un valor
        AppSetting::setValue('app_name', 'Cached Name', 'string', 'Test');

        // Verificar que está en caché
        $this->assertEquals('Cached Name', AppConfigHelper::getAppName());

        // Limpiar caché
        AppConfigHelper::clearCache();

        // Cambiar valor en base de datos
        AppSetting::setValue('app_name', 'New Name', 'string', 'Test');

        // Verificar que se obtiene el nuevo valor (caché limpio)
        $this->assertEquals('New Name', AppConfigHelper::getAppName());
    }
}
