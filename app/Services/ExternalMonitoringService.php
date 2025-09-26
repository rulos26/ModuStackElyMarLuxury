<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ExternalMonitoringService
{
    protected $provider;
    protected $apiKey;
    protected $apiSecret;
    protected $timeout;

    public function __construct()
    {
        $this->provider = config('monitoring.provider', 'datadog');
        $this->apiKey = config('monitoring.api_key');
        $this->apiSecret = config('monitoring.api_secret');
        $this->timeout = config('monitoring.timeout', 30);
    }

    /**
     * Enviar métrica
     */
    public function sendMetric(string $name, float $value, array $tags = [], array $options = []): array
    {
        try {
            $metricData = [
                'name' => $name,
                'value' => $value,
                'tags' => $tags,
                'timestamp' => $options['timestamp'] ?? time(),
                'type' => $options['type'] ?? 'gauge',
                'unit' => $options['unit'] ?? null
            ];

            $result = $this->sendMetricViaProvider($metricData);

            if ($result['success']) {
                $this->logMetricSent($metricData, $result);
                $this->updateMonitoringStats(true);
            } else {
                $this->logMetricError($metricData, $result['error']);
                $this->updateMonitoringStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logMetricError(['name' => $name, 'value' => $value], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar evento
     */
    public function sendEvent(string $title, string $text, array $tags = [], array $options = []): array
    {
        try {
            $eventData = [
                'title' => $title,
                'text' => $text,
                'tags' => $tags,
                'timestamp' => $options['timestamp'] ?? time(),
                'alert_type' => $options['alert_type'] ?? 'info',
                'priority' => $options['priority'] ?? 'normal',
                'source' => $options['source'] ?? 'modustack'
            ];

            $result = $this->sendEventViaProvider($eventData);

            if ($result['success']) {
                $this->logEventSent($eventData, $result);
                $this->updateMonitoringStats(true);
            } else {
                $this->logEventError($eventData, $result['error']);
                $this->updateMonitoringStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logEventError(['title' => $title, 'text' => $text], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar log
     */
    public function sendLog(string $message, string $level = 'info', array $tags = [], array $options = []): array
    {
        try {
            $logData = [
                'message' => $message,
                'level' => $level,
                'tags' => $tags,
                'timestamp' => $options['timestamp'] ?? time(),
                'source' => $options['source'] ?? 'modustack',
                'service' => $options['service'] ?? 'app'
            ];

            $result = $this->sendLogViaProvider($logData);

            if ($result['success']) {
                $this->logLogSent($logData, $result);
                $this->updateMonitoringStats(true);
            } else {
                $this->logLogError($logData, $result['error']);
                $this->updateMonitoringStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logLogError(['message' => $message, 'level' => $level], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar alerta
     */
    public function sendAlert(string $title, string $message, string $severity = 'warning', array $options = []): array
    {
        try {
            $alertData = [
                'title' => $title,
                'message' => $message,
                'severity' => $severity,
                'timestamp' => $options['timestamp'] ?? time(),
                'source' => $options['source'] ?? 'modustack',
                'tags' => $options['tags'] ?? []
            ];

            $result = $this->sendAlertViaProvider($alertData);

            if ($result['success']) {
                $this->logAlertSent($alertData, $result);
                $this->updateMonitoringStats(true);
            } else {
                $this->logAlertError($alertData, $result['error']);
                $this->updateMonitoringStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logAlertError(['title' => $title, 'message' => $message], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar estado del servicio de monitoreo
     */
    public function checkHealth(): array
    {
        try {
            $result = $this->sendMetric('monitoring.health_check', 1, ['service' => 'monitoring']);

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
     * Obtener estadísticas de monitoreo
     */
    public function getStats(): array
    {
        $stats = Cache::get('monitoring_stats', [
            'total_metrics' => 0,
            'total_events' => 0,
            'total_logs' => 0,
            'total_alerts' => 0,
            'successful_sends' => 0,
            'failed_sends' => 0,
            'last_send' => null,
            'providers_used' => []
        ]);

        return $stats;
    }

    /**
     * Configurar proveedor de monitoreo
     */
    public function configure(string $provider, string $apiKey, string $apiSecret = null): void
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Enviar métrica via proveedor
     */
    protected function sendMetricViaProvider(array $metricData): array
    {
        switch ($this->provider) {
            case 'datadog':
                return $this->sendMetricViaDatadog($metricData);
            case 'newrelic':
                return $this->sendMetricViaNewRelic($metricData);
            case 'prometheus':
                return $this->sendMetricViaPrometheus($metricData);
            case 'grafana':
                return $this->sendMetricViaGrafana($metricData);
            default:
                return $this->sendMetricViaDatadog($metricData);
        }
    }

    /**
     * Enviar métrica via Datadog
     */
    protected function sendMetricViaDatadog(array $metricData): array
    {
        try {
            $response = Http::withHeaders([
                'DD-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.datadoghq.com/api/v1/series', [
                'series' => [
                    [
                        'metric' => $metricData['name'],
                        'points' => [[$metricData['timestamp'], $metricData['value']]],
                        'tags' => $metricData['tags'],
                        'type' => $metricData['type']
                    ]
                ]
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'datadog',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar métrica via New Relic
     */
    protected function sendMetricViaNewRelic(array $metricData): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://metric-api.newrelic.com/metric/v1', [
                'metrics' => [
                    [
                        'name' => $metricData['name'],
                        'value' => $metricData['value'],
                        'timestamp' => $metricData['timestamp'],
                        'tags' => $metricData['tags']
                    ]
                ]
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'newrelic',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar métrica via Prometheus
     */
    protected function sendMetricViaPrometheus(array $metricData): array
    {
        try {
            // Implementar Prometheus
            return ['success' => true, 'provider' => 'prometheus'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar métrica via Grafana
     */
    protected function sendMetricViaGrafana(array $metricData): array
    {
        try {
            // Implementar Grafana
            return ['success' => true, 'provider' => 'grafana'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar evento via proveedor
     */
    protected function sendEventViaProvider(array $eventData): array
    {
        switch ($this->provider) {
            case 'datadog':
                return $this->sendEventViaDatadog($eventData);
            case 'newrelic':
                return $this->sendEventViaNewRelic($eventData);
            default:
                return $this->sendEventViaDatadog($eventData);
        }
    }

    /**
     * Enviar evento via Datadog
     */
    protected function sendEventViaDatadog(array $eventData): array
    {
        try {
            $response = Http::withHeaders([
                'DD-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.datadoghq.com/api/v1/events', [
                'title' => $eventData['title'],
                'text' => $eventData['text'],
                'tags' => $eventData['tags'],
                'alert_type' => $eventData['alert_type'],
                'priority' => $eventData['priority'],
                'source' => $eventData['source']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'datadog',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar evento via New Relic
     */
    protected function sendEventViaNewRelic(array $eventData): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://insights-collector.newrelic.com/v1/accounts/{account_id}/events', [
                'eventType' => 'CustomEvent',
                'title' => $eventData['title'],
                'text' => $eventData['text'],
                'tags' => $eventData['tags']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'newrelic',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar log via proveedor
     */
    protected function sendLogViaProvider(array $logData): array
    {
        switch ($this->provider) {
            case 'datadog':
                return $this->sendLogViaDatadog($logData);
            case 'newrelic':
                return $this->sendLogViaNewRelic($logData);
            default:
                return $this->sendLogViaDatadog($logData);
        }
    }

    /**
     * Enviar log via Datadog
     */
    protected function sendLogViaDatadog(array $logData): array
    {
        try {
            $response = Http::withHeaders([
                'DD-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://http-intake.logs.datadoghq.com/v1/input/' . $this->apiKey, [
                'message' => $logData['message'],
                'level' => $logData['level'],
                'tags' => $logData['tags'],
                'timestamp' => $logData['timestamp'],
                'source' => $logData['source'],
                'service' => $logData['service']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'datadog',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar log via New Relic
     */
    protected function sendLogViaNewRelic(array $logData): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://log-api.newrelic.com/log/v1', [
                'message' => $logData['message'],
                'level' => $logData['level'],
                'tags' => $logData['tags'],
                'timestamp' => $logData['timestamp'],
                'source' => $logData['source'],
                'service' => $logData['service']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'newrelic',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar alerta via proveedor
     */
    protected function sendAlertViaProvider(array $alertData): array
    {
        switch ($this->provider) {
            case 'datadog':
                return $this->sendAlertViaDatadog($alertData);
            case 'newrelic':
                return $this->sendAlertViaNewRelic($alertData);
            default:
                return $this->sendAlertViaDatadog($alertData);
        }
    }

    /**
     * Enviar alerta via Datadog
     */
    protected function sendAlertViaDatadog(array $alertData): array
    {
        try {
            $response = Http::withHeaders([
                'DD-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.datadoghq.com/api/v1/events', [
                'title' => $alertData['title'],
                'text' => $alertData['message'],
                'alert_type' => $alertData['severity'],
                'tags' => $alertData['tags'],
                'source' => $alertData['source']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'datadog',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar alerta via New Relic
     */
    protected function sendAlertViaNewRelic(array $alertData): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.newrelic.com/v2/alerts_policy_channels.json', [
                'title' => $alertData['title'],
                'message' => $alertData['message'],
                'severity' => $alertData['severity'],
                'tags' => $alertData['tags']
            ]);

            return [
                'success' => $response->successful(),
                'provider' => 'newrelic',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log de métrica enviada
     */
    protected function logMetricSent(array $metricData, array $result): void
    {
        Log::info('Metric sent successfully', [
            'name' => $metricData['name'],
            'value' => $metricData['value'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de métrica
     */
    protected function logMetricError(array $metricData, string $error): void
    {
        Log::error('Metric sending failed', [
            'name' => $metricData['name'],
            'error' => $error
        ]);
    }

    /**
     * Log de evento enviado
     */
    protected function logEventSent(array $eventData, array $result): void
    {
        Log::info('Event sent successfully', [
            'title' => $eventData['title'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de evento
     */
    protected function logEventError(array $eventData, string $error): void
    {
        Log::error('Event sending failed', [
            'title' => $eventData['title'],
            'error' => $error
        ]);
    }

    /**
     * Log de log enviado
     */
    protected function logLogSent(array $logData, array $result): void
    {
        Log::info('Log sent successfully', [
            'level' => $logData['level'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de log
     */
    protected function logLogError(array $logData, string $error): void
    {
        Log::error('Log sending failed', [
            'level' => $logData['level'],
            'error' => $error
        ]);
    }

    /**
     * Log de alerta enviada
     */
    protected function logAlertSent(array $alertData, array $result): void
    {
        Log::info('Alert sent successfully', [
            'title' => $alertData['title'],
            'severity' => $alertData['severity'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de alerta
     */
    protected function logAlertError(array $alertData, string $error): void
    {
        Log::error('Alert sending failed', [
            'title' => $alertData['title'],
            'error' => $error
        ]);
    }

    /**
     * Actualizar estadísticas de monitoreo
     */
    protected function updateMonitoringStats(bool $success): void
    {
        $stats = Cache::get('monitoring_stats', [
            'total_metrics' => 0,
            'total_events' => 0,
            'total_logs' => 0,
            'total_alerts' => 0,
            'successful_sends' => 0,
            'failed_sends' => 0,
            'last_send' => null,
            'providers_used' => []
        ]);

        $stats['last_send'] = now()->toISOString();

        if ($success) {
            $stats['successful_sends']++;
        } else {
            $stats['failed_sends']++;
        }

        if (!in_array($this->provider, $stats['providers_used'])) {
            $stats['providers_used'][] = $this->provider;
        }

        Cache::put('monitoring_stats', $stats, 86400);
    }
}



