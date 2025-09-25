<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ExternalSmsService
{
    protected $provider;
    protected $apiKey;
    protected $apiSecret;
    protected $fromNumber;
    protected $timeout;

    public function __construct()
    {
        $this->provider = config('sms.provider', 'twilio');
        $this->apiKey = config('sms.api_key');
        $this->apiSecret = config('sms.api_secret');
        $this->fromNumber = config('sms.from_number');
        $this->timeout = config('sms.timeout', 30);
    }

    /**
     * Enviar SMS simple
     */
    public function sendSms(string $to, string $message, array $options = []): array
    {
        try {
            $smsData = [
                'to' => $this->formatPhoneNumber($to),
                'message' => $message,
                'from' => $options['from'] ?? $this->fromNumber,
                'priority' => $options['priority'] ?? 'normal',
                'delivery_report' => $options['delivery_report'] ?? false
            ];

            $result = $this->sendViaProvider($smsData);

            if ($result['success']) {
                $this->logSmsSent($smsData, $result);
                $this->updateSmsStats(true);
            } else {
                $this->logSmsError($smsData, $result['error']);
                $this->updateSmsStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logSmsError(['to' => $to, 'message' => $message], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar SMS masivo
     */
    public function sendBulkSms(array $recipients, string $message, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->sendSms(
                $recipient['phone'],
                $message,
                array_merge($options, [
                    'from' => $recipient['from'] ?? $options['from'] ?? $this->fromNumber
                ])
            );

            $results[] = [
                'phone' => $recipient['phone'],
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
                'message_id' => $result['message_id'] ?? null
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
     * Enviar SMS con plantilla
     */
    public function sendTemplateSms(string $to, string $template, array $data = [], array $options = []): array
    {
        $message = $this->renderTemplate($template, $data);
        return $this->sendSms($to, $message, $options);
    }

    /**
     * Verificar estado del servicio de SMS
     */
    public function checkHealth(): array
    {
        try {
            $testPhone = '+1234567890';
            $result = $this->sendSms($testPhone, 'Health check SMS');

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
     * Obtener estadísticas de SMS
     */
    public function getStats(): array
    {
        $stats = Cache::get('sms_stats', [
            'total_sms' => 0,
            'successful_sms' => 0,
            'failed_sms' => 0,
            'last_sms' => null,
            'providers_used' => []
        ]);

        return $stats;
    }

    /**
     * Configurar proveedor de SMS
     */
    public function configure(string $provider, string $apiKey, string $apiSecret, string $fromNumber): void
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->fromNumber = $fromNumber;
    }

    /**
     * Enviar SMS via proveedor
     */
    protected function sendViaProvider(array $smsData): array
    {
        switch ($this->provider) {
            case 'twilio':
                return $this->sendViaTwilio($smsData);
            case 'nexmo':
                return $this->sendViaNexmo($smsData);
            case 'aws_sns':
                return $this->sendViaAwsSns($smsData);
            case 'messagebird':
                return $this->sendViaMessageBird($smsData);
            default:
                return $this->sendViaTwilio($smsData);
        }
    }

    /**
     * Enviar SMS via Twilio
     */
    protected function sendViaTwilio(array $smsData): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->apiKey}/Messages.json", [
                    'From' => $smsData['from'],
                    'To' => $smsData['to'],
                    'Body' => $smsData['message']
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'provider' => 'twilio',
                    'message_id' => $data['sid'],
                    'status' => $data['status']
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar SMS via Nexmo
     */
    protected function sendViaNexmo(array $smsData): array
    {
        try {
            $response = Http::post('https://rest.nexmo.com/sms/json', [
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
                'to' => $smsData['to'],
                'from' => $smsData['from'],
                'text' => $smsData['message']
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'provider' => 'nexmo',
                    'message_id' => $data['messages'][0]['message-id'] ?? null,
                    'status' => $data['messages'][0]['status'] ?? 'unknown'
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar SMS via AWS SNS
     */
    protected function sendViaAwsSns(array $smsData): array
    {
        try {
            // Implementar AWS SNS
            return ['success' => true, 'provider' => 'aws_sns'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar SMS via MessageBird
     */
    protected function sendViaMessageBird(array $smsData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'AccessKey ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://rest.messagebird.com/messages', [
                'originator' => $smsData['from'],
                'recipients' => $smsData['to'],
                'body' => $smsData['message']
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'provider' => 'messagebird',
                    'message_id' => $data['id'],
                    'status' => $data['status']
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Formatear número de teléfono
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remover caracteres no numéricos excepto +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Agregar + si no está presente
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Renderizar plantilla
     */
    protected function renderTemplate(string $template, array $data): string
    {
        $templates = [
            'welcome' => 'Bienvenido a ModuStack ElyMar Luxury! Tu código de verificación es: {code}',
            'verification' => 'Tu código de verificación es: {code}. Válido por 10 minutos.',
            'notification' => 'Notificación: {message}',
            'alert' => 'ALERTA: {message}',
            'maintenance' => 'Mantenimiento programado: {message}'
        ];

        $message = $templates[$template] ?? $template;

        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Log de SMS enviado
     */
    protected function logSmsSent(array $smsData, array $result): void
    {
        Log::info('SMS sent successfully', [
            'to' => $smsData['to'],
            'provider' => $result['provider'] ?? $this->provider,
            'message_id' => $result['message_id'] ?? null
        ]);
    }

    /**
     * Log de error de SMS
     */
    protected function logSmsError(array $smsData, string $error): void
    {
        Log::error('SMS sending failed', [
            'to' => $smsData['to'],
            'error' => $error
        ]);
    }

    /**
     * Actualizar estadísticas de SMS
     */
    protected function updateSmsStats(bool $success): void
    {
        $stats = Cache::get('sms_stats', [
            'total_sms' => 0,
            'successful_sms' => 0,
            'failed_sms' => 0,
            'last_sms' => null,
            'providers_used' => []
        ]);

        $stats['total_sms']++;
        $stats['last_sms'] = now()->toISOString();

        if ($success) {
            $stats['successful_sms']++;
        } else {
            $stats['failed_sms']++;
        }

        if (!in_array($this->provider, $stats['providers_used'])) {
            $stats['providers_used'][] = $this->provider;
        }

        Cache::put('sms_stats', $stats, 86400);
    }
}

