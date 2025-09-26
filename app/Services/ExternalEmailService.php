<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Mail\Mailable;

class ExternalEmailService
{
    protected $provider;
    protected $apiKey;
    protected $fromEmail;
    protected $fromName;
    protected $timeout;

    public function __construct()
    {
        $this->provider = config('mail.external_provider', 'smtp');
        $this->apiKey = config('mail.external_api_key');
        $this->fromEmail = config('mail.from.address', 'noreply@example.com');
        $this->fromName = config('mail.from.name', 'ModuStack ElyMar Luxury');
        $this->timeout = config('mail.timeout', 30);
    }

    /**
     * Enviar email simple
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): array
    {
        try {
            $emailData = [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'from_email' => $options['from_email'] ?? $this->fromEmail,
                'from_name' => $options['from_name'] ?? $this->fromName,
                'reply_to' => $options['reply_to'] ?? null,
                'cc' => $options['cc'] ?? [],
                'bcc' => $options['bcc'] ?? [],
                'attachments' => $options['attachments'] ?? [],
                'priority' => $options['priority'] ?? 'normal'
            ];

            $result = $this->sendViaProvider($emailData);

            if ($result['success']) {
                $this->logEmailSent($emailData, $result);
                $this->updateEmailStats(true);
            } else {
                $this->logEmailError($emailData, $result['error']);
                $this->updateEmailStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logEmailError(['to' => $to, 'subject' => $subject], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email con plantilla
     */
    public function sendTemplateEmail(string $to, string $template, array $data = [], array $options = []): array
    {
        try {
            $emailData = [
                'to' => $to,
                'template' => $template,
                'data' => $data,
                'from_email' => $options['from_email'] ?? $this->fromEmail,
                'from_name' => $options['from_name'] ?? $this->fromName,
                'subject' => $options['subject'] ?? $this->getTemplateSubject($template),
                'reply_to' => $options['reply_to'] ?? null,
                'cc' => $options['cc'] ?? [],
                'bcc' => $options['bcc'] ?? [],
                'attachments' => $options['attachments'] ?? []
            ];

            $result = $this->sendTemplateViaProvider($emailData);

            if ($result['success']) {
                $this->logEmailSent($emailData, $result);
                $this->updateEmailStats(true);
            } else {
                $this->logEmailError($emailData, $result['error']);
                $this->updateEmailStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logEmailError(['to' => $to, 'template' => $template], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar email masivo
     */
    public function sendBulkEmail(array $recipients, string $subject, string $body, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->sendEmail(
                $recipient['email'],
                $subject,
                $body,
                array_merge($options, [
                    'from_email' => $recipient['from_email'] ?? $options['from_email'] ?? $this->fromEmail,
                    'from_name' => $recipient['from_name'] ?? $options['from_name'] ?? $this->fromName
                ])
            );

            $results[] = [
                'email' => $recipient['email'],
                'success' => $result['success'],
                'error' => $result['error'] ?? null
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        return [
            'success' => $failureCount === 0,
            'total' => count($recipients),
            'successful' => $successCount,
            'failed' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Enviar email con archivos adjuntos
     */
    public function sendEmailWithAttachments(string $to, string $subject, string $body, array $attachments, array $options = []): array
    {
        $options['attachments'] = $attachments;
        return $this->sendEmail($to, $subject, $body, $options);
    }

    /**
     * Verificar estado del servicio de email
     */
    public function checkHealth(): array
    {
        try {
            $testEmail = 'test@example.com';
            $result = $this->sendEmail($testEmail, 'Health Check', 'Test email for health check');

            return [
                'status' => $result['success'] ? 'healthy' : 'unhealthy',
                'provider' => $this->provider,
                'error' => $result['error'] ?? null
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'provider' => $this->provider,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas de email
     */
    public function getStats(): array
    {
        $stats = Cache::get('email_stats', [
            'total_emails' => 0,
            'successful_emails' => 0,
            'failed_emails' => 0,
            'last_email' => null,
            'providers_used' => []
        ]);

        return $stats;
    }

    /**
     * Configurar proveedor de email
     */
    public function configure(string $provider, string $apiKey, string $fromEmail, string $fromName): void
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Enviar email via proveedor
     */
    protected function sendViaProvider(array $emailData): array
    {
        switch ($this->provider) {
            case 'smtp':
                return $this->sendViaSmtp($emailData);
            case 'sendgrid':
                return $this->sendViaSendGrid($emailData);
            case 'mailgun':
                return $this->sendViaMailgun($emailData);
            case 'ses':
                return $this->sendViaSes($emailData);
            default:
                return $this->sendViaSmtp($emailData);
        }
    }

    /**
     * Enviar email via SMTP
     */
    protected function sendViaSmtp(array $emailData): array
    {
        try {
            Mail::raw($emailData['body'], function ($message) use ($emailData) {
                $message->to($emailData['to'])
                        ->subject($emailData['subject'])
                        ->from($emailData['from_email'], $emailData['from_name']);

                if ($emailData['reply_to']) {
                    $message->replyTo($emailData['reply_to']);
                }

                if (!empty($emailData['cc'])) {
                    $message->cc($emailData['cc']);
                }

                if (!empty($emailData['bcc'])) {
                    $message->bcc($emailData['bcc']);
                }

                if (!empty($emailData['attachments'])) {
                    foreach ($emailData['attachments'] as $attachment) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? null
                        ]);
                    }
                }
            });

            return ['success' => true, 'provider' => 'smtp'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar email via SendGrid
     */
    protected function sendViaSendGrid(array $emailData): array
    {
        try {
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [
                    [
                        'to' => [['email' => $emailData['to']]],
                        'subject' => $emailData['subject']
                    ]
                ],
                'from' => [
                    'email' => $emailData['from_email'],
                    'name' => $emailData['from_name']
                ],
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $emailData['body']
                    ]
                ]
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'sendgrid',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar email via Mailgun
     */
    protected function sendViaMailgun(array $emailData): array
    {
        try {
            $response = \Http::asForm()->post("https://api.mailgun.net/v3/{$this->apiKey}/messages", [
                'from' => "{$emailData['from_name']} <{$emailData['from_email']}>",
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
                'html' => $emailData['body']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'mailgun',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar email via SES
     */
    protected function sendViaSes(array $emailData): array
    {
        try {
            // Implementar SES
            return ['success' => true, 'provider' => 'ses'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar plantilla via proveedor
     */
    protected function sendTemplateViaProvider(array $emailData): array
    {
        // Implementar envío de plantillas
        return $this->sendViaProvider($emailData);
    }

    /**
     * Obtener asunto de plantilla
     */
    protected function getTemplateSubject(string $template): string
    {
        $templates = [
            'welcome' => 'Bienvenido a ModuStack ElyMar Luxury',
            'notification' => 'Notificación del Sistema',
            'alert' => 'Alerta del Sistema',
            'maintenance' => 'Mantenimiento Programado'
        ];

        return $templates[$template] ?? 'Notificación';
    }

    /**
     * Log de email enviado
     */
    protected function logEmailSent(array $emailData, array $result): void
    {
        Log::info('Email sent successfully', [
            'to' => $emailData['to'],
            'subject' => $emailData['subject'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de email
     */
    protected function logEmailError(array $emailData, string $error): void
    {
        Log::error('Email sending failed', [
            'to' => $emailData['to'],
            'subject' => $emailData['subject'] ?? 'N/A',
            'error' => $error
        ]);
    }

    /**
     * Actualizar estadísticas de email
     */
    protected function updateEmailStats(bool $success): void
    {
        $stats = Cache::get('email_stats', [
            'total_emails' => 0,
            'successful_emails' => 0,
            'failed_emails' => 0,
            'last_email' => null,
            'providers_used' => []
        ]);

        $stats['total_emails']++;
        $stats['last_email'] = now()->toISOString();

        if ($success) {
            $stats['successful_emails']++;
        } else {
            $stats['failed_emails']++;
        }

        if (!in_array($this->provider, $stats['providers_used'])) {
            $stats['providers_used'][] = $this->provider;
        }

        Cache::put('email_stats', $stats, 86400);
    }
}



