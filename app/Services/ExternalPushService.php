<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ExternalPushService
{
    protected $provider;
    protected $apiKey;
    protected $apiSecret;
    protected $timeout;

    public function __construct()
    {
        $this->provider = config('push.provider', 'fcm');
        $this->apiKey = config('push.api_key');
        $this->apiSecret = config('push.api_secret');
        $this->timeout = config('push.timeout', 30);
    }

    /**
     * Enviar notificación push
     */
    public function sendPush(string $to, string $title, string $body, array $options = []): array
    {
        try {
            $pushData = [
                'to' => $to,
                'title' => $title,
                'body' => $body,
                'data' => $options['data'] ?? [],
                'icon' => $options['icon'] ?? null,
                'sound' => $options['sound'] ?? 'default',
                'badge' => $options['badge'] ?? null,
                'priority' => $options['priority'] ?? 'normal',
                'ttl' => $options['ttl'] ?? 3600
            ];

            $result = $this->sendViaProvider($pushData);

            if ($result['success']) {
                $this->logPushSent($pushData, $result);
                $this->updatePushStats(true);
            } else {
                $this->logPushError($pushData, $result['error']);
                $this->updatePushStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logPushError(['to' => $to, 'title' => $title], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar notificación push masiva
     */
    public function sendBulkPush(array $recipients, string $title, string $body, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            $result = $this->sendPush(
                $recipient['token'],
                $title,
                $body,
                array_merge($options, [
                    'data' => array_merge($options['data'] ?? [], $recipient['data'] ?? [])
                ])
            );

            $results[] = [
                'token' => $recipient['token'],
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
     * Enviar notificación push a topic
     */
    public function sendToTopic(string $topic, string $title, string $body, array $options = []): array
    {
        try {
            $pushData = [
                'topic' => $topic,
                'title' => $title,
                'body' => $body,
                'data' => $options['data'] ?? [],
                'icon' => $options['icon'] ?? null,
                'sound' => $options['sound'] ?? 'default',
                'badge' => $options['badge'] ?? null,
                'priority' => $options['priority'] ?? 'normal',
                'ttl' => $options['ttl'] ?? 3600
            ];

            $result = $this->sendToTopicViaProvider($pushData);

            if ($result['success']) {
                $this->logPushSent($pushData, $result);
                $this->updatePushStats(true);
            } else {
                $this->logPushError($pushData, $result['error']);
                $this->updatePushStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logPushError(['topic' => $topic, 'title' => $title], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Suscribir dispositivo a topic
     */
    public function subscribeToTopic(string $token, string $topic): array
    {
        try {
            $result = $this->subscribeViaProvider($token, $topic);

            if ($result['success']) {
                $this->logSubscription($token, $topic, 'subscribed');
            } else {
                $this->logSubscriptionError($token, $topic, $result['error']);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logSubscriptionError($token, $topic, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Desuscribir dispositivo de topic
     */
    public function unsubscribeFromTopic(string $token, string $topic): array
    {
        try {
            $result = $this->unsubscribeViaProvider($token, $topic);

            if ($result['success']) {
                $this->logSubscription($token, $topic, 'unsubscribed');
            } else {
                $this->logSubscriptionError($token, $topic, $result['error']);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logSubscriptionError($token, $topic, $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar estado del servicio de push
     */
    public function checkHealth(): array
    {
        try {
            $testToken = 'test_token_123';
            $result = $this->sendPush($testToken, 'Health Check', 'Test push notification');

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
     * Obtener estadísticas de push
     */
    public function getStats(): array
    {
        $stats = Cache::get('push_stats', [
            'total_push' => 0,
            'successful_push' => 0,
            'failed_push' => 0,
            'last_push' => null,
            'providers_used' => []
        ]);

        return $stats;
    }

    /**
     * Configurar proveedor de push
     */
    public function configure(string $provider, string $apiKey, string $apiSecret = null): void
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Enviar push via proveedor
     */
    protected function sendViaProvider(array $pushData): array
    {
        switch ($this->provider) {
            case 'fcm':
                return $this->sendViaFcm($pushData);
            case 'apns':
                return $this->sendViaApns($pushData);
            case 'onesignal':
                return $this->sendViaOneSignal($pushData);
            case 'pusher':
                return $this->sendViaPusher($pushData);
            default:
                return $this->sendViaFcm($pushData);
        }
    }

    /**
     * Enviar push via FCM
     */
    protected function sendViaFcm(array $pushData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $pushData['to'],
                'notification' => [
                    'title' => $pushData['title'],
                    'body' => $pushData['body'],
                    'icon' => $pushData['icon'],
                    'sound' => $pushData['sound'],
                    'badge' => $pushData['badge']
                ],
                'data' => $pushData['data'],
                'priority' => $pushData['priority'],
                'ttl' => $pushData['ttl']
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'provider' => 'fcm',
                    'message_id' => $data['message_id'] ?? null,
                    'status' => $data['success'] ? 'sent' : 'failed'
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
     * Enviar push via APNS
     */
    protected function sendViaApns(array $pushData): array
    {
        try {
            // Implementar APNS
            return ['success' => true, 'provider' => 'apns'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar push via OneSignal
     */
    protected function sendViaOneSignal(array $pushData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $this->apiSecret,
                'include_player_ids' => [$pushData['to']],
                'headings' => ['en' => $pushData['title']],
                'contents' => ['en' => $pushData['body']],
                'data' => $pushData['data']
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'provider' => 'onesignal',
                    'message_id' => $data['id'],
                    'status' => 'sent'
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
     * Enviar push via Pusher
     */
    protected function sendViaPusher(array $pushData): array
    {
        try {
            // Implementar Pusher
            return ['success' => true, 'provider' => 'pusher'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar a topic via proveedor
     */
    protected function sendToTopicViaProvider(array $pushData): array
    {
        switch ($this->provider) {
            case 'fcm':
                return $this->sendToTopicViaFcm($pushData);
            case 'onesignal':
                return $this->sendToTopicViaOneSignal($pushData);
            default:
                return $this->sendToTopicViaFcm($pushData);
        }
    }

    /**
     * Enviar a topic via FCM
     */
    protected function sendToTopicViaFcm(array $pushData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => '/topics/' . $pushData['topic'],
                'notification' => [
                    'title' => $pushData['title'],
                    'body' => $pushData['body'],
                    'icon' => $pushData['icon'],
                    'sound' => $pushData['sound'],
                    'badge' => $pushData['badge']
                ],
                'data' => $pushData['data']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'fcm',
                'status' => $response->successful() ? 'sent' : 'failed'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar a topic via OneSignal
     */
    protected function sendToTopicViaOneSignal(array $pushData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $this->apiSecret,
                'included_segments' => [$pushData['topic']],
                'headings' => ['en' => $pushData['title']],
                'contents' => ['en' => $pushData['body']],
                'data' => $pushData['data']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'onesignal',
                'status' => $response->successful() ? 'sent' : 'failed'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Suscribir via proveedor
     */
    protected function subscribeViaProvider(string $token, string $topic): array
    {
        switch ($this->provider) {
            case 'fcm':
                return $this->subscribeViaFcm($token, $topic);
            default:
                return ['success' => true, 'provider' => $this->provider];
        }
    }

    /**
     * Suscribir via FCM
     */
    protected function subscribeViaFcm(string $token, string $topic): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://iid.googleapis.com/iid/v1/' . $token . '/rel/topics/' . $topic);

            return [
                'success' => $response->successful(),
                'provider' => 'fcm'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Desuscribir via proveedor
     */
    protected function unsubscribeViaProvider(string $token, string $topic): array
    {
        switch ($this->provider) {
            case 'fcm':
                return $this->unsubscribeViaFcm($token, $topic);
            default:
                return ['success' => true, 'provider' => $this->provider];
        }
    }

    /**
     * Desuscribir via FCM
     */
    protected function unsubscribeViaFcm(string $token, string $topic): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->delete('https://iid.googleapis.com/iid/v1/' . $token . '/rel/topics/' . $topic);

            return [
                'success' => $response->successful(),
                'provider' => 'fcm'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log de push enviado
     */
    protected function logPushSent(array $pushData, array $result): void
    {
        Log::info('Push notification sent successfully', [
            'to' => $pushData['to'] ?? $pushData['topic'] ?? 'N/A',
            'title' => $pushData['title'],
            'provider' => $result['provider'] ?? $this->provider,
            'message_id' => $result['message_id'] ?? null
        ]);
    }

    /**
     * Log de error de push
     */
    protected function logPushError(array $pushData, string $error): void
    {
        Log::error('Push notification failed', [
            'to' => $pushData['to'] ?? $pushData['topic'] ?? 'N/A',
            'title' => $pushData['title'],
            'error' => $error
        ]);
    }

    /**
     * Log de suscripción
     */
    protected function logSubscription(string $token, string $topic, string $action): void
    {
        Log::info("Device {$action} to topic", [
            'token' => $token,
            'topic' => $topic,
            'action' => $action
        ]);
    }

    /**
     * Log de error de suscripción
     */
    protected function logSubscriptionError(string $token, string $topic, string $error): void
    {
        Log::error('Subscription failed', [
            'token' => $token,
            'topic' => $topic,
            'error' => $error
        ]);
    }

    /**
     * Actualizar estadísticas de push
     */
    protected function updatePushStats(bool $success): void
    {
        $stats = Cache::get('push_stats', [
            'total_push' => 0,
            'successful_push' => 0,
            'failed_push' => 0,
            'last_push' => null,
            'providers_used' => []
        ]);

        $stats['total_push']++;
        $stats['last_push'] = now()->toISOString();

        if ($success) {
            $stats['successful_push']++;
        } else {
            $stats['failed_push']++;
        }

        if (!in_array($this->provider, $stats['providers_used'])) {
            $stats['providers_used'][] = $this->provider;
        }

        Cache::put('push_stats', $stats, 86400);
    }
}

