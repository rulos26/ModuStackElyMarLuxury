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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cleanupType;
    protected $cleanupData;
    protected $retentionDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $cleanupType,
        array $cleanupData = [],
        int $retentionDays = 30
    ) {
        $this->cleanupType = $cleanupType;
        $this->cleanupData = $cleanupData;
        $this->retentionDays = $retentionDays;

        // Establecer cola de limpieza
        $this->onQueue('cleanup');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            Log::info("Iniciando job de limpieza", [
                'type' => $this->cleanupType,
                'data' => $this->cleanupData,
                'retention_days' => $this->retentionDays
            ]);

            // Ejecutar limpieza según el tipo
            switch ($this->cleanupType) {
                case 'logs':
                    $this->cleanupLogs();
                    break;
                case 'cache':
                    $this->cleanupCache();
                    break;
                case 'sessions':
                    $this->cleanupSessions();
                    break;
                case 'temp_files':
                    $this->cleanupTempFiles();
                    break;
                case 'backups':
                    $this->cleanupBackups();
                    break;
                case 'notifications':
                    $this->cleanupNotifications();
                    break;
                case 'activity_logs':
                    $this->cleanupActivityLogs();
                    break;
                case 'full_cleanup':
                    $this->fullCleanup();
                    break;
                default:
                    throw new \InvalidArgumentException("Tipo de limpieza no válido: {$this->cleanupType}");
            }

            $executionTime = microtime(true) - $startTime;

            Log::info("Job de limpieza completado exitosamente", [
                'type' => $this->cleanupType,
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true)
            ]);

        } catch (\Exception $e) {
            Log::error("Error en job de limpieza", [
                'type' => $this->cleanupType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Limpiar logs antiguos
     */
    protected function cleanupLogs(): void
    {
        Log::info('Iniciando limpieza de logs');

        $logPath = storage_path('logs');
        $deletedFiles = 0;
        $freedSpace = 0;

        // Limpiar logs de Laravel
        $laravelLogs = glob($logPath . '/laravel-*.log');
        foreach ($laravelLogs as $logFile) {
            if (filemtime($logFile) < strtotime("-{$this->retentionDays} days")) {
                $fileSize = filesize($logFile);
                if (unlink($logFile)) {
                    $deletedFiles++;
                    $freedSpace += $fileSize;
                }
            }
        }

        // Limpiar logs de jobs
        $jobLogs = glob($logPath . '/job-*.log');
        foreach ($jobLogs as $logFile) {
            if (filemtime($logFile) < strtotime("-{$this->retentionDays} days")) {
                $fileSize = filesize($logFile);
                if (unlink($logFile)) {
                    $deletedFiles++;
                    $freedSpace += $fileSize;
                }
            }
        }

        // Limpiar logs de sistema
        $systemLogs = glob($logPath . '/system-*.log');
        foreach ($systemLogs as $logFile) {
            if (filemtime($logFile) < strtotime("-{$this->retentionDays} days")) {
                $fileSize = filesize($logFile);
                if (unlink($logFile)) {
                    $deletedFiles++;
                    $freedSpace += $fileSize;
                }
            }
        }

        Log::info('Limpieza de logs completada', [
            'deleted_files' => $deletedFiles,
            'freed_space' => $this->formatBytes($freedSpace)
        ]);
    }

    /**
     * Limpiar cache
     */
    protected function cleanupCache(): void
    {
        Log::info('Iniciando limpieza de cache');

        $clearedItems = 0;

        // Limpiar cache de aplicación
        Cache::flush();
        $clearedItems++;

        // Limpiar cache de configuración
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $clearedItems++;
        }

        // Limpiar cache de vistas
        $viewCachePath = storage_path('framework/views');
        if (is_dir($viewCachePath)) {
            $viewFiles = glob($viewCachePath . '/*');
            foreach ($viewFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $clearedItems++;
                }
            }
        }

        // Limpiar cache de rutas
        $routeCachePath = storage_path('framework/cache');
        if (is_dir($routeCachePath)) {
            $routeFiles = glob($routeCachePath . '/*');
            foreach ($routeFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $clearedItems++;
                }
            }
        }

        Log::info('Limpieza de cache completada', [
            'cleared_items' => $clearedItems
        ]);
    }

    /**
     * Limpiar sesiones expiradas
     */
    protected function cleanupSessions(): void
    {
        Log::info('Iniciando limpieza de sesiones');

        $deletedSessions = 0;

        // Limpiar sesiones de base de datos
        if (config('session.driver') === 'database') {
            $deletedSessions = DB::table('sessions')
                ->where('last_activity', '<', now()->subMinutes(config('session.lifetime', 120))->timestamp)
                ->delete();
        }

        // Limpiar archivos de sesión
        $sessionPath = storage_path('framework/sessions');
        if (is_dir($sessionPath)) {
            $sessionFiles = glob($sessionPath . '/*');
            foreach ($sessionFiles as $file) {
                if (is_file($file) && filemtime($file) < strtotime("-{$this->retentionDays} days")) {
                    unlink($file);
                    $deletedSessions++;
                }
            }
        }

        Log::info('Limpieza de sesiones completada', [
            'deleted_sessions' => $deletedSessions
        ]);
    }

    /**
     * Limpiar archivos temporales
     */
    protected function cleanupTempFiles(): void
    {
        Log::info('Iniciando limpieza de archivos temporales');

        $deletedFiles = 0;
        $freedSpace = 0;

        // Limpiar directorio temp
        $tempPath = storage_path('app/temp');
        if (is_dir($tempPath)) {
            $tempFiles = glob($tempPath . '/*');
            foreach ($tempFiles as $file) {
                if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                    $fileSize = filesize($file);
                    if (unlink($file)) {
                        $deletedFiles++;
                        $freedSpace += $fileSize;
                    }
                }
            }
        }

        // Limpiar archivos de upload temporal
        $uploadTempPath = storage_path('app/uploads/temp');
        if (is_dir($uploadTempPath)) {
            $uploadFiles = glob($uploadTempPath . '/*');
            foreach ($uploadFiles as $file) {
                if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                    $fileSize = filesize($file);
                    if (unlink($file)) {
                        $deletedFiles++;
                        $freedSpace += $fileSize;
                    }
                }
            }
        }

        // Limpiar archivos de cache de imágenes
        $imageCachePath = storage_path('app/cache/images');
        if (is_dir($imageCachePath)) {
            $imageFiles = glob($imageCachePath . '/*');
            foreach ($imageFiles as $file) {
                if (is_file($file) && filemtime($file) < strtotime('-7 days')) {
                    $fileSize = filesize($file);
                    if (unlink($file)) {
                        $deletedFiles++;
                        $freedSpace += $fileSize;
                    }
                }
            }
        }

        Log::info('Limpieza de archivos temporales completada', [
            'deleted_files' => $deletedFiles,
            'freed_space' => $this->formatBytes($freedSpace)
        ]);
    }

    /**
     * Limpiar respaldos antiguos
     */
    protected function cleanupBackups(): void
    {
        Log::info('Iniciando limpieza de respaldos antiguos');

        $deletedBackups = 0;
        $freedSpace = 0;

        $backupPath = storage_path('app/backups');
        if (is_dir($backupPath)) {
            $backupFiles = glob($backupPath . '/*');
            foreach ($backupFiles as $file) {
                if (is_file($file) && filemtime($file) < strtotime("-{$this->retentionDays} days")) {
                    $fileSize = filesize($file);
                    if (unlink($file)) {
                        $deletedBackups++;
                        $freedSpace += $fileSize;
                    }
                }
            }
        }

        Log::info('Limpieza de respaldos completada', [
            'deleted_backups' => $deletedBackups,
            'freed_space' => $this->formatBytes($freedSpace)
        ]);
    }

    /**
     * Limpiar notificaciones antiguas
     */
    protected function cleanupNotifications(): void
    {
        Log::info('Iniciando limpieza de notificaciones antiguas');

        $deletedNotifications = 0;

        // Limpiar notificaciones leídas antiguas
        $deletedNotifications = DB::table('notifications')
            ->where('read_at', '!=', null)
            ->where('created_at', '<', now()->subDays($this->retentionDays))
            ->delete();

        // Limpiar notificaciones no leídas muy antiguas
        $deletedNotifications += DB::table('notifications')
            ->where('read_at', null)
            ->where('created_at', '<', now()->subDays($this->retentionDays * 2))
            ->delete();

        Log::info('Limpieza de notificaciones completada', [
            'deleted_notifications' => $deletedNotifications
        ]);
    }

    /**
     * Limpiar logs de actividad
     */
    protected function cleanupActivityLogs(): void
    {
        Log::info('Iniciando limpieza de logs de actividad');

        $deletedLogs = 0;

        // Limpiar logs de actividad antiguos
        if (DB::getSchemaBuilder()->hasTable('activity_logs')) {
            $deletedLogs = DB::table('activity_logs')
                ->where('created_at', '<', now()->subDays($this->retentionDays))
                ->delete();
        }

        // Limpiar logs de sistema
        if (DB::getSchemaBuilder()->hasTable('system_logs')) {
            $deletedLogs += DB::table('system_logs')
                ->where('created_at', '<', now()->subDays($this->retentionDays))
                ->delete();
        }

        Log::info('Limpieza de logs de actividad completada', [
            'deleted_logs' => $deletedLogs
        ]);
    }

    /**
     * Limpieza completa del sistema
     */
    protected function fullCleanup(): void
    {
        Log::info('Iniciando limpieza completa del sistema');

        $totalDeleted = 0;
        $totalFreedSpace = 0;

        // Ejecutar todas las limpiezas
        $this->cleanupLogs();
        $this->cleanupCache();
        $this->cleanupSessions();
        $this->cleanupTempFiles();
        $this->cleanupBackups();
        $this->cleanupNotifications();
        $this->cleanupActivityLogs();

        // Limpiar directorios vacíos
        $this->cleanupEmptyDirectories();

        // Optimizar base de datos
        $this->optimizeDatabase();

        Log::info('Limpieza completa del sistema finalizada', [
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Limpiar directorios vacíos
     */
    protected function cleanupEmptyDirectories(): void
    {
        Log::info('Limpiando directorios vacíos');

        $directories = [
            storage_path('app/temp'),
            storage_path('app/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs')
        ];

        $deletedDirs = 0;

        foreach ($directories as $dir) {
            if (is_dir($dir)) {
                $this->removeEmptyDirectories($dir);
                $deletedDirs++;
            }
        }

        Log::info('Limpieza de directorios vacíos completada', [
            'deleted_directories' => $deletedDirs
        ]);
    }

    /**
     * Remover directorios vacíos recursivamente
     */
    protected function removeEmptyDirectories(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        $files = array_diff($files, ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeEmptyDirectories($path);
            }
        }

        $files = scandir($dir);
        $files = array_diff($files, ['.', '..']);

        if (empty($files)) {
            rmdir($dir);
        }
    }

    /**
     * Optimizar base de datos
     */
    protected function optimizeDatabase(): void
    {
        Log::info('Optimizando base de datos');

        try {
            // Optimizar tablas principales
            $tables = ['users', 'sessions', 'notifications', 'activity_logs'];

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::statement("OPTIMIZE TABLE {$table}");
                }
            }

            Log::info('Optimización de base de datos completada');

        } catch (\Exception $e) {
            Log::error('Error al optimizar base de datos', [
                'error' => $e->getMessage()
            ]);
        }
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
        Log::error("Job de limpieza falló permanentemente", [
            'type' => $this->cleanupType,
            'error' => $exception->getMessage(),
            'data' => $this->cleanupData
        ]);
    }
}

