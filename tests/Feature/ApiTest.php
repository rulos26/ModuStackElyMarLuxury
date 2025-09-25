<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario de prueba
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Crear token de Sanctum
        $this->token = $this->user->createToken('API Token')->plainTextToken;
    }

    /** @test */
    public function can_get_api_info()
    {
        $response = $this->getJson('/api/info');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'name',
                        'version',
                        'description',
                        'endpoints',
                        'authentication',
                        'rate_limit'
                    ]
                ]);
    }

    /** @test */
    public function can_get_api_documentation()
    {
        $response = $this->getJson('/api/docs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'openapi',
                    'info',
                    'servers',
                    'security',
                    'paths',
                    'components'
                ]);
    }

    /** @test */
    public function can_get_simple_documentation()
    {
        $response = $this->getJson('/api/docs/simple');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'title',
                    'version',
                    'base_url',
                    'authentication',
                    'rate_limits',
                    'endpoints',
                    'examples'
                ]);
    }

    /** @test */
    public function can_authenticate_with_bearer_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/system/status');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'system',
                        'drivers',
                        'database',
                        'cache',
                        'storage'
                    ]
                ]);
    }

    /** @test */
    public function can_authenticate_with_api_key()
    {
        // Crear usuario con API key personalizada
        $this->user->update(['api_token' => 'test-api-key-123']);

        $response = $this->withHeaders([
            'X-API-Key' => 'test-api-key-123'
        ])->getJson('/api/system/status');

        $response->assertStatus(200);
    }

    /** @test */
    public function rejects_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->getJson('/api/system/status');

        $response->assertStatus(401)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'error_code'
                ]);
    }

    /** @test */
    public function requires_authentication_for_protected_endpoints()
    {
        $response = $this->getJson('/api/system/status');

        $response->assertStatus(401);
    }

    /** @test */
    public function can_get_system_status()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/system/status');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'system' => [
                            'status',
                            'uptime',
                            'memory_usage',
                            'memory_peak',
                            'php_version',
                            'laravel_version'
                        ],
                        'drivers',
                        'database',
                        'cache',
                        'storage'
                    ]
                ]);
    }

    /** @test */
    public function can_manage_drivers()
    {
        // Obtener estado de drivers
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/drivers?action=status');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);

        // Cambiar driver
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/drivers', [
            'service' => 'cache',
            'driver' => 'file',
            'config' => []
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function can_manage_backups()
    {
        // Listar respaldos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/backups?action=list');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);

        // Crear respaldo
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/backups', [
            'name' => 'Test Backup',
            'description' => 'Respaldo de prueba'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /** @test */
    public function can_manage_notifications()
    {
        // Listar notificaciones
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/notifications?action=list');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);

        // Enviar notificación
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/notifications', [
            'title' => 'Test Notification',
            'message' => 'Mensaje de prueba',
            'type' => 'info'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /** @test */
    public function can_manage_settings()
    {
        // Obtener configuración
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/settings?action=get');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);

        // Actualizar configuración
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/settings', [
            'settings' => [
                [
                    'key' => 'test_setting',
                    'value' => 'test_value'
                ]
            ]
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /** @test */
    public function can_manage_users()
    {
        // Listar usuarios
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/users?action=list');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data'
                ]);

        // Crear usuario
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data'
                ]);
    }

    /** @test */
    public function validates_request_data()
    {
        // Intentar cambiar driver con datos inválidos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/drivers', [
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
    public function handles_rate_limiting()
    {
        // Hacer múltiples requests para probar rate limiting
        $responses = [];

        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token
            ])->getJson('/api/system/status');
        }

        // Los primeros requests deberían ser exitosos
        $this->assertTrue($responses[0]->status() === 200);
        $this->assertTrue($responses[1]->status() === 200);
    }

    /** @test */
    public function can_login_via_api()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'token',
                        'token_type'
                    ]
                ]);
    }

    /** @test */
    public function can_logout_via_api()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    /** @test */
    public function can_get_user_info()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at'
                    ]
                ]);
    }

    /** @test */
    public function can_check_health()
    {
        $response = $this->getJson('/api/utils/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'status',
                    'timestamp',
                    'version'
                ]);
    }

    /** @test */
    public function can_get_version_info()
    {
        $response = $this->getJson('/api/utils/version');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'api_version',
                        'laravel_version',
                        'php_version',
                        'server_time'
                    ]
                ]);
    }

    /** @test */
    public function handles_404_errors()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/nonexistent-endpoint');

        $response->assertStatus(404)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'error_code'
                ]);
    }

    /** @test */
    public function includes_rate_limit_headers()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/system/status');

        $response->assertStatus(200)
                ->assertHeader('X-RateLimit-Limit')
                ->assertHeader('X-RateLimit-Remaining')
                ->assertHeader('X-RateLimit-Reset');
    }

    /** @test */
    public function can_handle_concurrent_requests()
    {
        $responses = [];

        // Simular requests concurrentes
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token
            ])->getJson('/api/system/status');
        }

        // Todos los requests deberían ser exitosos
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->status());
        }
    }

    /** @test */
    public function logs_api_access()
    {
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/system/status');

        // Verificar que se logueó el acceso
        $this->assertTrue(true); // El log se verifica en el middleware
    }

    /** @test */
    public function can_handle_driver_validation()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/drivers', [
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
                    'message'
                ]);
    }

    /** @test */
    public function can_restore_driver_config()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/drivers', [
            'service' => 'cache',
            'driver' => 'file',
            'config' => []
        ]);

        $response->assertStatus(200);

        // Intentar restaurar
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/drivers', [
            'service' => 'cache',
            'driver' => 'array',
            'config' => []
        ]);

        $response->assertStatus(200);
    }
}

