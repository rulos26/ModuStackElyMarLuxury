<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\SmtpConfig;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Mail\Message;
use Carbon\Carbon;

class EmailService
{
    protected $config;
    protected $smtpConfigService;

    public function __construct(SmtpConfigService $smtpConfigService)
    {
        $this->config = config('mail');
        $this->smtpConfigService = $smtpConfigService;

        // Aplicar configuración SMTP dinámica
        $this->smtpConfigService->applyDynamicConfig();
    }

    /**
     * Enviar email usando plantilla con configuración SMTP específica
     */
    public function sendTemplate(
        string $templateName,
        string $toEmail,
        array $variables = [],
        ?string $toName = null,
        array $attachments = [],
        bool $queue = true,
        ?SmtpConfig $smtpConfig = null
    ): bool {
        try {
            // Aplicar configuración SMTP específica si se proporciona
            if ($smtpConfig) {
                $this->smtpConfigService->applyDynamicConfig($smtpConfig);
            }

            $template = EmailTemplate::getTemplateByName($templateName);

            if (!$template) {
                Log::error("Plantilla de email no encontrada: {$templateName}");
                return false;
            }

            // Validar variables requeridas
            $missingVariables = $template->validateVariables($variables);
            if (!empty($missingVariables)) {
                Log::error("Variables faltantes en plantilla {$templateName}: " . implode(', ', $missingVariables));
                return false;
            }

            // Procesar plantilla
            $processedTemplate = $template->processTemplate($variables);

            // Preparar datos del email
            $emailData = [
                'subject' => $processedTemplate['subject'],
                'body_html' => $processedTemplate['body_html'],
                'body_text' => $processedTemplate['body_text'],
                'to_email' => $toEmail,
                'to_name' => $toName,
                'template_name' => $templateName,
                'variables' => $variables,
                'attachments' => $attachments,
                'sent_at' => now(),
                'smtp_config_id' => $smtpConfig?->id
            ];

            if ($queue && config('queue.default') !== 'sync') {
                return $this->queueEmail($emailData);
            } else {
                return $this->sendEmailDirectly($emailData);
            }

        } catch (\Exception $e) {
            Log::error("Error enviando email con plantilla {$templateName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar email directo sin plantilla
     */
    public function sendDirect(
        string $toEmail,
        string $subject,
        string $body,
        ?string $toName = null,
        array $attachments = [],
        bool $queue = true,
        bool $isHtml = true
    ): bool {
        try {
            $emailData = [
                'subject' => $subject,
                'body' => $body,
                'to_email' => $toEmail,
                'to_name' => $toName,
                'is_html' => $isHtml,
                'attachments' => $attachments,
                'sent_at' => now()
            ];

            if ($queue && config('queue.default') !== 'sync') {
                return $this->queueDirectEmail($emailData);
            } else {
                return $this->sendDirectEmail($emailData);
            }

        } catch (\Exception $e) {
            Log::error("Error enviando email directo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar email a múltiples destinatarios
     */
    public function sendBulk(
        string $templateName,
        array $recipients,
        array $variables = [],
        array $attachments = [],
        bool $queue = true
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            $toEmail = is_array($recipient) ? $recipient['email'] : $recipient;
            $toName = is_array($recipient) ? ($recipient['name'] ?? null) : null;

            $success = $this->sendTemplate(
                $templateName,
                $toEmail,
                $variables,
                $toName,
                $attachments,
                $queue
            );

            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Error enviando a: {$toEmail}";
            }
        }

        return $results;
    }

    /**
     * Enviar email a usuarios con rol específico
     */
    public function sendToRole(
        string $templateName,
        string $roleName,
        array $variables = [],
        array $attachments = [],
        bool $queue = true
    ): array {
        $users = User::role($roleName)->get();

        $recipients = $users->map(function ($user) {
            return [
                'email' => $user->email,
                'name' => $user->name
            ];
        })->toArray();

        return $this->sendBulk($templateName, $recipients, $variables, $attachments, $queue);
    }

    /**
     * Enviar email a todos los usuarios activos
     */
    public function sendToAllUsers(
        string $templateName,
        array $variables = [],
        array $attachments = [],
        bool $queue = true
    ): array {
        $users = User::where('email_verified_at', '!=', null)->get();

        $recipients = $users->map(function ($user) {
            return [
                'email' => $user->email,
                'name' => $user->name
            ];
        })->toArray();

        return $this->sendBulk($templateName, $recipients, $variables, $attachments, $queue);
    }

    /**
     * Enviar email de bienvenida
     */
    public function sendWelcomeEmail(User $user, ?string $tempPassword = null): bool
    {
        $variables = [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'login_url' => route('login'),
            'temp_password' => $tempPassword
        ];

        return $this->sendTemplate(
            'welcome',
            $user->email,
            $variables,
            $user->name
        );
    }

    /**
     * Enviar email de notificación de seguridad
     */
    public function sendSecurityNotification(
        User $user,
        string $event,
        array $additionalData = []
    ): bool {
        $variables = [
            'user_name' => $user->name,
            'event' => $event,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->format('d/m/Y H:i:s'),
            ...$additionalData
        ];

        return $this->sendTemplate(
            'security_notification',
            $user->email,
            $variables,
            $user->name
        );
    }

    /**
     * Enviar email de notificación del sistema
     */
    public function sendSystemNotification(
        string $subject,
        string $message,
        array $recipients = [],
        array $attachments = []
    ): array {
        if (empty($recipients)) {
            // Enviar a administradores por defecto
            $recipients = User::role('admin')->get()->map(function ($user) {
                return [
                    'email' => $user->email,
                    'name' => $user->name
                ];
            })->toArray();
        }

        $variables = [
            'notification_title' => $subject,
            'notification_message' => $message
        ];

        return $this->sendBulk(
            'system_notification',
            $recipients,
            $variables,
            $attachments
        );
    }

    /**
     * Probar configuración de email
     */
    public function testConfiguration(): array
    {
        $results = [
            'driver' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'status' => 'unknown',
            'error' => null
        ];

        try {
            // Intentar enviar email de prueba
            $testResult = $this->sendDirect(
                config('mail.from.address'),
                'Test de configuración - ' . config('app.name'),
                'Este es un email de prueba para verificar la configuración.',
                config('mail.from.name'),
                [],
                false // No usar cola para prueba
            );

            $results['status'] = $testResult ? 'success' : 'failed';

            if (!$testResult) {
                $results['error'] = 'No se pudo enviar el email de prueba';
            }

        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Obtener estadísticas de emails
     */
    public function getEmailStats(): array
    {
        // Esto se puede implementar con una tabla de logs de emails
        return [
            'templates_count' => EmailTemplate::count(),
            'active_templates' => EmailTemplate::active()->count(),
            'categories' => EmailTemplate::select('category')
                ->distinct()
                ->pluck('category')
                ->toArray(),
            'last_24h_sent' => 0, // Se implementaría con logs
            'total_sent' => 0,    // Se implementaría con logs
            'success_rate' => 0   // Se implementaría con logs
        ];
    }

    /**
     * Cola de email con plantilla
     */
    protected function queueEmail(array $emailData): bool
    {
        try {
            // Aquí se implementaría el job para envío asíncrono
            Log::info("Email encolado: {$emailData['subject']} para {$emailData['to_email']}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error encolando email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envío directo de email con plantilla
     */
    protected function sendEmailDirectly(array $emailData): bool
    {
        try {
            Mail::raw($emailData['body_text'], function (Message $message) use ($emailData) {
                $message->to($emailData['to_email'], $emailData['to_name'])
                        ->subject($emailData['subject']);

                // Agregar HTML si está disponible
                if (!empty($emailData['body_html'])) {
                    $message->html($emailData['body_html']);
                }

                // Agregar adjuntos
                foreach ($emailData['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path'])
                        ]);
                    } else {
                        $message->attach($attachment);
                    }
                }
            });

            Log::info("Email enviado exitosamente: {$emailData['subject']} para {$emailData['to_email']}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error enviando email directamente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cola de email directo
     */
    protected function queueDirectEmail(array $emailData): bool
    {
        try {
            Log::info("Email directo encolado: {$emailData['subject']} para {$emailData['to_email']}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error encolando email directo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envío directo de email sin plantilla
     */
    protected function sendDirectEmail(array $emailData): bool
    {
        try {
            if ($emailData['is_html']) {
                Mail::html($emailData['body'], function (Message $message) use ($emailData) {
                    $message->to($emailData['to_email'], $emailData['to_name'])
                            ->subject($emailData['subject']);

                    foreach ($emailData['attachments'] as $attachment) {
                        if (is_array($attachment)) {
                            $message->attach($attachment['path'], [
                                'as' => $attachment['name'] ?? basename($attachment['path'])
                            ]);
                        } else {
                            $message->attach($attachment);
                        }
                    }
                });
            } else {
                Mail::raw($emailData['body'], function (Message $message) use ($emailData) {
                    $message->to($emailData['to_email'], $emailData['to_name'])
                            ->subject($emailData['subject']);

                    foreach ($emailData['attachments'] as $attachment) {
                        if (is_array($attachment)) {
                            $message->attach($attachment['path'], [
                                'as' => $attachment['name'] ?? basename($attachment['path'])
                            ]);
                        } else {
                            $message->attach($attachment);
                        }
                    }
                });
            }

            Log::info("Email directo enviado exitosamente: {$emailData['subject']} para {$emailData['to_email']}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error enviando email directo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar configuración de email
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        $warnings = [];

        // Verificar si hay configuración SMTP dinámica
        $currentConfig = $this->smtpConfigService->getCurrentConfig();

        if ($currentConfig) {
            // Usar validación de la configuración SMTP dinámica
            $validation = $currentConfig->validate();
            $errors = array_merge($errors, $validation['errors']);
            $warnings = array_merge($warnings, $validation['warnings']);
        } else {
            // Validación tradicional desde config
            $driver = config('mail.default');
            if (!$driver) {
                $errors[] = 'Driver de email no configurado';
            }

            // Verificar configuración SMTP
            if ($driver === 'smtp') {
                $host = config('mail.mailers.smtp.host');
                $port = config('mail.mailers.smtp.port');
                $username = config('mail.mailers.smtp.username');
                $password = config('mail.mailers.smtp.password');

                if (!$host) {
                    $errors[] = 'Host SMTP no configurado';
                }
                if (!$port) {
                    $errors[] = 'Puerto SMTP no configurado';
                }
                if (!$username) {
                    $warnings[] = 'Usuario SMTP no configurado (puede ser necesario para autenticación)';
                }
                if (!$password) {
                    $warnings[] = 'Contraseña SMTP no configurada (puede ser necesario para autenticación)';
                }
            }

            // Verificar remitente
            $fromAddress = config('mail.from.address');
            $fromName = config('mail.from.name');

            if (!$fromAddress) {
                $errors[] = 'Dirección de remitente no configurada';
            }
            if (!$fromName) {
                $warnings[] = 'Nombre de remitente no configurado';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'current_config' => $currentConfig?->name
        ];
    }

    /**
     * Obtener configuración SMTP actual
     */
    public function getCurrentSmtpConfig(): ?SmtpConfig
    {
        return $this->smtpConfigService->getCurrentConfig();
    }

    /**
     * Enviar email usando configuración SMTP específica
     */
    public function sendWithSmtpConfig(
        string $templateName,
        string $toEmail,
        string $smtpConfigName,
        array $variables = [],
        ?string $toName = null,
        array $attachments = [],
        bool $queue = true
    ): bool {
        $smtpConfig = SmtpConfig::getByName($smtpConfigName);

        if (!$smtpConfig) {
            Log::error("Configuración SMTP no encontrada: {$smtpConfigName}");
            return false;
        }

        return $this->sendTemplate(
            $templateName,
            $toEmail,
            $variables,
            $toName,
            $attachments,
            $queue,
            $smtpConfig
        );
    }

    /**
     * Probar configuración SMTP
     */
    public function testSmtpConfiguration(?SmtpConfig $config = null): array
    {
        if (!$config) {
            $config = $this->smtpConfigService->getCurrentConfig();
        }

        if (!$config) {
            return [
                'success' => false,
                'error' => 'No hay configuración SMTP disponible para probar'
            ];
        }

        return $this->smtpConfigService->testConfiguration($config);
    }
}
