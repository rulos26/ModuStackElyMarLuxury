<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\DynamicDriverService;
use App\Services\BackupService;
use App\Services\NotificationService;
use App\Services\ActivityLogService;

class SystemIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $integrationType;
    protected $data;
    protected $priority;

    /**
     * Create a new job instance.
     */
    public function __construct(string $integrationType, array $data = [], int $priority = 5)
    {
        $this->integrationType = $integrationType;
        $this->data = $data;
        $this->priority = $priority;

        // Establecer prioridad de la cola
        $this->onQueue($this->getQueueName());
    }

    /**
     * Execute the job.
     */
    public function handle(
        DynamicDriverService $dynamicDriverService,
        BackupService $backupService,
        NotificationService $notificationService,
        ActivityLogService $activityLogService
    ): void {
        $startTime = microtime(true);

        try {
            Log::info("Iniciando job de integración del sistema", [
                'type' => $this->integrationType,
                'data' => $this->data,
                'priority' => $this->priority
            ]);

            // Ejecutar integración según el tipo
            switch ($this->integrationType) {
                case 'driver_sync':
                    $this->syncDrivers($dynamicDriverService);
                    break;
                case 'backup_creation':
                    $this->createBackup($backupService);
                    break;
                case 'notification_send':
                    $this->sendNotification($notificationService);
                    break;
                case 'activity_log':
                    $this->logActivity($activityLogService);
                    break;
                case 'system_health_check':
                    $this->checkSystemHealth();
                    break;
                case 'cleanup_resources':
                    $this->cleanupResources();
                    break;
                default:
                    throw new \InvalidArgumentException("Tipo de integración no válido: {$this->integrationType}");
            }

            $executionTime = microtime(true) - $startTime;

            Log::info("Job de integración completado exitosamente", [
                'type' => $this->integrationType,
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true)
            ]);

        } catch (\Exception $e) {
            Log::error("Error en job de integración del sistema", [
                'type' => $this->integrationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar la excepción para que Laravel maneje el reintento
            throw $e;
        }
    }

    /**
     * Sincronizar drivers dinámicos
     */
    protected function syncDrivers(DynamicDriverService $dynamicDriverService): void
    {
        Log::info('Sincronizando drivers dinámicos');

        // Obtener estado actual de drivers
        $driversStatus = $dynamicDriverService->getAllDriversStatus();

        // Verificar si hay cambios pendientes
        foreach ($driversStatus as $service => $status) {
            if (isset($this->data['drivers'][$service])) {
                $newDriver = $this->data['drivers'][$service];
                if ($status['current'] !== $newDriver) {
                    $dynamicDriverService->changeDriver(
                        $service,
                        $newDriver,
                        $this->data['config'][$service] ?? []
                    );
                }
            }
        }
    }

    /**
     * Crear respaldo del sistema
     */
    protected function createBackup(BackupService $backupService): void
    {
        Log::info('Creando respaldo del sistema');

        $backupName = $this->data['name'] ?? 'Backup Automático - ' . now()->format('Y-m-d H:i:s');
        $backupDescription = $this->data['description'] ?? 'Respaldo automático del sistema';

        $backupService->createBackup($backupName, $backupDescription);
    }

    /**
     * Enviar notificación
     */
    protected function sendNotification(NotificationService $notificationService): void
    {
        Log::info('Enviando notificación del sistema');

        $notificationService->createNotification(
            $this->data['title'] ?? 'Notificación del Sistema',
            $this->data['message'] ?? 'Mensaje de notificación',
            $this->data['type'] ?? 'info'
        );
    }

    /**
     * Registrar actividad del sistema
     */
    protected function logActivity(ActivityLogService $activityLogService): void
    {
        Log::info('Registrando actividad del sistema');

        $activityLogService->logSystemActivity(
            $this->data['activity'] ?? 'system_job',
            $this->data['context'] ?? []
        );
    }

    /**
     * Verificar salud del sistema
     */
    protected function checkSystemHealth(): void
    {
        Log::info('Verificando salud del sistema');

        $healthStatus = [
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'disk_usage' => $this->getDiskUsage(),
            'database_connection' => $this->checkDatabaseConnection(),
            'cache_status' => $this->checkCacheStatus()
        ];

        // Si hay problemas, registrar alerta
        if ($healthStatus['memory_usage'] > 100 * 1024 * 1024) { // 100MB
            Log::warning('Uso de memoria alto detectado', $healthStatus);
        }

        if ($healthStatus['disk_usage']['percentage'] > 90) {
            Log::warning('Espacio en disco bajo', $healthStatus);
        }
    }

    /**
     * Limpiar recursos del sistema
     */
    protected function cleanupResources(): void
    {
        Log::info('Limpiando recursos del sistema');

        // Limpiar cache
        \Cache::flush();

        // Limpiar logs antiguos
        $this->cleanupOldLogs();

        // Limpiar sesiones expiradas
        $this->cleanupExpiredSessions();

        // Limpiar archivos temporales
        $this->cleanupTempFiles();
    }

    /**
     * Obtener nombre de la cola según prioridad
     */
    protected function getQueueName(): string
    {
        if ($this->priority <= 2) {
            return 'high';
        } elseif ($this->priority <= 4) {
            return 'normal';
        } else {
            return 'low';
        }
    }

    /**
     * Obtener uso de disco
     */
    protected function getDiskUsage(): array
    {
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;
        $percentage = ($usedSpace / $totalSpace) * 100;

        return [
            'total' => $totalSpace,
            'used' => $usedSpace,
            'free' => $freeSpace,
            'percentage' => round($percentage, 2)
        ];
    }

    /**
     * Verificar conexión a base de datos
     */
    protected function checkDatabaseConnection(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar estado del cache
     */
    protected function checkCacheStatus(): bool
    {
        try {
            \Cache::put('health_check', 'ok', 60);
            return \Cache::get('health_check') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Limpiar logs antiguos
     */
    protected function cleanupOldLogs(): void
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/laravel-*.log');

        foreach ($files as $file) {
            if (filemtime($file) < strtotime('-7 days')) {
                unlink($file);
            }
        }
    }

    /**
     * Limpiar sesiones expiradas
     */
    protected function cleanupExpiredSessions(): void
    {
        \DB::table('sessions')
            ->where('last_activity', '<', now()->subMinutes(config('session.lifetime', 120))->timestamp)
            ->delete();
    }

    /**
     * Limpiar archivos temporales
     */
    protected function cleanupTempFiles(): void
    {
        $tempPath = storage_path('app/temp');
        if (is_dir($tempPath)) {
            $files = glob($tempPath . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Determinar si el job debe fallar
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de integración falló permanentemente", [
            'type' => $this->integrationType,
            'error' => $exception->getMessage(),
            'data' => $this->data
        ]);
    }
}

