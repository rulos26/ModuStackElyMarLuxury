<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ActivityLogService;
use App\Services\NotificationService;

class IntegratedLoggingMiddleware
{
    protected $activityLogService;
    protected $notificationService;

    public function __construct(
        ActivityLogService $activityLogService,
        NotificationService $notificationService
    ) {
        $this->activityLogService = $activityLogService;
        $this->notificationService = $notificationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();

        // Agregar ID de request al contexto
        $request->merge(['request_id' => $requestId]);

        try {
            // Log de inicio de request
            $this->logRequestStart($request, $requestId);

            // Procesar la request
            $response = $next($request);

            // Log de finalización de request
            $this->logRequestEnd($request, $response, $startTime, $requestId);

            return $response;

        } catch (\Exception $e) {
            // Log de error
            $this->logRequestError($request, $e, $startTime, $requestId);

            // Re-lanzar la excepción
            throw $e;
        }
    }

    /**
     * Genera un ID único para la request
     */
    protected function generateRequestId(): string
    {
        return 'req_' . uniqid() . '_' . time();
    }

    /**
     * Log del inicio de la request
     */
    protected function logRequestStart(Request $request, string $requestId): void
    {
        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'headers' => $this->getSafeHeaders($request),
            'query_params' => $request->query(),
            'request_size' => strlen($request->getContent())
        ];

        // Log estructurado
        Log::channel('daily')->info('Request started', $logData);

        // Log de actividad
        Log::info('Request started', $logData);

