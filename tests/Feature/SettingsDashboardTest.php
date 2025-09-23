<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\AppSetting;
use App\Models\User;
use App\Helpers\AppConfigHelper;
use Illuminate\Support\Facades\Cache;

class SettingsDashboardTest extends TestCase
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
     * Test que verifica que el dashboard de configuración se carga correctamente
     */
    public function test_settings_dashboard_loads_correctly()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.dashboard');
        $response->assertSee('Dashboard de Configuración');
        $response->assertSee('Secciones');
    }

    /**
     * Test que verifica que las secciones del dashboard son accesibles
     */
    public function test_settings_sections_are_accessible()
    {
        $sections = ['general', 'appearance', 'security', 'notifications', 'advanced'];

        foreach ($sections as $section) {
            $response = $this->actingAs($this->user)
                ->get("/admin/settings/section/{$section}");

            $response->assertStatus(200);
            $response->assertViewIs("admin.settings.sections.{$section}");
        }
    }

    /**
     * Test que verifica que sección inválida retorna 404
     */
    public function test_invalid_section_returns_404()
    {
        $response = $this->actingAs($this->user)
            ->get('/admin/settings/section/invalid-section');

        $response->assertStatus(404);
    }

    /**
     * Test que verifica que se pueden actualizar configuraciones generales
     */
    public function test_can_update_general_settings()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('admin.settings.update.section', 'general'), [
            'app_name' => 'Mi Nueva Aplicación',
            'app_version' => '2.0.0',
            'app_description' => 'Descripción actualizada',
            'app_author' => 'Nuevo Autor',
            'app_url' => 'https://nuevaapp.com',
        ]);

        $response->assertRedirect(route('admin.settings.section', 'general'));
        $response->assertSessionHas('success');

        $this->assertEquals('Mi Nueva Aplicación', AppSetting::getValue('app_name'));
        $this->assertEquals('2.0.0', AppSetting::getValue('app_version'));
        $this->assertEquals('Descripción actualizada', AppSetting::getValue('app_description'));
    }

    /**
     * Test que verifica que se pueden actualizar configuraciones de apariencia
     */
    public function test_can_update_appearance_settings()
    {
        $this->actingAs($this->user);

        $newLogo = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzMiIGhlaWdodD0iMzMiIHZpZXdCb3g9IjAgMCAzMyAzMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMzIiBoZWlnaHQ9IjMzIiByeD0iNCIgZmlsbD0iIzAwN2JmZiIvPgo8dGV4dCB4PSIxNi41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjEwIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TTwvdGV4dD4KPC9zdmc+';

        $response = $this->put(route('admin.settings.update.section', 'appearance'), [
            'app_logo' => $newLogo,
            'app_icon' => 'fas fa-fw fa-home',
            'app_title_prefix' => '[Admin]',
            'app_title_postfix' => ' - Panel',
            'theme_color' => '#28a745',
            'sidebar_style' => 'dark',
        ]);

        $response->assertRedirect(route('admin.settings.section', 'appearance'));
        $response->assertSessionHas('success');

        $this->assertEquals($newLogo, AppSetting::getValue('app_logo'));
        $this->assertEquals('fas fa-fw fa-home', AppSetting::getValue('app_icon'));
        $this->assertEquals('[Admin]', AppSetting::getValue('app_title_prefix'));
    }

    /**
     * Test que verifica que se pueden actualizar configuraciones de seguridad
     */
    public function test_can_update_security_settings()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('admin.settings.update.section', 'security'), [
            'session_timeout' => 60,
            'max_login_attempts' => 3,
            'password_min_length' => 10,
            'require_2fa' => true,
            'allow_registration' => false,
        ]);

        $response->assertRedirect(route('admin.settings.section', 'security'));
        $response->assertSessionHas('success');

        $this->assertEquals(60, AppSetting::getValue('session_timeout'));
        $this->assertEquals(3, AppSetting::getValue('max_login_attempts'));
        $this->assertEquals(10, AppSetting::getValue('password_min_length'));
        $this->assertTrue(AppSetting::getValue('require_2fa'));
    }

    /**
     * Test que verifica que se pueden actualizar configuraciones de notificaciones
     */
    public function test_can_update_notification_settings()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('admin.settings.update.section', 'notifications'), [
            'email_notifications' => true,
            'push_notifications' => false,
            'notification_sound' => true,
            'email_smtp_host' => 'smtp.gmail.com',
            'email_smtp_port' => 587,
            'email_smtp_user' => 'test@gmail.com',
            'email_smtp_encryption' => 'tls',
        ]);

        $response->assertRedirect(route('admin.settings.section', 'notifications'));
        $response->assertSessionHas('success');

        $this->assertTrue(AppSetting::getValue('email_notifications'));
        $this->assertFalse(AppSetting::getValue('push_notifications'));
        $this->assertEquals('smtp.gmail.com', AppSetting::getValue('email_smtp_host'));
    }

    /**
     * Test que verifica que se pueden actualizar configuraciones avanzadas
     */
    public function test_can_update_advanced_settings()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('admin.settings.update.section', 'advanced'), [
            'debug_mode' => true,
            'maintenance_mode' => false,
            'cache_driver' => 'redis',
            'queue_driver' => 'database',
            'backup_frequency' => 'daily',
            'log_level' => 'debug',
        ]);

        $response->assertRedirect(route('admin.settings.section', 'advanced'));
        $response->assertSessionHas('success');

        $this->assertTrue(AppSetting::getValue('debug_mode'));
        $this->assertFalse(AppSetting::getValue('maintenance_mode'));
        $this->assertEquals('redis', AppSetting::getValue('cache_driver'));
    }

    /**
     * Test que verifica validación de campos requeridos
     */
    public function test_validation_for_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('admin.settings.update.section', 'general'), [
            'app_name' => '', // Campo requerido vacío
        ]);

        $response->assertSessionHasErrors(['app_name']);
    }

    /**
     * Test que verifica validación de campos de apariencia
     */
    public function test_validation_for_appearance_fields()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('admin.settings.update.section', 'appearance'), [
            'app_icon' => '', // Campo requerido vacío
            'theme_color' => 'invalid-color', // Color inválido
            'sidebar_style' => 'invalid-style', // Estilo inválido
        ]);

        $response->assertSessionHasErrors(['app_icon', 'theme_color', 'sidebar_style']);
    }

    /**
     * Test que verifica validación de campos de seguridad
     */
    public function test_validation_for_security_fields()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('admin.settings.update.section', 'security'), [
            'session_timeout' => 2, // Valor menor al mínimo
            'max_login_attempts' => 15, // Valor mayor al máximo
            'password_min_length' => 3, // Valor menor al mínimo
        ]);

        $response->assertSessionHasErrors(['session_timeout', 'max_login_attempts', 'password_min_length']);
    }

    /**
     * Test que verifica que el caché se limpia después de actualizar configuraciones
     */
    public function test_cache_is_cleared_after_settings_update()
    {
        $this->actingAs($this->user);

        // Crear configuración inicial
        AppSetting::setValue('app_name', 'Nombre Original');
        Cache::put('app_settings', ['app_name' => 'Nombre Original']);

        // Verificar que el caché tiene el valor
        $this->assertEquals('Nombre Original', Cache::get('app_settings')['app_name']);

        // Actualizar configuración
        $response = $this->put(route('admin.settings.update.section', 'general'), [
            'app_name' => 'Nombre Actualizado',
        ]);

        // Verificar que la configuración se actualizó
        $this->assertEquals('Nombre Actualizado', AppSetting::getValue('app_name'));

        // El caché debería estar limpio (AppConfigHelper::clearCache() se ejecuta)
        $this->assertEquals('Nombre Actualizado', AppConfigHelper::getAppName());
    }

    /**
     * Test que verifica que usuarios sin permisos no pueden acceder al dashboard
     */
    public function test_unauthorized_users_cannot_access_dashboard()
    {
        // Crear usuario sin permisos
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/settings');

        $response->assertStatus(403);
    }

    /**
     * Test que verifica que usuarios sin permisos no pueden actualizar configuraciones
     */
    public function test_unauthorized_users_cannot_update_settings()
    {
        // Crear usuario sin permisos
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('admin.settings.update.section', 'general'), [
                'app_name' => 'Nombre No Autorizado',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test que verifica la navegación entre secciones
     */
    public function test_navigation_between_sections()
    {
        $sections = ['general', 'appearance', 'security', 'notifications', 'advanced'];

        foreach ($sections as $section) {
            $response = $this->actingAs($this->user)
                ->get("/admin/settings/section/{$section}");

            $response->assertStatus(200);

            // Verificar que cada sección tiene enlaces de navegación
            $response->assertSee('Volver al Dashboard');
            $response->assertSee('Secciones');

            // Verificar que la sección actual está marcada como activa
            $response->assertSee('nav-link active');
        }
    }

    /**
     * Test que verifica que las vistas cargan correctamente con AdminLTE
     */
    public function test_views_load_with_adminlte_layout()
    {
        $this->actingAs($this->user);

        $response = $this->get('/admin/settings');
        $response->assertSee('AdminLTE', false); // Verifica que AdminLTE está presente
        $response->assertSee('sidebar', false); // Verifica que el sidebar está presente

        $response = $this->get('/admin/settings/section/general');
        $response->assertSee('AdminLTE', false);
        $response->assertSee('sidebar', false);
    }
}
