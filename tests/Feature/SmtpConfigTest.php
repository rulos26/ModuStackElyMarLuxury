<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\SmtpConfig;
use App\Models\User;
use App\Services\SmtpConfigService;
use App\Services\EmailService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmtpConfigTest extends TestCase
{
    use RefreshDatabase;

    protected $smtpConfigService;
    protected $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->smtpConfigService = app(SmtpConfigService::class);
        $this->emailService = app(EmailService::class);
    }

    /**
     * Test crear configuración SMTP
     */
    public function test_can_create_smtp_config()
    {
        $config = SmtpConfig::create([
            'name' => 'Test SMTP',
            'mailer' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'test@gmail.com',
            'password' => 'test_password',
            'from_address' => 'noreply@test.com',
            'from_name' => 'Test App'
        ]);

        $this->assertDatabaseHas('smtp_configs', [
            'name' => 'Test SMTP',
            'mailer' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'from_address' => 'noreply@test.com',
            'from_name' => 'Test App'
        ]);
    }

    /**
     * Test encriptación/desencriptación de contraseña
     */
    public function test_password_encryption_decryption()
    {
        $config = SmtpConfig::create([
            'name' => 'Test SMTP',
            'mailer' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'password' => 'test_password',
            'from_address' => 'noreply@test.com',
            'from_name' => 'Test App'
        ]);

        // Verificar que la contraseña se encriptó en la base de datos
        $this->assertNotEquals('test_password', $config->getRawOriginal('password'));

        // Verificar que se puede desencriptar
        $this->assertEquals('test_password', $config->password);
    }

    /**
     * Test obtener configuración por defecto
     */
    public function test_can_get_active_default_config()
    {
        // Crear configuración no activa
        SmtpConfig::create([
            'name' => 'Inactive Config',
            'mailer' => 'smtp',
            'host' => 'smtp.test.com',
            'port' => 587,
            'from_address' => 'test@test.com',
            'from_name' => 'Test',
            'is_active' => false,
            'is_default' => true
        ]);

        // Crear configuración activa por defecto
        $activeConfig = SmtpConfig::create([
            'name' => 'Active Default',
            'mailer' => 'smtp',
            'host' => 'smtp.active.com',
            'port' => 587,
            'from_address' => 'active@test.com',
            'from_name' => 'Active',
            'is_active' => true,
            'is_default' => true
        ]);

        $defaultConfig = SmtpConfig::getActiveDefault();

        $this->assertNotNull($defaultConfig);
        $this->assertEquals('Active Default', $defaultConfig->name);
        $this->assertTrue($defaultConfig->is_active);
        $this->assertTrue($defaultConfig->is_default);
    }

    /**
     * Test establecer como configuración por defecto
     */
    public function test_can_set_as_default()
    {
        // Crear configuraciones
        $config1 = SmtpConfig::create([
            'name' => 'Config 1',
            'mailer' => 'smtp',
            'host' => 'smtp1.test.com',
            'port' => 587,
            'from_address' => 'config1@test.com',
            'from_name' => 'Config 1',
            'is_default' => true
        ]);

        $config2 = SmtpConfig::create([
            'name' => 'Config 2',
            'mailer' => 'smtp',
            'host' => 'smtp2.test.com',
            'port' => 587,
            'from_address' => 'config2@test.com',
            'from_name' => 'Config 2',
            'is_default' => false
        ]);

        // Establecer config2 como por defecto
        $result = $config2->setAsDefault();

        $this->assertTrue($result);

        // Verificar que config1 ya no es por defecto
        $this->assertFalse($config1->fresh()->is_default);

        // Verificar que config2 es por defecto y activa
        $this->assertTrue($config2->fresh()->is_default);
        $this->assertTrue($config2->fresh()->is_active);
    }

    /**
     * Test validación de configuración
     */
    public function test_can_validate_configuration()
    {
        // Configuración válida
        $validConfig = SmtpConfig::create([
            'name' => 'Valid Config',
            'mailer' => 'smtp',
            'host' => 'smtp.test.com',
            'port' => 587,
            'from_address' => 'valid@test.com',
            'from_name' => 'Valid Config'
        ]);

        $validation = $validConfig->validate();
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);

        // Configuración inválida
        $invalidConfig = SmtpConfig::create([
            'name' => 'Invalid Config',
            'mailer' => 'smtp',
            'host' => '', // Host vacío
            'port' => 99999, // Puerto inválido
            'from_address' => 'invalid-email', // Email inválido
            'from_name' => 'Invalid Config'
        ]);

        $validation = $invalidConfig->validate();
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /**
     * Test crear configuración predefinida
     */
    public function test_can_create_predefined_config()
    {
        $config = SmtpConfig::createPredefined('gmail', [
            'username' => 'test@gmail.com',
            'password' => 'app_password',
            'from_address' => 'noreply@gmail.com',
            'from_name' => 'Gmail Config'
        ]);

        $this->assertEquals('Gmail', $config->name);
        $this->assertEquals('smtp', $config->mailer);
        $this->assertEquals('smtp.gmail.com', $config->host);
        $this->assertEquals(587, $config->port);
        $this->assertEquals('tls', $config->encryption);
        $this->assertEquals('test@gmail.com', $config->username);
        $this->assertEquals('app_password', $config->password);
    }

    /**
     * Test obtener configuraciones predefinidas
     */
    public function test_can_get_predefined_configs()
    {
        $predefined = SmtpConfig::getPredefinedConfigs();

        $this->assertArrayHasKey('gmail', $predefined);
        $this->assertArrayHasKey('outlook', $predefined);
        $this->assertArrayHasKey('yahoo', $predefined);
        $this->assertArrayHasKey('mailtrap', $predefined);
        $this->assertArrayHasKey('sendmail', $predefined);

        $this->assertEquals('Gmail', $predefined['gmail']['name']);
        $this->assertEquals('smtp.gmail.com', $predefined['gmail']['host']);
        $this->assertEquals(587, $predefined['gmail']['port']);
    }

    /**
     * Test aplicar configuración dinámica
     */
    public function test_can_apply_dynamic_config()
    {
        $config = SmtpConfig::create([
            'name' => 'Test Config',
            'mailer' => 'smtp',
            'host' => 'smtp.test.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'test@test.com',
            'password' => 'test_password',
            'from_address' => 'noreply@test.com',
            'from_name' => 'Test App'
        ]);

        $result = $this->smtpConfigService->applyDynamicConfig($config);

        $this->assertTrue($result);

        // Verificar que la configuración se aplicó
        $currentConfig = $this->smtpConfigService->getCurrentConfig();
        $this->assertEquals('Test Config', $currentConfig->name);
    }

    /**
     * Test crear configuración desde formulario
     */
    public function test_can_create_from_form()
    {
        $user = User::factory()->create();

        $formData = [
            'name' => 'Form Config',
            'mailer' => 'smtp',
            'host' => 'smtp.form.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'form@test.com',
            'password' => 'form_password',
            'from_address' => 'noreply@form.com',
            'from_name' => 'Form Config',
            'description' => 'Config created from form'
        ];

        $config = $this->smtpConfigService->createFromForm($formData, $user->id);

        $this->assertEquals('Form Config', $config->name);
        $this->assertEquals('smtp.form.com', $config->host);
        $this->assertEquals('form@test.com', $config->username);
        $this->assertEquals('form_password', $config->password);
        $this->assertEquals($user->id, $config->created_by);
        $this->assertTrue($config->is_active);
    }

    /**
     * Test actualizar configuración
     */
    public function test_can_update_configuration()
    {
        $config = SmtpConfig::create([
            'name' => 'Original Config',
            'mailer' => 'smtp',
            'host' => 'smtp.original.com',
            'port' => 587,
            'from_address' => 'original@test.com',
            'from_name' => 'Original'
        ]);

        $updateData = [
            'name' => 'Updated Config',
            'host' => 'smtp.updated.com',
            'port' => 465,
            'encryption' => 'ssl',
            'from_address' => 'updated@test.com',
            'from_name' => 'Updated'
        ];

        $result = $this->smtpConfigService->updateConfiguration($config, $updateData);

        $this->assertTrue($result);

        $config->refresh();
        $this->assertEquals('Updated Config', $config->name);
        $this->assertEquals('smtp.updated.com', $config->host);
        $this->assertEquals(465, $config->port);
        $this->assertEquals('ssl', $config->encryption);
    }

    /**
     * Test activar/desactivar configuración
     */
    public function test_can_toggle_active()
    {
        $config = SmtpConfig::create([
            'name' => 'Toggle Config',
            'mailer' => 'smtp',
            'host' => 'smtp.toggle.com',
            'port' => 587,
            'from_address' => 'toggle@test.com',
            'from_name' => 'Toggle',
            'is_active' => true,
            'is_default' => true
        ]);

        // Desactivar
        $result = $this->smtpConfigService->toggleActive($config);
        $this->assertTrue($result);

        $config->refresh();
        $this->assertFalse($config->is_active);
        $this->assertFalse($config->is_default); // Debe quitar el flag de default

        // Activar
        $result = $this->smtpConfigService->toggleActive($config);
        $this->assertTrue($result);

        $config->refresh();
        $this->assertTrue($config->is_active);
    }

    /**
     * Test eliminar configuración
     */
    public function test_can_delete_configuration()
    {
        $config = SmtpConfig::create([
            'name' => 'Delete Config',
            'mailer' => 'smtp',
            'host' => 'smtp.delete.com',
            'port' => 587,
            'from_address' => 'delete@test.com',
            'from_name' => 'Delete'
        ]);

        $result = $this->smtpConfigService->deleteConfiguration($config);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('smtp_configs', ['id' => $config->id]);
    }

    /**
     * Test no permitir eliminar configuración por defecto
     */
    public function test_cannot_delete_default_configuration()
    {
        $config = SmtpConfig::create([
            'name' => 'Default Config',
            'mailer' => 'smtp',
            'host' => 'smtp.default.com',
            'port' => 587,
            'from_address' => 'default@test.com',
            'from_name' => 'Default',
            'is_default' => true
        ]);

        $result = $this->smtpConfigService->deleteConfiguration($config);

        $this->assertFalse($result);
        $this->assertDatabaseHas('smtp_configs', ['id' => $config->id]);
    }

    /**
     * Test obtener configuraciones disponibles
     */
    public function test_can_get_available_configurations()
    {
        // Crear configuraciones activas e inactivas
        SmtpConfig::create([
            'name' => 'Active Config',
            'mailer' => 'smtp',
            'host' => 'smtp.active.com',
            'port' => 587,
            'from_address' => 'active@test.com',
            'from_name' => 'Active',
            'is_active' => true
        ]);

        SmtpConfig::create([
            'name' => 'Inactive Config',
            'mailer' => 'smtp',
            'host' => 'smtp.inactive.com',
            'port' => 587,
            'from_address' => 'inactive@test.com',
            'from_name' => 'Inactive',
            'is_active' => false
        ]);

        $configs = $this->smtpConfigService->getAvailableConfigurations();

        $this->assertCount(1, $configs);
        $this->assertEquals('Active Config', $configs[0]['name']);
    }

    /**
     * Test estadísticas del sistema
     */
    public function test_can_get_system_statistics()
    {
        // Crear algunas configuraciones
        SmtpConfig::create([
            'name' => 'Config 1',
            'mailer' => 'smtp',
            'host' => 'smtp1.test.com',
            'port' => 587,
            'from_address' => 'config1@test.com',
            'from_name' => 'Config 1',
            'is_active' => true,
            'is_default' => true
        ]);

        SmtpConfig::create([
            'name' => 'Config 2',
            'mailer' => 'smtp',
            'host' => 'smtp2.test.com',
            'port' => 587,
            'from_address' => 'config2@test.com',
            'from_name' => 'Config 2',
            'is_active' => false
        ]);

        SmtpConfig::create([
            'name' => 'Config 3',
            'mailer' => 'sendmail',
            'from_address' => 'config3@test.com',
            'from_name' => 'Config 3',
            'is_active' => true
        ]);

        $stats = $this->smtpConfigService->getSystemStatistics();

        $this->assertArrayHasKey('smtp_configs', $stats);
        $this->assertEquals(3, $stats['smtp_configs']['total_configs']);
        $this->assertEquals(2, $stats['smtp_configs']['active_configs']);
        $this->assertEquals(1, $stats['smtp_configs']['inactive_configs']);
        $this->assertEquals('Config 1', $stats['smtp_configs']['default_config']);
        $this->assertContains('smtp', $stats['smtp_configs']['mailer_types']);
        $this->assertContains('sendmail', $stats['smtp_configs']['mailer_types']);
    }

    /**
     * Test migrar configuración desde .env
     */
    public function test_can_migrate_from_env()
    {
        // Configurar valores en config para simular .env
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.env.com');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.encryption', 'tls');
        Config::set('mail.mailers.smtp.username', 'env@test.com');
        Config::set('mail.mailers.smtp.password', 'env_password');
        Config::set('mail.from.address', 'noreply@env.com');
        Config::set('mail.from.name', 'ENV Config');

        $config = $this->smtpConfigService->migrateFromEnv();

        $this->assertNotNull($config);
        $this->assertEquals('Configuración desde .env', $config->name);
        $this->assertEquals('smtp.env.com', $config->host);
        $this->assertEquals(587, $config->port);
        $this->assertEquals('tls', $config->encryption);
        $this->assertEquals('env@test.com', $config->username);
        $this->assertEquals('env_password', $config->password);
        $this->assertEquals('noreply@env.com', $config->from_address);
        $this->assertEquals('ENV Config', $config->from_name);
    }

    /**
     * Test duplicar configuración
     */
    public function test_can_duplicate_configuration()
    {
        $original = SmtpConfig::create([
            'name' => 'Original Config',
            'mailer' => 'smtp',
            'host' => 'smtp.original.com',
            'port' => 587,
            'from_address' => 'original@test.com',
            'from_name' => 'Original'
        ]);

        // Crear duplicado manualmente
        $duplicate = SmtpConfig::create([
            'name' => 'Duplicated Config',
            'mailer' => $original->mailer,
            'host' => $original->host,
            'port' => $original->port,
            'encryption' => $original->encryption,
            'username' => $original->username,
            'password' => $original->password,
            'timeout' => $original->timeout,
            'local_domain' => $original->local_domain,
            'from_address' => $original->from_address,
            'from_name' => $original->from_name,
            'description' => "Copia de {$original->name}",
            'is_active' => false
        ]);

        $this->assertEquals('Duplicated Config', $duplicate->name);
        $this->assertEquals('smtp.original.com', $duplicate->host);
        $this->assertEquals(587, $duplicate->port);
        $this->assertEquals('original@test.com', $duplicate->from_address);
        $this->assertEquals('Original', $duplicate->from_name);
        $this->assertFalse($duplicate->is_active);
    }

    /**
     * Test atributos de configuración
     */
    public function test_configuration_attributes()
    {
        $config = SmtpConfig::create([
            'name' => 'Test Attributes',
            'mailer' => 'smtp',
            'host' => 'smtp.test.com',
            'port' => 587,
            'from_address' => 'test@test.com',
            'from_name' => 'Test',
            'is_active' => true,
            'is_default' => false
        ]);

        $this->assertStringContainsString('badge-primary', $config->status_badge);
        $this->assertStringContainsString('Activa', $config->status_badge);

        $this->assertStringContainsString('badge-primary', $config->mailer_badge);
        $this->assertStringContainsString('SMTP', $config->mailer_badge);

        $connectionInfo = $config->connection_info;
        $this->assertArrayHasKey('name', $connectionInfo);
        $this->assertArrayHasKey('mailer', $connectionInfo);
        $this->assertArrayHasKey('host', $connectionInfo);
        $this->assertArrayNotHasKey('password', $connectionInfo); // No debe incluir contraseña
    }

    /**
     * Test integración con EmailService
     */
    public function test_email_service_integration()
    {
        $config = SmtpConfig::create([
            'name' => 'Email Service Config',
            'mailer' => 'smtp',
            'host' => 'smtp.emailservice.com',
            'port' => 587,
            'from_address' => 'service@test.com',
            'from_name' => 'Email Service',
            'is_active' => true,
            'is_default' => true
        ]);

        // Verificar que el servicio puede aplicar la configuración
        $result = $this->smtpConfigService->applyDynamicConfig($config);
        $this->assertTrue($result);

        // Verificar que el servicio puede obtener la configuración actual
        $currentConfig = $this->smtpConfigService->getCurrentConfig();
        $this->assertNotNull($currentConfig);
        $this->assertEquals('Email Service Config', $currentConfig->name);
    }

    /**
     * Test limpiar cache
     */
    public function test_can_clear_cache()
    {
        // Crear configuración y establecer como default para generar cache
        $config = SmtpConfig::create([
            'name' => 'Cache Config',
            'mailer' => 'smtp',
            'host' => 'smtp.cache.com',
            'port' => 587,
            'from_address' => 'cache@test.com',
            'from_name' => 'Cache',
            'is_active' => true,
            'is_default' => true
        ]);

        // Forzar generación de cache
        SmtpConfig::getActiveDefault();

        // Verificar que se generó cache
        $this->assertTrue(Cache::has('smtp_config_default'));

        // Limpiar cache
        $this->smtpConfigService->clearCache();

        // Verificar que se limpió cache
        $this->assertFalse(Cache::has('smtp_config_default'));
    }
}
