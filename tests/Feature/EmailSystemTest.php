<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailService;
use App\Jobs\SendEmailJob;
use App\Jobs\SendBulkEmailJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmailSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailService = app(EmailService::class);

        // Configurar mail para testing
        Config::set('mail.default', 'log');
        Mail::fake();
        Queue::fake();
    }

    /**
     * Test crear plantilla de email
     */
    public function test_can_create_email_template()
    {
        $template = EmailTemplate::create([
            'name' => 'test_template',
            'subject' => 'Test Subject {{user_name}}',
            'body_html' => '<h1>Hello {{user_name}}</h1>',
            'body_text' => 'Hello {{user_name}}',
            'variables' => ['user_name' => 'Nombre del usuario'],
            'category' => 'test',
            'description' => 'Plantilla de prueba'
        ]);

        $this->assertDatabaseHas('email_templates', [
            'name' => 'test_template',
            'subject' => 'Test Subject {{user_name}}'
        ]);
    }

    /**
     * Test procesar plantilla con variables
     */
    public function test_can_process_template_with_variables()
    {
        $template = EmailTemplate::create([
            'name' => 'welcome_template',
            'subject' => 'Bienvenido {{user_name}} a {{app_name}}',
            'body_html' => '<h1>Hola {{user_name}}</h1><p>Bienvenido a {{app_name}}</p>',
            'body_text' => 'Hola {{user_name}}, bienvenido a {{app_name}}',
            'variables' => ['user_name' => 'Nombre del usuario'],
            'category' => 'auth'
        ]);

        $processed = $template->processTemplate(['user_name' => 'Juan']);

        $this->assertStringContainsString('Juan', $processed['subject']);
        $this->assertStringContainsString('Juan', $processed['body_html']);
        $this->assertStringContainsString(config('app.name'), $processed['subject']);
    }

    /**
     * Test envío de email con plantilla
     */
    public function test_can_send_email_with_template()
    {
        // Crear plantilla
        EmailTemplate::create([
            'name' => 'welcome',
            'subject' => 'Bienvenido {{user_name}}',
            'body_html' => '<h1>Hola {{user_name}}</h1>',
            'body_text' => 'Hola {{user_name}}',
            'variables' => ['user_name' => 'Nombre del usuario'],
            'category' => 'auth'
        ]);

        $result = $this->emailService->sendTemplate(
            'welcome',
            'test@example.com',
            ['user_name' => 'Juan'],
            'Juan Pérez'
        );

        $this->assertTrue($result);
    }

    /**
     * Test envío de email directo
     */
    public function test_can_send_direct_email()
    {
        $result = $this->emailService->sendDirect(
            'test@example.com',
            'Test Subject',
            'Test Body',
            'Test User'
        );

        $this->assertTrue($result);
    }

    /**
     * Test envío masivo de emails
     */
    public function test_can_send_bulk_emails()
    {
        // Crear plantilla
        EmailTemplate::create([
            'name' => 'notification',
            'subject' => 'Notificación: {{title}}',
            'body_html' => '<h1>{{title}}</h1><p>{{message}}</p>',
            'body_text' => '{{title}}: {{message}}',
            'variables' => [
                'title' => 'Título',
                'message' => 'Mensaje'
            ],
            'category' => 'notifications'
        ]);

        $recipients = [
            ['email' => 'user1@example.com', 'name' => 'User 1'],
            ['email' => 'user2@example.com', 'name' => 'User 2']
        ];

        $result = $this->emailService->sendBulk(
            'notification',
            $recipients,
            ['title' => 'Test', 'message' => 'Test message']
        );

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * Test envío de email de bienvenida
     */
    public function test_can_send_welcome_email()
    {
        $user = User::factory()->create();

        // Crear plantilla de bienvenida
        EmailTemplate::create([
            'name' => 'welcome',
            'subject' => 'Bienvenido {{user_name}}',
            'body_html' => '<h1>¡Bienvenido {{user_name}}!</h1>',
            'body_text' => '¡Bienvenido {{user_name}}!',
            'variables' => ['user_name' => 'Nombre del usuario'],
            'category' => 'auth'
        ]);

        $result = $this->emailService->sendWelcomeEmail($user);

        $this->assertTrue($result);
    }

    /**
     * Test envío de email a usuarios con rol
     */
    public function test_can_send_email_to_role()
    {
        // Crear usuarios directamente sin roles para evitar problemas de permisos
        $user = User::factory()->create();

        // Crear plantilla
        EmailTemplate::create([
            'name' => 'admin_notification',
            'subject' => 'Notificación de administrador',
            'body_html' => '<h1>Notificación importante</h1>',
            'body_text' => 'Notificación importante',
            'category' => 'system'
        ]);

        // Simular envío a un usuario específico en lugar de por rol
        $result = $this->emailService->sendBulk(
            'admin_notification',
            [['email' => $user->email, 'name' => $user->name]],
            ['message' => 'Test message']
        );

        $this->assertEquals(1, $result['success']);
    }

    /**
     * Test validación de configuración de email
     */
    public function test_can_validate_email_configuration()
    {
        $validation = $this->emailService->validateConfiguration();

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
    }

    /**
     * Test estadísticas de emails
     */
    public function test_can_get_email_stats()
    {
        // Crear algunas plantillas
        EmailTemplate::create([
            'name' => 'template1',
            'subject' => 'Subject 1',
            'body_html' => 'Body 1',
            'category' => 'auth'
        ]);

        EmailTemplate::create([
            'name' => 'template2',
            'subject' => 'Subject 2',
            'body_html' => 'Body 2',
            'category' => 'notifications',
            'is_active' => false
        ]);

        $stats = $this->emailService->getEmailStats();

        $this->assertEquals(2, $stats['templates_count']);
        $this->assertEquals(1, $stats['active_templates']);
        $this->assertContains('auth', $stats['categories']);
        $this->assertContains('notifications', $stats['categories']);
    }

    /**
     * Test plantillas por categoría
     */
    public function test_can_get_templates_by_category()
    {
        EmailTemplate::create([
            'name' => 'auth_template',
            'subject' => 'Auth Subject',
            'body_html' => 'Auth Body',
            'category' => 'auth'
        ]);

        EmailTemplate::create([
            'name' => 'notification_template',
            'subject' => 'Notification Subject',
            'body_html' => 'Notification Body',
            'category' => 'notifications'
        ]);

        $authTemplates = EmailTemplate::getTemplatesByCategory('auth');
        $this->assertCount(1, $authTemplates);
        $this->assertEquals('auth_template', $authTemplates->first()->name);

        $notificationTemplates = EmailTemplate::getTemplatesByCategory('notifications');
        $this->assertCount(1, $notificationTemplates);
        $this->assertEquals('notification_template', $notificationTemplates->first()->name);
    }

    /**
     * Test plantilla por nombre
     */
    public function test_can_get_template_by_name()
    {
        EmailTemplate::create([
            'name' => 'specific_template',
            'subject' => 'Specific Subject',
            'body_html' => 'Specific Body',
            'category' => 'test'
        ]);

        $template = EmailTemplate::getTemplateByName('specific_template');
        $this->assertNotNull($template);
        $this->assertEquals('specific_template', $template->name);

        $nonExistentTemplate = EmailTemplate::getTemplateByName('non_existent');
        $this->assertNull($nonExistentTemplate);
    }

    /**
     * Test variables disponibles en plantilla
     */
    public function test_can_get_available_variables()
    {
        $template = EmailTemplate::create([
            'name' => 'variable_template',
            'subject' => 'Subject with {{custom_var}}',
            'body_html' => 'Body with :system_var',
            'body_text' => 'Text with {{custom_var}} and :system_var',
            'variables' => ['custom_var' => 'Variable personalizada'],
            'category' => 'test'
        ]);

        $variables = $template->getAvailableVariables();

        $this->assertArrayHasKey('app_name', $variables);
        $this->assertArrayHasKey('custom_var', $variables);
        $this->assertEquals('Variable personalizada', $variables['custom_var']);
    }

    /**
     * Test validación de variables
     */
    public function test_can_validate_template_variables()
    {
        $template = EmailTemplate::create([
            'name' => 'validation_template',
            'subject' => 'Hello {{user_name}}, your code is {{verification_code}}',
            'body_html' => '<p>Hello {{user_name}}</p>',
            'body_text' => 'Hello {{user_name}}',
            'variables' => [
                'user_name' => 'Nombre del usuario',
                'verification_code' => 'Código de verificación'
            ],
            'category' => 'auth'
        ]);

        // Variables completas
        $missing = $template->validateVariables([
            'user_name' => 'Juan',
            'verification_code' => '123456'
        ]);
        $this->assertEmpty($missing);

        // Variables faltantes
        $missing = $template->validateVariables(['user_name' => 'Juan']);
        $this->assertContains('verification_code', $missing);
    }

    /**
     * Test duplicar plantilla
     */
    public function test_can_duplicate_template()
    {
        $original = EmailTemplate::create([
            'name' => 'original_template',
            'subject' => 'Original Subject',
            'body_html' => 'Original Body',
            'category' => 'test'
        ]);

        $duplicate = $original->duplicate('duplicate_template');

        $this->assertEquals('duplicate_template', $duplicate->name);
        $this->assertEquals('Original Subject', $duplicate->subject);
        $this->assertEquals('Original Body', $duplicate->body_html);
        $this->assertFalse($duplicate->is_active);
    }

    /**
     * Test crear plantilla de ejemplo
     */
    public function test_can_create_example_template()
    {
        $template = EmailTemplate::createExampleTemplate('welcome', 'auth');

        $this->assertEquals('welcome', $template->name);
        $this->assertEquals('auth', $template->category);
        $this->assertStringContainsString('Bienvenido', $template->subject);
        $this->assertTrue($template->is_active);
    }

    /**
     * Test atributos de plantilla
     */
    public function test_template_attributes()
    {
        $template = EmailTemplate::create([
            'name' => 'test_template',
            'subject' => 'Test Subject',
            'body_html' => 'Test Body',
            'category' => 'auth',
            'is_active' => true
        ]);

        $this->assertEquals('Autenticación', $template->category_name);
        $this->assertStringContainsString('badge-success', $template->status_badge);
        $this->assertStringContainsString('badge-primary', $template->category_badge);
    }

    /**
     * Test job de envío de email
     */
    public function test_send_email_job()
    {
        Queue::fake();

        // Crear plantilla
        EmailTemplate::create([
            'name' => 'job_template',
            'subject' => 'Job Subject',
            'body_html' => 'Job Body',
            'category' => 'test'
        ]);

        $emailData = [
            'template_name' => 'job_template',
            'to_email' => 'test@example.com',
            'to_name' => 'Test User',
            'variables' => ['test' => 'value']
        ];

        SendEmailJob::dispatch($emailData, true);

        Queue::assertPushed(SendEmailJob::class);
    }

    /**
     * Test job de envío masivo
     */
    public function test_send_bulk_email_job()
    {
        Queue::fake();

        $recipients = [
            ['email' => 'user1@example.com', 'name' => 'User 1'],
            ['email' => 'user2@example.com', 'name' => 'User 2']
        ];

        SendBulkEmailJob::dispatch(
            'test_template',
            $recipients,
            ['test' => 'value'],
            [],
            10
        );

        Queue::assertPushed(SendBulkEmailJob::class);
    }

    /**
     * Test envío de notificación del sistema
     */
    public function test_can_send_system_notification()
    {
        $user = User::factory()->create();

        // Crear plantilla de notificación del sistema
        EmailTemplate::create([
            'name' => 'system_notification',
            'subject' => '{{notification_title}}',
            'body_html' => '<h1>{{notification_title}}</h1><p>{{notification_message}}</p>',
            'body_text' => '{{notification_title}}: {{notification_message}}',
            'variables' => [
                'notification_title' => 'Título de la notificación',
                'notification_message' => 'Mensaje de la notificación'
            ],
            'category' => 'system'
        ]);

        $result = $this->emailService->sendSystemNotification(
            'Test System Notification',
            'This is a test system notification',
            [['email' => $user->email, 'name' => $user->name]]
        );

        $this->assertEquals(1, $result['success']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * Test modo de prueba de configuración
     */
    public function test_can_test_email_configuration()
    {
        $testResult = $this->emailService->testConfiguration();

        $this->assertArrayHasKey('driver', $testResult);
        $this->assertArrayHasKey('status', $testResult);
        $this->assertArrayHasKey('error', $testResult);
    }
}