        // Log de métricas
        $this->logRequestMetrics($request, 'start', $logData);
    }

    /**
     * Log del final de la request
     */
    protected function logRequestEnd(Request $request, $response, float $startTime, string $requestId): void
    {
        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'execution_time' => round($executionTime, 4),
            'memory_usage' => $memoryUsage,
            'peak_memory' => $peakMemory,
            'response_size' => strlen($response->getContent()),
            'timestamp' => now()->toISOString(),
            'user_id' => $request->user()?->id
        ];

        // Log estructurado
        Log::channel('daily')->info('Request completed', $logData);

        // Log de actividad
        Log::info('Request completed', $logData);

        // Log de métricas
        $this->logRequestMetrics($request, 'end', $logData);

        // Verificar si hay problemas de rendimiento
        $this->checkPerformanceIssues($request, $logData);
    }

    /**
     * Log de error en la request
     */
    protected function logRequestError(Request $request, \Exception $e, float $startTime, string $requestId): void
    {
        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true);

        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString(),
            'execution_time' => round($executionTime, 4),
            'memory_usage' => $memoryUsage,
            'timestamp' => now()->toISOString(),
            'user_id' => $request->user()?->id
        ];

        // Log de error estructurado
        Log::channel('daily')->error('Request failed', $logData);

        // Log de actividad
        Log::error('Request failed', $logData);

        // Log de métricas
        $this->logRequestMetrics($request, 'error', $logData);

        // Enviar notificación si es crítico
        $this->handleCriticalError($request, $e, $logData);
    }

    /**
     * Obtiene headers seguros (sin información sensible)
     */
    protected function getSafeHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        $safeHeaders = [];

        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
            'x-csrf-token'
        ];

        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, $sensitiveHeaders)) {
                $safeHeaders[$key] = '[REDACTED]';
            } else {
                $safeHeaders[$key] = $value;
            }
        }

        return $safeHeaders;
    }

    /**
     * Log de métricas de la request
     */
    protected function logRequestMetrics(Request $request, string $event, array $logData): void
    {
        $metricsKey = 'request_metrics_' . date('Y-m-d');
        $metrics = Cache::get($metricsKey, [
            'total_requests' => 0,
            'total_errors' => 0,
            'total_execution_time' => 0,
            'total_memory_usage' => 0,
            'average_response_time' => 0,
            'endpoints' => [],
            'status_codes' => [],
            'user_agents' => []
        ]);

        // Actualizar métricas generales
        $metrics['total_requests']++;

        if ($event === 'error') {
            $metrics['total_errors']++;
        }

        if (isset($logData['execution_time'])) {
            $metrics['total_execution_time'] += $logData['execution_time'];
            $metrics['average_response_time'] = $metrics['total_execution_time'] / $metrics['total_requests'];
        }

        if (isset($logData['memory_usage'])) {
            $metrics['total_memory_usage'] += $logData['memory_usage'];
        }

        // Métricas por endpoint
        $endpoint = $request->path();
        if (!isset($metrics['endpoints'][$endpoint])) {
            $metrics['endpoints'][$endpoint] = [
                'count' => 0,
                'total_time' => 0,
                'errors' => 0
            ];
        }

        $metrics['endpoints'][$endpoint]['count']++;
        if (isset($logData['execution_time'])) {
            $metrics['endpoints'][$endpoint]['total_time'] += $logData['execution_time'];
        }
        if ($event === 'error') {
            $metrics['endpoints'][$endpoint]['errors']++;
        }

        // Métricas por código de estado
        if (isset($logData['status_code'])) {
            $statusCode = $logData['status_code'];
            if (!isset($metrics['status_codes'][$statusCode])) {
                $metrics['status_codes'][$statusCode] = 0;
            }
            $metrics['status_codes'][$statusCode]++;
        }

        // Métricas por user agent
        $userAgent = $request->userAgent();
        if ($userAgent) {
            $shortUserAgent = $this->getShortUserAgent($userAgent);
            if (!isset($metrics['user_agents'][$shortUserAgent])) {
                $metrics['user_agents'][$shortUserAgent] = 0;
            }
            $metrics['user_agents'][$shortUserAgent]++;
        }

        // Guardar métricas
        Cache::put($metricsKey, $metrics, 86400); // 24 horas
    }

    /**
     * Verifica problemas de rendimiento
     */
    protected function checkPerformanceIssues(Request $request, array $logData): void
    {
        $issues = [];

        // Tiempo de ejecución lento
        if ($logData['execution_time'] > 5.0) {
            $issues[] = "Tiempo de ejecución lento: {$logData['execution_time']}s";
        }

        // Uso de memoria alto
        if ($logData['memory_usage'] > 100 * 1024 * 1024) { // 100MB
            $issues[] = "Uso de memoria alto: " . $this->formatBytes($logData['memory_usage']);
        }

        // Código de estado de error
        if ($logData['status_code'] >= 400) {
            $issues[] = "Código de estado de error: {$logData['status_code']}";
        }

        // Si hay problemas, registrar y notificar
        if (!empty($issues)) {
            $this->logPerformanceIssues($request, $issues, $logData);
        }
    }

    /**
     * Log de problemas de rendimiento
     */
    protected function logPerformanceIssues(Request $request, array $issues, array $logData): void
    {
        $performanceLog = [
            'request_id' => $logData['request_id'],
            'url' => $request->fullUrl(),
            'issues' => $issues,
            'execution_time' => $logData['execution_time'],
            'memory_usage' => $logData['memory_usage'],
            'status_code' => $logData['status_code'],
            'timestamp' => now()->toISOString()
        ];

        // Log de rendimiento
        Log::channel('daily')->warning('Performance issues detected', $performanceLog);

        // Log de actividad
        Log::warning('Performance issues detected', $performanceLog);

        // Enviar notificación si es crítico
        if ($logData['execution_time'] > 10.0 || $logData['status_code'] >= 500) {
            Log::warning('Problemas de rendimiento críticos detectados', [
                'issues' => $issues,
                'execution_time' => $logData['execution_time'],
                'status_code' => $logData['status_code']
            ]);
        }
    }

    /**
     * Maneja errores críticos
     */
    protected function handleCriticalError(Request $request, \Exception $e, array $logData): void
    {
        $criticalErrors = [
            'Database connection failed',
            'Cache system unavailable',
            'Session storage failed',
            'File system error',
            'Memory limit exceeded'
        ];

        $isCritical = false;
        foreach ($criticalErrors as $criticalError) {
            if (str_contains($e->getMessage(), $criticalError)) {
                $isCritical = true;
                break;
            }
        }

        if ($isCritical) {
            // Log crítico
            Log::channel('daily')->critical('Critical error occurred', $logData);

            // Log crítico
            Log::critical('Error crítico del sistema', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Obtiene una versión corta del user agent
     */
    protected function getShortUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        } elseif (str_contains($userAgent, 'Postman')) {
            return 'Postman';
        } elseif (str_contains($userAgent, 'curl')) {
            return 'cURL';
        } else {
            return 'Other';
        }
    }

    /**
     * Formatea bytes en formato legible
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Obtiene estadísticas de logging
     */
    public function getLoggingStatistics(): array
    {
        $metricsKey = 'request_metrics_' . date('Y-m-d');
        $metrics = Cache::get($metricsKey, []);

        return [
            'total_requests' => $metrics['total_requests'] ?? 0,
            'total_errors' => $metrics['total_errors'] ?? 0,
            'error_rate' => $metrics['total_requests'] > 0 ?
                round(($metrics['total_errors'] ?? 0) / $metrics['total_requests'] * 100, 2) : 0,
            'average_response_time' => $metrics['average_response_time'] ?? 0,
            'top_endpoints' => $this->getTopEndpoints($metrics['endpoints'] ?? []),
            'status_codes' => $metrics['status_codes'] ?? [],
            'user_agents' => $metrics['user_agents'] ?? []
        ];
    }

    /**
     * Obtiene los endpoints más utilizados
     */
    protected function getTopEndpoints(array $endpoints): array
    {
        uasort($endpoints, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_slice($endpoints, 0, 10, true);
    }

    /**
     * Limpia logs antiguos
     */
    public function cleanupOldLogs(): void
    {
        // Limpiar métricas de días anteriores
        $daysToKeep = 7;
        for ($i = 1; $i <= $daysToKeep; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $oldMetricsKey = 'request_metrics_' . $date;
            Cache::forget($oldMetricsKey);
        }

        // Log de limpieza
        Log::info('Old logs cleaned up', [
            'days_cleaned' => $daysToKeep,
            'timestamp' => now()->toISOString()
        ]);
    }
}
