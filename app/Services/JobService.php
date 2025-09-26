<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SystemIntegrationJob;
use App\Jobs\LoggingJob;
use App\Jobs\BackupJob;
use App\Jobs\NotificationJob;
use App\Jobs\CleanupJob;

class JobService
{
    protected $jobStats = [];

    /**
     * Despachar job de integración del sistema
     */
    public function dispatchSystemJob(string $integrationType, array $data = [], int $priority = 3): void
    {
        try {
            $job = new SystemIntegrationJob($integrationType, $data, $priority);
            dispatch($job);

            $this->updateJobStats('system');

            Log::info('Job de sistema despachado', [
                'type' => $integrationType,
                'priority' => $priority,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error al despachar job de sistema', [
                'type' => $integrationType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Despachar job de logging
     */
    public function dispatchLoggingJob(string $logType, array $logData, string $logLevel = 'info', string $channel = 'daily'): void
    {
        try {
            $job = new LoggingJob($logType, $logData, $logLevel, $channel);
            dispatch($job);

            $this->updateJobStats('logging');

            Log::info('Job de logging despachado', [
                'type' => $logType,
                'level' => $logLevel,
                'channel' => $channel
            ]);

        } catch (\Exception $e) {
            Log::error('Error al despachar job de logging', [
                'type' => $logType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Despachar job de respaldo
     */
    public function dispatchBackupJob(string $backupType, array $backupData = [], int $priority = 3, int $retentionDays = 30): void
    {
        try {
            $job = new BackupJob($backupType, $backupData, $priority, $retentionDays);
            dispatch($job);

            $this->updateJobStats('backup');

            Log::info('Job de respaldo despachado', [
                'type' => $backupType,
                'priority' => $priority,
                'retention_days' => $retentionDays
            ]);

        } catch (\Exception $e) {
            Log::error('Error al despachar job de respaldo', [
                'type' => $backupType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Despachar job de notificación
     */
    public function dispatchNotificationJob(string $notificationType, array $notificationData, int $priority = 3, array $channels = ['database']): void
    {
        try {
            $job = new NotificationJob($notificationType, $notificationData, $priority, $channels);
            dispatch($job);

            $this->updateJobStats('notification');

            Log::info('Job de notificación despachado', [
                'type' => $notificationType,
                'priority' => $priority,
                'channels' => $channels
            ]);

        } catch (\Exception $e) {
            Log::error('Error al despachar job de notificación', [
                'type' => $notificationType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Despachar job de limpieza
     */
    public function dispatchCleanupJob(string $cleanupType, array $cleanupData = [], int $retentionDays = 30): void
    {
        try {
            $job = new CleanupJob($cleanupType, $cleanupData, $retentionDays);
            dispatch($job);

            $this->updateJobStats('cleanup');

            Log::info('Job de limpieza despachado', [
                'type' => $cleanupType,
                'retention_days' => $retentionDays
            ]);

        } catch (\Exception $e) {
            Log::error('Error al despachar job de limpieza', [
                'type' => $cleanupType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Despachar jobs programados
     */
    public function dispatchScheduledJobs(): void
    {
        try {
            // Job de verificación de salud del sistema (cada hora)
            $this->dispatchSystemJob('system_health_check', [
                'scheduled' => true,
                'timestamp' => now()->toISOString()
            ], 2);

            // Job de respaldo automático (diario)
            if ($this->shouldRunDailyBackup()) {
                $this->dispatchBackupJob('database', [
                    'name' => 'Backup Automático - ' . now()->format('Y-m-d'),
                    'description' => 'Respaldo automático diario del sistema',
                    'scheduled' => true
                ], 3, 30);
            }

            // Job de limpieza (diario)
            $this->dispatchCleanupJob('full_cleanup', [
                'scheduled' => true,
                'timestamp' => now()->toISOString()
            ], 30);

            // Job de logging de estadísticas (cada 6 horas)
            $this->dispatchLoggingJob('system_log', [
                'event' => 'scheduled_statistics',
                'timestamp' => now()->toISOString(),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            ], 'info', 'daily');

            Log::info('Jobs programados despachados exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al despachar jobs programados', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de jobs
     */
    public function getJobStatistics(): array
    {
        $stats = [
            'total_jobs' => Cache::get('job_stats_total', 0),
            'successful_jobs' => Cache::get('job_stats_successful', 0),
            'failed_jobs' => Cache::get('job_stats_failed', 0),
            'system_jobs' => Cache::get('job_stats_system', 0),
            'logging_jobs' => Cache::get('job_stats_logging', 0),
            'backup_jobs' => Cache::get('job_stats_backup', 0),
            'notification_jobs' => Cache::get('job_stats_notification', 0),
            'cleanup_jobs' => Cache::get('job_stats_cleanup', 0),
            'queue_sizes' => $this->getQueueSizes(),
            'worker_status' => $this->getWorkerStatus()
        ];

        return $stats;
    }

    /**
     * Obtener tamaños de las colas
     */
    protected function getQueueSizes(): array
    {
        $queues = ['high', 'normal', 'low', 'logging', 'cleanup'];
        $sizes = [];

        foreach ($queues as $queue) {
            try {
                $sizes[$queue] = \Queue::size($queue);
            } catch (\Exception $e) {
                $sizes[$queue] = 0;
            }
        }

        return $sizes;
    }

    /**
     * Obtener estado de los workers
     */
    protected function getWorkerStatus(): array
    {
        return [
            'total_workers' => 7,
            'active_workers' => 7,
            'idle_workers' => 0,
            'busy_workers' => 0,
            'last_activity' => now()->toISOString()
        ];
    }

    /**
     * Actualizar estadísticas de jobs
     */
    protected function updateJobStats(string $jobType): void
    {
        $statsKey = "job_stats_{$jobType}";
        $totalKey = 'job_stats_total';

        // Incrementar contador específico
        $current = Cache::get($statsKey, 0);
        Cache::put($statsKey, $current + 1, 86400);

        // Incrementar contador total
        $total = Cache::get($totalKey, 0);
        Cache::put($totalKey, $total + 1, 86400);
    }

    /**
     * Registrar job exitoso
     */
    public function recordSuccessfulJob(string $jobType): void
    {
        $statsKey = 'job_stats_successful';
        $current = Cache::get($statsKey, 0);
        Cache::put($statsKey, $current + 1, 86400);

        Log::info('Job exitoso registrado', [
            'type' => $jobType,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Registrar job fallido
     */
    public function recordFailedJob(string $jobType, string $error): void
    {
        $statsKey = 'job_stats_failed';
        $current = Cache::get($statsKey, 0);
        Cache::put($statsKey, $current + 1, 86400);

        Log::error('Job fallido registrado', [
            'type' => $jobType,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Verificar si debe ejecutar respaldo diario
     */
    protected function shouldRunDailyBackup(): bool
    {
        $lastBackupKey = 'last_daily_backup';
        $lastBackup = Cache::get($lastBackupKey);

        if (!$lastBackup) {
            return true;
        }

        $lastBackupDate = \Carbon\Carbon::parse($lastBackup);
        return $lastBackupDate->isBefore(now()->startOfDay());
    }

    /**
     * Limpiar estadísticas de jobs
     */
    public function clearJobStatistics(): void
    {
        $keys = [
            'job_stats_total',
            'job_stats_successful',
            'job_stats_failed',
            'job_stats_system',
            'job_stats_logging',
            'job_stats_backup',
            'job_stats_notification',
            'job_stats_cleanup'
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Log::info('Estadísticas de jobs limpiadas');
    }

    /**
     * Obtener jobs pendientes
     */
    public function getPendingJobs(): array
    {
        $queues = ['high', 'normal', 'low', 'logging', 'cleanup'];
        $pendingJobs = [];

        foreach ($queues as $queue) {
            try {
                $size = \Queue::size($queue);
                if ($size > 0) {
                    $pendingJobs[$queue] = $size;
                }
            } catch (\Exception $e) {
                Log::error('Error al obtener jobs pendientes', [
                    'queue' => $queue,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $pendingJobs;
    }

    /**
     * Verificar salud de los jobs
     */
    public function checkJobHealth(): array
    {
        $health = [
            'healthy' => true,
            'issues' => [],
            'timestamp' => now()->toISOString()
        ];

        // Verificar colas
        $pendingJobs = $this->getPendingJobs();
        $totalPending = array_sum($pendingJobs);

        if ($totalPending > 100) {
            $health['healthy'] = false;
            $health['issues'][] = "Demasiados jobs pendientes: {$totalPending}";
        }

        // Verificar jobs fallidos
        $failedJobs = Cache::get('job_stats_failed', 0);
        $totalJobs = Cache::get('job_stats_total', 0);

        if ($totalJobs > 0) {
            $failureRate = ($failedJobs / $totalJobs) * 100;
            if ($failureRate > 10) {
                $health['healthy'] = false;
                $health['issues'][] = "Tasa de fallos alta: {$failureRate}%";
            }
        }

        return $health;
    }
}



