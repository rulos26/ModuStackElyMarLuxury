<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Services\ActivityLogService;

class PerformanceMonitoringMiddleware
{
    protected $notificationService;
    protected $activityLogService;

    public function __construct(
        NotificationService $notificationService,
        ActivityLogService $activityLogService
    ) {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
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
        $startMemory = memory_get_usage(true);
        $startPeakMemory = memory_get_peak_usage(true);

        // Inicializar métricas
        $metrics = $this->initializeMetrics($request);

        try {
            // Procesar la request
            $response = $next($request);

            // Calcular métricas finales
            $this->calculateFinalMetrics($metrics, $startTime, $startMemory, $startPeakMemory);

            // Analizar rendimiento
            $this->analyzePerformance($request, $response, $metrics);

            // Actualizar estadísticas
            $this->updatePerformanceStatistics($request, $metrics);

            return $response;

        } catch (\Exception $e) {
            // Calcular métricas de error
            $this->calculateErrorMetrics($metrics, $startTime, $startMemory, $startPeakMemory, $e);

            // Analizar rendimiento del error
            $this->analyzeErrorPerformance($request, $e, $metrics);

            // Re-lanzar la excepción
            throw $e;
        }
    }

    /**
     * Inicializa las métricas de rendimiento
     */
    protected function initializeMetrics(Request $request): array
    {
        return [
            'request_id' => $request->get('request_id', uniqid()),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'start_peak_memory' => memory_get_peak_usage(true),
            'database_queries' => [],
            'cache_operations' => [],
            'external_requests' => [],
            'file_operations' => [],
            'session_operations' => [],
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Calcula las métricas finales
     */
    protected function calculateFinalMetrics(array &$metrics, float $startTime, int $startMemory, int $startPeakMemory): void
    {
        $metrics['end_time'] = microtime(true);
        $metrics['execution_time'] = $metrics['end_time'] - $startTime;
        $metrics['memory_usage'] = memory_get_usage(true) - $startMemory;
        $metrics['peak_memory'] = memory_get_peak_usage(true);
        $metrics['memory_peak_increase'] = $metrics['peak_memory'] - $startPeakMemory;
        $metrics['database_query_count'] = count($metrics['database_queries']);
        $metrics['cache_operation_count'] = count($metrics['cache_operations']);
        $metrics['external_request_count'] = count($metrics['external_requests']);
        $metrics['file_operation_count'] = count($metrics['file_operations']);
        $metrics['session_operation_count'] = count($metrics['session_operations']);
    }

    /**
     * Calcula métricas de error
     */
    protected function calculateErrorMetrics(array &$metrics, float $startTime, int $startMemory, int $startPeakMemory, \Exception $e): void
    {
        $metrics['end_time'] = microtime(true);
        $metrics['execution_time'] = $metrics['end_time'] - $startTime;
        $metrics['memory_usage'] = memory_get_usage(true) - $startMemory;
        $metrics['peak_memory'] = memory_get_peak_usage(true);
        $metrics['memory_peak_increase'] = $metrics['peak_memory'] - $startPeakMemory;
        $metrics['error'] = true;
        $metrics['error_message'] = $e->getMessage();
        $metrics['error_file'] = $e->getFile();
        $metrics['error_line'] = $e->getLine();
    }

    /**
     * Analiza el rendimiento de la request
     */
    protected function analyzePerformance(Request $request, $response, array $metrics): void
    {
        $performanceIssues = [];

        // Análisis de tiempo de ejecución
        if ($metrics['execution_time'] > 5.0) {
            $performanceIssues[] = [
                'type' => 'slow_execution',
                'message' => "Tiempo de ejecución lento: {$metrics['execution_time']}s",
                'severity' => $metrics['execution_time'] > 10.0 ? 'high' : 'medium'
            ];
        }

        // Análisis de uso de memoria
        if ($metrics['memory_usage'] > 50 * 1024 * 1024) { // 50MB
            $performanceIssues[] = [
                'type' => 'high_memory_usage',
                'message' => "Uso de memoria alto: " . $this->formatBytes($metrics['memory_usage']),
                'severity' => $metrics['memory_usage'] > 100 * 1024 * 1024 ? 'high' : 'medium'
            ];
        }

        // Análisis de consultas de base de datos
        if ($metrics['database_query_count'] > 20) {
            $performanceIssues[] = [
                'type' => 'too_many_queries',
                'message' => "Demasiadas consultas de base de datos: {$metrics['database_query_count']}",
                'severity' => $metrics['database_query_count'] > 50 ? 'high' : 'medium'
            ];
        }

        // Análisis de operaciones de cache
        if ($metrics['cache_operation_count'] > 100) {
            $performanceIssues[] = [
                'type' => 'too_many_cache_operations',
                'message' => "Demasiadas operaciones de cache: {$metrics['cache_operation_count']}",
                'severity' => 'medium'
            ];
        }

        // Análisis de código de estado
        if ($response->getStatusCode() >= 400) {
            $performanceIssues[] = [
                'type' => 'error_response',
                'message' => "Código de estado de error: {$response->getStatusCode()}",
                'severity' => $response->getStatusCode() >= 500 ? 'high' : 'medium'
            ];
        }

        // Si hay problemas de rendimiento, manejarlos
        if (!empty($performanceIssues)) {
            $this->handlePerformanceIssues($request, $performanceIssues, $metrics);
        }

        // Log de métricas de rendimiento
        $this->logPerformanceMetrics($request, $metrics);
    }

    /**
     * Analiza el rendimiento del error
     */
    protected function analyzeErrorPerformance(Request $request, \Exception $e, array $metrics): void
    {
        $errorIssues = [];

        // Análisis de tiempo de ejecución del error
        if ($metrics['execution_time'] > 2.0) {
            $errorIssues[] = [
                'type' => 'slow_error',
                'message' => "Error con tiempo de ejecución lento: {$metrics['execution_time']}s",
                'severity' => 'medium'
            ];
        }

        // Análisis de memoria en error
        if ($metrics['memory_usage'] > 25 * 1024 * 1024) { // 25MB
            $errorIssues[] = [
                'type' => 'high_memory_error',
                'message' => "Error con uso de memoria alto: " . $this->formatBytes($metrics['memory_usage']),
                'severity' => 'medium'
            ];
        }

        // Si hay problemas, manejarlos
        if (!empty($errorIssues)) {
            $this->handleErrorPerformanceIssues($request, $errorIssues, $metrics);
        }

        // Log de métricas de error
        $this->logErrorMetrics($request, $metrics);
    }

    /**
     * Maneja problemas de rendimiento
     */
    protected function handlePerformanceIssues(Request $request, array $issues, array $metrics): void
    {
        $highSeverityIssues = array_filter($issues, function ($issue) {
            return $issue['severity'] === 'high';
        });

        $mediumSeverityIssues = array_filter($issues, function ($issue) {
            return $issue['severity'] === 'medium';
        });

        // Log de problemas de rendimiento
        Log::channel('daily')->warning('Performance issues detected', [
            'request_id' => $metrics['request_id'],
            'url' => $request->fullUrl(),
            'issues' => $issues,
            'metrics' => $metrics,
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('performance_issues', [
            'request_id' => $metrics['request_id'],
            'url' => $request->fullUrl(),
            'issues' => $issues,
            'execution_time' => $metrics['execution_time'],
            'memory_usage' => $metrics['memory_usage']
        ]);

        // Enviar notificaciones según la severidad
        if (!empty($highSeverityIssues)) {
            $this->notificationService->createNotification(
                'Problemas Críticos de Rendimiento',
                'Se han detectado problemas críticos de rendimiento: ' .
                implode(', ', array_column($highSeverityIssues, 'message')),
                'error'
            );
        } elseif (!empty($mediumSeverityIssues)) {
            $this->notificationService->createNotification(
                'Problemas de Rendimiento',
                'Se han detectado problemas de rendimiento: ' .
                implode(', ', array_column($mediumSeverityIssues, 'message')),
                'warning'
            );
        }
    }

    /**
     * Maneja problemas de rendimiento en errores
     */
    protected function handleErrorPerformanceIssues(Request $request, array $issues, array $metrics): void
    {
        // Log de problemas de rendimiento en errores
        Log::channel('daily')->warning('Error performance issues detected', [
            'request_id' => $metrics['request_id'],
            'url' => $request->fullUrl(),
            'issues' => $issues,
            'metrics' => $metrics,
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('error_performance_issues', [
            'request_id' => $metrics['request_id'],
            'url' => $request->fullUrl(),
            'issues' => $issues,
            'execution_time' => $metrics['execution_time'],
            'memory_usage' => $metrics['memory_usage']
        ]);
    }

    /**
     * Log de métricas de rendimiento
     */
    protected function logPerformanceMetrics(Request $request, array $metrics): void
    {
        Log::channel('daily')->info('Performance metrics', [
            'request_id' => $metrics['request_id'],
            'url' => $request->fullUrl(),
            'execution_time' => $metrics['execution_time'],
            'memory_usage' => $metrics['memory_usage'],
            'peak_memory' => $metrics['peak_memory'],
            'database_queries' => $metrics['database_query_count'],
            'cache_operations' => $metrics['cache_operation_count'],
            'external_requests' => $metrics['external_request_count'],
            'file_operations' => $metrics['file_operation_count'],
            'session_operations' => $metrics['session_operation_count'],
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log de métricas de error
     */
    protected function logErrorMetrics(Request $request, array $metrics): void
    {
        Log::channel('daily')->error('Error performance metrics', [
            'request_id' => $metrics['request_id'],
            'url' => $request->fullUrl(),
            'execution_time' => $metrics['execution_time'],
            'memory_usage' => $metrics['memory_usage'],
            'peak_memory' => $metrics['peak_memory'],
            'error_message' => $metrics['error_message'],
            'error_file' => $metrics['error_file'],
            'error_line' => $metrics['error_line'],
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Actualiza las estadísticas de rendimiento
     */
    protected function updatePerformanceStatistics(Request $request, array $metrics): void
    {
        $statsKey = 'performance_stats_' . date('Y-m-d');
        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'total_execution_time' => 0,
            'total_memory_usage' => 0,
            'average_response_time' => 0,
            'average_memory_usage' => 0,
            'slow_requests' => 0,
            'high_memory_requests' => 0,
            'error_requests' => 0,
            'endpoints' => [],
            'performance_issues' => []
        ]);

        // Actualizar estadísticas generales
        $stats['total_requests']++;
        $stats['total_execution_time'] += $metrics['execution_time'];
        $stats['total_memory_usage'] += $metrics['memory_usage'];
        $stats['average_response_time'] = $stats['total_execution_time'] / $stats['total_requests'];
        $stats['average_memory_usage'] = $stats['total_memory_usage'] / $stats['total_requests'];

        // Contar requests lentos
        if ($metrics['execution_time'] > 5.0) {
            $stats['slow_requests']++;
        }

        // Contar requests con alta memoria
        if ($metrics['memory_usage'] > 50 * 1024 * 1024) { // 50MB
            $stats['high_memory_requests']++;
        }

        // Contar requests con errores
        if (isset($metrics['error']) && $metrics['error']) {
            $stats['error_requests']++;
        }

        // Estadísticas por endpoint
        $endpoint = $request->path();
        if (!isset($stats['endpoints'][$endpoint])) {
            $stats['endpoints'][$endpoint] = [
                'count' => 0,
                'total_time' => 0,
                'total_memory' => 0,
                'average_time' => 0,
                'average_memory' => 0,
                'slow_count' => 0,
                'high_memory_count' => 0,
                'error_count' => 0
            ];
        }

        $endpointStats = &$stats['endpoints'][$endpoint];
        $endpointStats['count']++;
        $endpointStats['total_time'] += $metrics['execution_time'];
        $endpointStats['total_memory'] += $metrics['memory_usage'];
        $endpointStats['average_time'] = $endpointStats['total_time'] / $endpointStats['count'];
        $endpointStats['average_memory'] = $endpointStats['total_memory'] / $endpointStats['count'];

        if ($metrics['execution_time'] > 5.0) {
            $endpointStats['slow_count']++;
        }

        if ($metrics['memory_usage'] > 50 * 1024 * 1024) {
            $endpointStats['high_memory_count']++;
        }

        if (isset($metrics['error']) && $metrics['error']) {
            $endpointStats['error_count']++;
        }

        // Guardar estadísticas
        Cache::put($statsKey, $stats, 86400); // 24 horas
    }

    /**
     * Obtiene estadísticas de rendimiento
     */
    public function getPerformanceStatistics(): array
    {
        $statsKey = 'performance_stats_' . date('Y-m-d');
        $stats = Cache::get($statsKey, []);

        return [
            'total_requests' => $stats['total_requests'] ?? 0,
            'average_response_time' => $stats['average_response_time'] ?? 0,
            'average_memory_usage' => $stats['average_memory_usage'] ?? 0,
            'slow_requests' => $stats['slow_requests'] ?? 0,
            'high_memory_requests' => $stats['high_memory_requests'] ?? 0,
            'error_requests' => $stats['error_requests'] ?? 0,
            'slow_request_rate' => $stats['total_requests'] > 0 ?
                round(($stats['slow_requests'] ?? 0) / $stats['total_requests'] * 100, 2) : 0,
            'high_memory_rate' => $stats['total_requests'] > 0 ?
                round(($stats['high_memory_requests'] ?? 0) / $stats['total_requests'] * 100, 2) : 0,
            'error_rate' => $stats['total_requests'] > 0 ?
                round(($stats['error_requests'] ?? 0) / $stats['total_requests'] * 100, 2) : 0,
            'top_endpoints' => $this->getTopEndpoints($stats['endpoints'] ?? []),
            'performance_issues' => $stats['performance_issues'] ?? []
        ];
    }

    /**
     * Obtiene los endpoints con mejor rendimiento
     */
    protected function getTopEndpoints(array $endpoints): array
    {
        // Ordenar por tiempo promedio
        uasort($endpoints, function ($a, $b) {
            return $a['average_time'] <=> $b['average_time'];
        });

        return array_slice($endpoints, 0, 10, true);
    }

    /**
     * Obtiene los endpoints con peor rendimiento
     */
    public function getWorstPerformingEndpoints(): array
    {
        $statsKey = 'performance_stats_' . date('Y-m-d');
        $stats = Cache::get($statsKey, []);
        $endpoints = $stats['endpoints'] ?? [];

        // Ordenar por tiempo promedio (descendente)
        uasort($endpoints, function ($a, $b) {
            return $b['average_time'] <=> $a['average_time'];
        });

        return array_slice($endpoints, 0, 10, true);
    }

    /**
     * Obtiene alertas de rendimiento
     */
    public function getPerformanceAlerts(): array
    {
        $alerts = [];
        $stats = $this->getPerformanceStatistics();

        // Alerta de tasa de requests lentos
        if ($stats['slow_request_rate'] > 10) {
            $alerts[] = [
                'type' => 'slow_requests',
                'message' => "Tasa alta de requests lentos: {$stats['slow_request_rate']}%",
                'severity' => $stats['slow_request_rate'] > 20 ? 'high' : 'medium'
            ];
        }

        // Alerta de tasa de requests con alta memoria
        if ($stats['high_memory_rate'] > 15) {
            $alerts[] = [
                'type' => 'high_memory',
                'message' => "Tasa alta de requests con alta memoria: {$stats['high_memory_rate']}%",
                'severity' => $stats['high_memory_rate'] > 30 ? 'high' : 'medium'
            ];
        }

        // Alerta de tasa de errores
        if ($stats['error_rate'] > 5) {
            $alerts[] = [
                'type' => 'high_error_rate',
                'message' => "Tasa alta de errores: {$stats['error_rate']}%",
                'severity' => $stats['error_rate'] > 10 ? 'high' : 'medium'
            ];
        }

        return $alerts;
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
     * Limpia estadísticas antiguas
     */
    public function cleanupOldStatistics(): void
    {
        $daysToKeep = 7;
        for ($i = 1; $i <= $daysToKeep; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $oldStatsKey = 'performance_stats_' . $date;
            Cache::forget($oldStatsKey);
        }

        Log::info('Old performance statistics cleaned up', [
            'days_cleaned' => $daysToKeep,
            'timestamp' => now()->toISOString()
        ]);
    }
}



