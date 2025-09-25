<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\DynamicDriverService;
use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class DynamicDriverTest extends TestCase
{
    use RefreshDatabase;

    protected $dynamicDriverService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dynamicDriverService = new DynamicDriverService();
    }

    /** @test */
    public function can_get_supported_drivers_for_service()
    {
        $cacheDrivers = $this->dynamicDriverService->getSupportedDrivers('cache');
        $mailDrivers = $this->dynamicDriverService->getSupportedDrivers('mail');
        $databaseDrivers = $this->dynamicDriverService->getSupportedDrivers('database');

        $this->assertContains('file', $cacheDrivers);
        $this->assertContains('redis', $cacheDrivers);
        $this->assertContains('smtp', $mailDrivers);
        $this->assertContains('mysql', $databaseDrivers);
    }

    /** @test */
    public function can_change_driver_successfully()
    {
        // Cambiar driver de cache a file (más compatible para testing)
        $result = $this->dynamicDriverService->changeDriver('cache', 'file', [
            'path' => storage_path('framework/cache')
        ]);

        $this->assertTrue($result);

        // Verificar que se guardó en base de datos
        $setting = AppSetting::where('key', 'driver_config_cache')->first();
        $this->assertNotNull($setting);

        $config = json_decode($setting->value, true);
        $this->assertEquals('file', $config['driver']);
    }

    /** @test */
    public function cannot_change_to_unsupported_driver()
    {
        $result = $this->dynamicDriverService->changeDriver('cache', 'unsupported_driver');

        $this->assertFalse($result);
    }

    /** @test */
    public function can_get_current_driver()
    {
        // Establecer un driver
        $this->dynamicDriverService->changeDriver('cache', 'file');

        $currentDriver = $this->dynamicDriverService->getCurrentDriver('cache');
        $this->assertEquals('file', $currentDriver);
    }

    /** @test */
    public function can_get_driver_config()
    {
        // Configurar un driver
        $config = ['path' => storage_path('framework/cache')];
        $this->dynamicDriverService->changeDriver('cache', 'file', $config);

        $driverConfig = $this->dynamicDriverService->getDriverConfig('cache');
        $this->assertEquals('file', $driverConfig['driver']);
        $this->assertEquals($config, $driverConfig['config']);
    }

    /** @test */
    public function can_restore_driver_config()
    {
        // Configurar un driver
        $this->dynamicDriverService->changeDriver('cache', 'file', ['path' => storage_path('framework/cache')]);

        // Verificar que se guardó en base de datos
        $setting = AppSetting::where('key', 'driver_config_cache')->first();
        $this->assertNotNull($setting);

        $config = json_decode($setting->value, true);
        $this->assertEquals('file', $config['driver']);

        // Cambiar a otro driver
        $this->dynamicDriverService->changeDriver('cache', 'array');

        // Limpiar cache para forzar la restauración desde base de datos
        Cache::forget('driver_config_cache');

        // Restaurar configuración
        $result = $this->dynamicDriverService->restoreDriverConfig('cache');

        $this->assertTrue($result);
    }

    /** @test */
    public function can_get_all_drivers_status()
    {
        $status = $this->dynamicDriverService->getAllDriversStatus();

        $this->assertArrayHasKey('cache', $status);
        $this->assertArrayHasKey('session', $status);
        $this->assertArrayHasKey('queue', $status);
        $this->assertArrayHasKey('mail', $status);
        $this->assertArrayHasKey('database', $status);

        foreach ($status as $service => $data) {
            $this->assertArrayHasKey('current', $data);
            $this->assertArrayHasKey('supported', $data);
            $this->assertArrayHasKey('config', $data);
        }
    }

    /** @test */
    public function validates_mail_smtp_config()
    {
        $errors = $this->dynamicDriverService->validateDriverConfig('mail', 'smtp', []);

        $this->assertContains('Host SMTP es requerido', $errors);
        $this->assertContains('Puerto SMTP es requerido', $errors);
    }

    /** @test */
    public function validates_database_config()
    {
        $errors = $this->dynamicDriverService->validateDriverConfig('database', 'mysql', []);

        $this->assertContains('Host de base de datos es requerido', $errors);
        $this->assertContains('Nombre de base de datos es requerido', $errors);
    }

    /** @test */
    public function validates_redis_config()
    {
        $errors = $this->dynamicDriverService->validateDriverConfig('cache', 'redis', []);

        $this->assertContains('Host Redis es requerido', $errors);
    }

    /** @test */
    public function can_restart_services()
    {
        $result = $this->dynamicDriverService->restartServices(['cache', 'session']);

        $this->assertTrue($result);
    }

    /** @test */
    public function api_can_get_drivers_status()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/admin/drivers/status');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'cache' => ['current', 'supported', 'config'],
                        'session' => ['current', 'supported', 'config'],
                        'queue' => ['current', 'supported', 'config'],
                        'mail' => ['current', 'supported', 'config'],
                        'database' => ['current', 'supported', 'config']
                    ]
                ]);
    }

    /** @test */
    public function api_can_get_supported_drivers()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/admin/drivers/supported/cache');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => []
                ]);
    }

    /** @test */
    public function api_can_change_driver()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/admin/drivers/change', [
            'service' => 'cache',
            'driver' => 'file',
            'config' => []
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => ['service', 'driver', 'config']
                ]);
    }

    /** @test */
    public function api_validates_driver_change_request()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/admin/drivers/change', [
            'service' => 'invalid_service',
            'driver' => 'file'
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    }

    /** @test */
    public function api_can_validate_driver_config()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/admin/drivers/validate', [
            'service' => 'mail',
            'driver' => 'smtp',
            'config' => [
                'host' => 'smtp.gmail.com',
                'port' => 587
            ]
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'valid',
                    'errors'
                ]);
    }

    /** @test */
    public function api_can_restore_driver()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Primero configurar un driver
        $this->dynamicDriverService->changeDriver('cache', 'file', ['path' => storage_path('framework/cache')]);

        $response = $this->postJson('/admin/drivers/restore/cache');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function api_can_restart_services()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/admin/drivers/restart', [
            'services' => ['cache', 'session']
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function api_can_get_statistics()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/admin/drivers/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'cache' => ['current_driver', 'supported_count', 'has_config', 'last_updated'],
                        'session' => ['current_driver', 'supported_count', 'has_config', 'last_updated'],
                        'queue' => ['current_driver', 'supported_count', 'has_config', 'last_updated'],
                        'mail' => ['current_driver', 'supported_count', 'has_config', 'last_updated'],
                        'database' => ['current_driver', 'supported_count', 'has_config', 'last_updated']
                    ]
                ]);
    }

    /** @test */
    public function middleware_applies_driver_configurations()
    {
        // Crear usuario y autenticarse
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Configurar drivers específicos
        $this->dynamicDriverService->changeDriver('cache', 'file', ['path' => storage_path('framework/cache')]);

        $response = $this->get('/admin/drivers');

        $response->assertStatus(200);

        // Verificar que la configuración se aplicó
        $this->assertEquals('file', $this->dynamicDriverService->getCurrentDriver('cache'));
    }

    /** @test */
    public function can_handle_driver_change_errors_gracefully()
    {
        // Intentar cambiar a un driver no soportado
        $result = $this->dynamicDriverService->changeDriver('cache', 'invalid_driver');

        $this->assertFalse($result);
    }

    /** @test */
    public function cache_is_cleared_after_driver_change()
    {
        // Configurar un driver
        $this->dynamicDriverService->changeDriver('cache', 'file', ['path' => storage_path('framework/cache')]);

        // Verificar que el cache se limpió
        $this->assertNull(Cache::get('driver_config_cache'));
    }

    /** @test */
    public function can_handle_multiple_concurrent_driver_changes()
    {
        $results = [];

        // Simular cambios concurrentes
        $results[] = $this->dynamicDriverService->changeDriver('cache', 'file');
        $results[] = $this->dynamicDriverService->changeDriver('session', 'database');
        $results[] = $this->dynamicDriverService->changeDriver('queue', 'sync');

        $this->assertTrue($results[0]);
        $this->assertTrue($results[1]);
        $this->assertTrue($results[2]);
    }
}
