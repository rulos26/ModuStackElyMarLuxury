<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LoggingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $logType;
    protected $logData;
    protected $logLevel;
    protected $channel;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $logType,
        array $logData,
        string $logLevel = 'info',
        string $channel = 'daily'
    ) {
        $this->logType = $logType;
        $this->logData = $logData;
        $this->logLevel = $logLevel;
        $this->channel = $channel;

        // Establecer cola de logging
        $this->onQueue('logging');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            // Procesar el log según el tipo
            switch ($this->logType) {
                case 'request_log':
                    $this->processRequestLog();
                    break;
                case 'error_log':
                    $this->processErrorLog();
                    break;
                case 'performance_log':
                    $this->processPerformanceLog();
                    break;
                case 'security_log':
                    $this->processSecurityLog();
                    break;
                case 'system_log':
                    $this->processSystemLog();
                    break;
                case 'audit_log':
                    $this->processAuditLog();
                    break;
                default:
                    $this->processGenericLog();
            }

            $executionTime = microtime(true) - $startTime;

            // Log de confirmación
            Log::channel($this->channel)->info("Logging job completado", [
                'type' => $this->logType,
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true)
            ]);

        } catch (\Exception $e) {
            Log::error("Error en job de logging", [
                'type' => $this->logType,
                'error' => $e->getMessage(),
                'data' => $this->logData
            ]);

            throw $e;
        }
    }

    /**
     * Procesar log de request
     */
    protected function processRequestLog(): void
    {
        $logData = array_merge($this->logData, [
            'timestamp' => now()->toISOString(),
            'job_type' => 'request_log'
        ]);

        // Log estructurado
        Log::channel($this->channel)->{$this->logLevel}('Request processed', $logData);

        // Actualizar métricas
        $this->updateRequestMetrics($logData);

        // Guardar en archivo específico si es necesario
        if ($this->shouldSaveToFile()) {
            $this->saveToFile('requests', $logData);
        }
    }

    /**
     * Procesar log de error
     */
    protected function processErrorLog(): void
    {
        $logData = array_merge($this->logData, [
            'timestamp' => now()->toISOString(),
            'job_type' => 'error_log',
            'severity' => $this->logLevel
        ]);

        // Log de error
        Log::channel($this->channel)->{$this->logLevel}('Error occurred', $logData);

        // Actualizar métricas de errores
        $this->updateErrorMetrics($logData);

        // Enviar alerta si es crítico
        if ($this->logLevel === 'critical' || $this->logLevel === 'emergency') {
            $this->sendCriticalAlert($logData);
        }

        // Guardar en archivo de errores
        $this->saveToFile('errors', $logData);
    }

    /**
     * Procesar log de rendimiento
     */
    protected function processPerformanceLog(): void
    {
        $logData = array_merge($this->logData, [
            'timestamp' => now()->toISOString(),
            'job_type' => 'performance_log'
        ]);

        // Log de rendimiento
        Log::channel($this->channel)->{$this->logLevel}('Performance metrics', $logData);

        // Actualizar métricas de rendimiento
        $this->updatePerformanceMetrics($logData);

        // Verificar si hay problemas de rendimiento
        $this->checkPerformanceIssues($logData);

        // Guardar en archivo de rendimiento
        $this->saveToFile('performance', $logData);
    }

    /**
     * Procesar log de seguridad
     */
    protected function processSecurityLog(): void
    {
        $logData = array_merge($this->logData, [
            'timestamp' => now()->toISOString(),
            'job_type' => 'security_log',
            'security_level' => $this->logLevel
        ]);

        // Log de seguridad
        Log::channel($this->channel)->{$this->logLevel}('Security event', $logData);

        // Actualizar métricas de seguridad
        $this->updateSecurityMetrics($logData);

        // Verificar amenazas
        $this->checkSecurityThreats($logData);

        // Guardar en archivo de seguridad
        $this->saveToFile('security', $logData);
    }

    /**
     * Procesar log del sistema
     */
    protected function processSystemLog(): void
    {
        $logData = array_merge($this->logData, [
            'timestamp' => now()->toISOString(),
            'job_type' => 'system_log'
        ]);

        // Log del sistema
        Log::channel($this->channel)->{$this->logLevel}('System event', $logData);

        // Actualizar métricas del sistema
        $this->updateSystemMetrics($logData);

        // Guardar en archivo del sistema
        $this->saveToFile('system', $logData);
    }

    /**
     * Procesar log de auditoría
     */
    protected function processAuditLog(): void
    {
        $logData = array_merge($this->logData, [
            'timestamp' => now()->toISOString(),
            'job_type' => 'audit_log'
        ]);

        // Log de auditoría
        Log::channel($this->channel)->{$this->logLevel}('Audit event', $logData);

        // Guardar en archivo de auditoría
        $this->saveToFile('audit', $logData);

        // Actualizar estadísticas de auditoría
        $this->updateAuditStats($logData);
    }

    /**
     * Procesar log genérico
     */
    protected function processGenericLog(): void
    {
        $logData = array_merge($this->logData, [
            'timestamp' => now()->toISOString(),
            'job_type' => 'generic_log'
        ]);

        // Log genérico
        Log::channel($this->channel)->{$this->logLevel}('Generic log', $logData);
    }

    /**
     * Actualizar métricas de requests
     */
    protected function updateRequestMetrics(array $logData): void
    {
        $metricsKey = 'request_metrics_' . date('Y-m-d');
        $metrics = Cache::get($metricsKey, [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'average_response_time' => 0
        ]);

        $metrics['total_requests']++;

        if (isset($logData['status_code']) && $logData['status_code'] < 400) {
            $metrics['successful_requests']++;
        } else {
            $metrics['failed_requests']++;
        }

        if (isset($logData['execution_time'])) {
            $metrics['average_response_time'] =
                ($metrics['average_response_time'] + $logData['execution_time']) / 2;
        }

        Cache::put($metricsKey, $metrics, 86400);
    }

    /**
     * Actualizar métricas de errores
     */
    protected function updateErrorMetrics(array $logData): void
    {
        $metricsKey = 'error_metrics_' . date('Y-m-d');
        $metrics = Cache::get($metricsKey, [
            'total_errors' => 0,
            'critical_errors' => 0,
            'error_types' => []
        ]);

        $metrics['total_errors']++;

        if ($this->logLevel === 'critical' || $this->logLevel === 'emergency') {
            $metrics['critical_errors']++;
        }

        $errorType = $logData['error_type'] ?? 'unknown';
        if (!isset($metrics['error_types'][$errorType])) {
            $metrics['error_types'][$errorType] = 0;
        }
        $metrics['error_types'][$errorType]++;

        Cache::put($metricsKey, $metrics, 86400);
    }

    /**
     * Actualizar métricas de rendimiento
     */
    protected function updatePerformanceMetrics(array $logData): void
    {
        $metricsKey = 'performance_metrics_' . date('Y-m-d');
        $metrics = Cache::get($metricsKey, [
            'slow_requests' => 0,
            'memory_issues' => 0,
            'average_execution_time' => 0
        ]);

        if (isset($logData['execution_time']) && $logData['execution_time'] > 5.0) {
            $metrics['slow_requests']++;
        }

        if (isset($logData['memory_usage']) && $logData['memory_usage'] > 100 * 1024 * 1024) {
            $metrics['memory_issues']++;
        }

        if (isset($logData['execution_time'])) {
            $metrics['average_execution_time'] =
                ($metrics['average_execution_time'] + $logData['execution_time']) / 2;
        }

        Cache::put($metricsKey, $metrics, 86400);
    }

    /**
     * Actualizar métricas de seguridad
     */
    protected function updateSecurityMetrics(array $logData): void
    {
        $metricsKey = 'security_metrics_' . date('Y-m-d');
        $metrics = Cache::get($metricsKey, [
            'security_events' => 0,
            'blocked_requests' => 0,
            'threat_levels' => []
        ]);

        $metrics['security_events']++;

        if (isset($logData['blocked']) && $logData['blocked']) {
            $metrics['blocked_requests']++;
        }

        $threatLevel = $logData['threat_level'] ?? 'low';
        if (!isset($metrics['threat_levels'][$threatLevel])) {
            $metrics['threat_levels'][$threatLevel] = 0;
        }
        $metrics['threat_levels'][$threatLevel]++;

        Cache::put($metricsKey, $metrics, 86400);
    }

    /**
     * Actualizar métricas del sistema
     */
    protected function updateSystemMetrics(array $logData): void
    {
        $metricsKey = 'system_metrics_' . date('Y-m-d');
        $metrics = Cache::get($metricsKey, [
            'system_events' => 0,
            'maintenance_events' => 0,
            'health_checks' => 0
        ]);

        $metrics['system_events']++;

        if (isset($logData['event_type'])) {
            switch ($logData['event_type']) {
                case 'maintenance':
                    $metrics['maintenance_events']++;
                    break;
                case 'health_check':
                    $metrics['health_checks']++;
                    break;
            }
        }

        Cache::put($metricsKey, $metrics, 86400);
    }

    /**
     * Actualizar estadísticas de auditoría
     */
    protected function updateAuditStats(array $logData): void
    {
        $statsKey = 'audit_stats_' . date('Y-m-d');
        $stats = Cache::get($statsKey, [
            'total_audit_events' => 0,
            'user_actions' => 0,
            'admin_actions' => 0
        ]);

        $stats['total_audit_events']++;

        if (isset($logData['user_id'])) {
            $stats['user_actions']++;
        }

        if (isset($logData['admin_action']) && $logData['admin_action']) {
            $stats['admin_actions']++;
        }

        Cache::put($statsKey, $stats, 86400);
    }

    /**
     * Verificar problemas de rendimiento
     */
    protected function checkPerformanceIssues(array $logData): void
    {
        $issues = [];

        if (isset($logData['execution_time']) && $logData['execution_time'] > 10.0) {
            $issues[] = "Tiempo de ejecución muy lento: {$logData['execution_time']}s";
        }

        if (isset($logData['memory_usage']) && $logData['memory_usage'] > 200 * 1024 * 1024) {
            $issues[] = "Uso de memoria excesivo: " . $this->formatBytes($logData['memory_usage']);
        }

        if (!empty($issues)) {
            Log::warning('Problemas de rendimiento detectados', [
                'issues' => $issues,
                'log_data' => $logData
            ]);
        }
    }

    /**
     * Verificar amenazas de seguridad
     */
    protected function checkSecurityThreats(array $logData): void
    {
        $threats = [];

        if (isset($logData['suspicious_patterns'])) {
            $threats[] = "Patrones sospechosos detectados";
        }

        if (isset($logData['blocked_ip'])) {
            $threats[] = "IP bloqueada: {$logData['blocked_ip']}";
        }

        if (!empty($threats)) {
            Log::warning('Amenazas de seguridad detectadas', [
                'threats' => $threats,
                'log_data' => $logData
            ]);
        }
    }

    /**
     * Enviar alerta crítica
     */
    protected function sendCriticalAlert(array $logData): void
    {
        Log::critical('Alerta crítica del sistema', [
            'alert_type' => 'critical_error',
            'log_data' => $logData,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Determinar si debe guardar en archivo
     */
    protected function shouldSaveToFile(): bool
    {
        return in_array($this->logType, ['request_log', 'error_log', 'performance_log', 'security_log', 'audit_log']);
    }

    /**
     * Guardar en archivo específico
     */
    protected function saveToFile(string $type, array $logData): void
    {
        $filename = $type . '_' . date('Y-m-d') . '.json';
        $filepath = storage_path('logs/' . $filename);

        $existingData = [];
        if (file_exists($filepath)) {
            $existingData = json_decode(file_get_contents($filepath), true) ?? [];
        }

        $existingData[] = $logData;

        file_put_contents($filepath, json_encode($existingData, JSON_PRETTY_PRINT));
    }

    /**
     * Formatear bytes
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
     * Determinar si el job debe fallar
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de logging falló permanentemente", [
            'type' => $this->logType,
            'error' => $exception->getMessage(),
            'data' => $this->logData
        ]);
    }
}

