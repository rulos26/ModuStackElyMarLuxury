<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ExternalApiService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;
    protected $retryAttempts;

    public function __construct()
    {
        $this->baseUrl = config('services.external_api.base_url', 'https://api.example.com');
        $this->apiKey = config('services.external_api.api_key');
        $this->timeout = config('services.external_api.timeout', 30);
        $this->retryAttempts = config('services.external_api.retry_attempts', 3);
    }

    /**
     * Realizar petición GET a API externa
     */
    public function get(string $endpoint, array $params = [], array $headers = []): array
    {
        return $this->makeRequest('GET', $endpoint, $params, $headers);
    }

    /**
     * Realizar petición POST a API externa
     */
    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeRequest('POST', $endpoint, $data, $headers);
    }

    /**
     * Realizar petición PUT a API externa
     */
    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeRequest('PUT', $endpoint, $data, $headers);
    }

    /**
     * Realizar petición DELETE a API externa
     */
    public function delete(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeRequest('DELETE', $endpoint, $data, $headers);
    }

    /**
     * Realizar petición HTTP con reintentos
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $attempt = 0;
        $lastError = null;

        // Headers por defecto
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'ModuStackElyMarLuxury/1.0'
        ];

        $headers = array_merge($defaultHeaders, $headers);

        while ($attempt < $this->retryAttempts) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders($headers)
                    ->$method($url, $data);

                if ($response->successful()) {
                    $this->logRequest($method, $url, $data, $response->status(), $response->body());
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'status' => $response->status(),
                        'headers' => $response->headers()
                    ];
                }

                $lastError = "HTTP {$response->status()}: {$response->body()}";
                $attempt++;

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                $attempt++;

                if ($attempt < $this->retryAttempts) {
                    $delay = pow(2, $attempt) * 1000; // Backoff exponencial
                    usleep($delay * 1000);
                }
            }
        }

        $this->logError($method, $url, $data, $lastError);

        return [
            'success' => false,
            'error' => $lastError,
            'attempts' => $attempt
        ];
    }

    /**
     * Obtener datos con cache
     */
    public function getCached(string $endpoint, array $params = [], int $ttl = 3600): array
    {
        $cacheKey = 'external_api_' . md5($endpoint . serialize($params));

        return Cache::remember($cacheKey, $ttl, function () use ($endpoint, $params) {
            return $this->get($endpoint, $params);
        });
    }

    /**
     * Enviar datos a webhook
     */
    public function sendWebhook(string $url, array $data, array $headers = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($url, $data);

            $this->logRequest('POST', $url, $data, $response->status(), $response->body());

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json()
            ];

        } catch (\Exception $e) {
            $this->logError('POST', $url, $data, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar estado de API externa
     */
    public function checkHealth(): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/health');

            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $response->transferStats?->getHandlerStat('total_time') ?? 0,
                'status_code' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas de API
     */
    public function getStats(): array
    {
        $stats = Cache::get('external_api_stats', [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'average_response_time' => 0,
            'last_request' => null
        ]);

        return $stats;
    }

    /**
     * Limpiar cache de API
     */
    public function clearCache(): bool
    {
        try {
            $keys = Cache::get('external_api_cache_keys', []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            Cache::forget('external_api_cache_keys');

            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing API cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Configurar API externa
     */
    public function configure(string $baseUrl, string $apiKey, int $timeout = 30, int $retryAttempts = 3): void
    {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
    }

    /**
     * Obtener información de la API
     */
    public function getApiInfo(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retryAttempts,
            'has_api_key' => !empty($this->apiKey)
        ];
    }

    /**
     * Log de petición exitosa
     */
    protected function logRequest(string $method, string $url, array $data, int $status, string $response): void
    {
        Log::info('External API request successful', [
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'data_size' => strlen($response)
        ]);

        $this->updateStats(true);
    }

    /**
     * Log de error
     */
    protected function logError(string $method, string $url, array $data, string $error): void
    {
        Log::error('External API request failed', [
            'method' => $method,
            'url' => $url,
            'error' => $error
        ]);

        $this->updateStats(false);
    }

    /**
     * Actualizar estadísticas
     */
    protected function updateStats(bool $success): void
    {
        $stats = Cache::get('external_api_stats', [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'average_response_time' => 0,
            'last_request' => null
        ]);

        $stats['total_requests']++;
        $stats['last_request'] = now()->toISOString();

        if ($success) {
            $stats['successful_requests']++;
        } else {
            $stats['failed_requests']++;
        }

        Cache::put('external_api_stats', $stats, 86400);
    }
}



