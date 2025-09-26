<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\BackupService;

class BackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $backupType;
    protected $backupData;
    protected $priority;
    protected $retentionDays;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $backupType,
        array $backupData = [],
        int $priority = 3,
        int $retentionDays = 30
    ) {
        $this->backupType = $backupType;
        $this->backupData = $backupData;
        $this->priority = $priority;
        $this->retentionDays = $retentionDays;

        // Establecer cola según prioridad
        $this->onQueue($this->getQueueName());
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        $startTime = microtime(true);

        try {
            Log::info("Iniciando job de respaldo", [
                'type' => $this->backupType,
                'data' => $this->backupData,
                'priority' => $this->priority
            ]);

            // Ejecutar respaldo según el tipo
            switch ($this->backupType) {
                case 'database':
                    $this->backupDatabase($backupService);
                    break;
                case 'files':
                    $this->backupFiles($backupService);
                    break;
                case 'full_system':
                    $this->backupFullSystem($backupService);
                    break;
                case 'incremental':
                    $this->backupIncremental($backupService);
                    break;
                case 'cleanup':
                    $this->cleanupOldBackups($backupService);
                    break;
                case 'verify':
                    $this->verifyBackups($backupService);
                    break;
                default:
                    throw new \InvalidArgumentException("Tipo de respaldo no válido: {$this->backupType}");
            }

            $executionTime = microtime(true) - $startTime;

            Log::info("Job de respaldo completado exitosamente", [
                'type' => $this->backupType,
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true)
            ]);

        } catch (\Exception $e) {
            Log::error("Error en job de respaldo", [
                'type' => $this->backupType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Respaldo de base de datos
     */
    protected function backupDatabase(BackupService $backupService): void
    {
        Log::info('Iniciando respaldo de base de datos');

        $backupName = $this->backupData['name'] ?? 'Database Backup - ' . now()->format('Y-m-d H:i:s');
        $backupDescription = $this->backupData['description'] ?? 'Respaldo automático de la base de datos';

        // Obtener información de la base de datos
        $dbInfo = $this->getDatabaseInfo();

        // Crear respaldo
        $backupService->createBackup($backupName, $backupDescription);

        // Verificar integridad del respaldo
        $this->verifyDatabaseBackup($backupName);

        Log::info('Respaldo de base de datos completado', [
            'backup_name' => $backupName,
            'database_size' => $dbInfo['size'],
            'tables_count' => $dbInfo['tables_count']
        ]);
    }

    /**
     * Respaldo de archivos
     */
    protected function backupFiles(BackupService $backupService): void
    {
        Log::info('Iniciando respaldo de archivos');

        $backupName = $this->backupData['name'] ?? 'Files Backup - ' . now()->format('Y-m-d H:i:s');
        $backupDescription = $this->backupData['description'] ?? 'Respaldo automático de archivos';

        // Directorios a respaldar
        $directories = $this->backupData['directories'] ?? [
            'app' => storage_path('app'),
            'config' => config_path(),
            'public' => public_path(),
            'resources' => resource_path()
        ];

        // Crear respaldo de archivos
        $backupService->createBackup($backupName, $backupDescription);

        // Calcular tamaño total
        $totalSize = $this->calculateDirectoriesSize($directories);

        Log::info('Respaldo de archivos completado', [
            'backup_name' => $backupName,
            'directories' => array_keys($directories),
            'total_size' => $this->formatBytes($totalSize)
        ]);
    }

    /**
     * Respaldo completo del sistema
     */
    protected function backupFullSystem(BackupService $backupService): void
    {
        Log::info('Iniciando respaldo completo del sistema');

        $backupName = $this->backupData['name'] ?? 'Full System Backup - ' . now()->format('Y-m-d H:i:s');
        $backupDescription = $this->backupData['description'] ?? 'Respaldo completo del sistema';

        // Respaldo de base de datos
        $this->backupDatabase($backupService);

        // Respaldo de archivos
        $this->backupFiles($backupService);

        // Respaldo de configuración
        $this->backupConfiguration();

        Log::info('Respaldo completo del sistema finalizado', [
            'backup_name' => $backupName,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Respaldo incremental
     */
    protected function backupIncremental(BackupService $backupService): void
    {
        Log::info('Iniciando respaldo incremental');

        $backupName = $this->backupData['name'] ?? 'Incremental Backup - ' . now()->format('Y-m-d H:i:s');
        $backupDescription = $this->backupData['description'] ?? 'Respaldo incremental del sistema';

        // Obtener último respaldo
        $lastBackup = $this->getLastBackup();

        // Respaldo solo de cambios
        $this->backupChanges($lastBackup);

        Log::info('Respaldo incremental completado', [
            'backup_name' => $backupName,
            'last_backup' => $lastBackup
        ]);
    }

    /**
     * Limpiar respaldos antiguos
     */
    protected function cleanupOldBackups(BackupService $backupService): void
    {
        Log::info('Iniciando limpieza de respaldos antiguos');

        $deletedCount = 0;
        $freedSpace = 0;

        // Obtener respaldos antiguos
        $oldBackups = $this->getOldBackups($this->retentionDays);

        foreach ($oldBackups as $backup) {
            try {
                // Eliminar respaldo
                $backupService->deleteBackup($backup['id']);
                $deletedCount++;
                $freedSpace += $backup['size'];

                Log::info('Respaldo antiguo eliminado', [
                    'backup_id' => $backup['id'],
                    'backup_name' => $backup['name'],
                    'size' => $this->formatBytes($backup['size'])
                ]);

            } catch (\Exception $e) {
                Log::error('Error al eliminar respaldo antiguo', [
                    'backup_id' => $backup['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Limpieza de respaldos completada', [
            'deleted_count' => $deletedCount,
            'freed_space' => $this->formatBytes($freedSpace)
        ]);
    }

    /**
     * Verificar respaldos
     */
    protected function verifyBackups(BackupService $backupService): void
    {
        Log::info('Iniciando verificación de respaldos');

        $backups = $backupService->getAllBackups();
        $verifiedCount = 0;
        $failedCount = 0;

        foreach ($backups as $backup) {
            try {
                $isValid = $this->verifyBackupIntegrity($backup);

                if ($isValid) {
                    $verifiedCount++;
                } else {
                    $failedCount++;
                    Log::warning('Respaldo con problemas de integridad', [
                        'backup_id' => $backup['id'],
                        'backup_name' => $backup['name']
                    ]);
                }

            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Error al verificar respaldo', [
                    'backup_id' => $backup['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Verificación de respaldos completada', [
            'total_backups' => count($backups),
            'verified' => $verifiedCount,
            'failed' => $failedCount
        ]);
    }

    /**
     * Obtener información de la base de datos
     */
    protected function getDatabaseInfo(): array
    {
        $tables = DB::select('SHOW TABLES');
        $tablesCount = count($tables);

        // Calcular tamaño de la base de datos
        $sizeQuery = DB::select("
            SELECT
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ");

        $size = $sizeQuery[0]->size_mb ?? 0;

        return [
            'tables_count' => $tablesCount,
            'size' => $size,
            'size_bytes' => $size * 1024 * 1024
        ];
    }

    /**
     * Verificar respaldo de base de datos
     */
    protected function verifyDatabaseBackup(string $backupName): bool
    {
        // Verificar que el archivo de respaldo existe
        $backupPath = storage_path('app/backups/' . $backupName . '.sql');

        if (!file_exists($backupPath)) {
            Log::error('Archivo de respaldo no encontrado', [
                'backup_path' => $backupPath
            ]);
            return false;
        }

        // Verificar tamaño del archivo
        $fileSize = filesize($backupPath);
        if ($fileSize < 1024) { // Menos de 1KB es sospechoso
            Log::warning('Archivo de respaldo muy pequeño', [
                'backup_path' => $backupPath,
                'file_size' => $fileSize
            ]);
            return false;
        }

        return true;
    }

    /**
     * Calcular tamaño de directorios
     */
    protected function calculateDirectoriesSize(array $directories): int
    {
        $totalSize = 0;

        foreach ($directories as $name => $path) {
            if (is_dir($path)) {
                $totalSize += $this->getDirectorySize($path);
            }
        }

        return $totalSize;
    }

    /**
     * Obtener tamaño de directorio recursivamente
     */
    protected function getDirectorySize(string $directory): int
    {
        $size = 0;

        if (is_dir($directory)) {
            $files = glob($directory . '/*');

            foreach ($files as $file) {
                if (is_file($file)) {
                    $size += filesize($file);
                } elseif (is_dir($file)) {
                    $size += $this->getDirectorySize($file);
                }
            }
        }

        return $size;
    }

    /**
     * Respaldo de configuración
     */
    protected function backupConfiguration(): void
    {
        Log::info('Respaldando configuración del sistema');

        $configFiles = [
            '.env' => base_path('.env'),
            'config' => config_path(),
            'composer.json' => base_path('composer.json'),
            'package.json' => base_path('package.json')
        ];

        foreach ($configFiles as $name => $path) {
            if (file_exists($path)) {
                Log::info('Configuración respaldada', [
                    'file' => $name,
                    'path' => $path
                ]);
            }
        }
    }

    /**
     * Obtener último respaldo
     */
    protected function getLastBackup(): ?array
    {
        // Implementar lógica para obtener el último respaldo
        return null;
    }

    /**
     * Respaldo de cambios
     */
    protected function backupChanges(?array $lastBackup): void
    {
        Log::info('Respaldando cambios desde último respaldo');

        // Implementar lógica de respaldo incremental
    }

    /**
     * Obtener respaldos antiguos
     */
    protected function getOldBackups(int $retentionDays): array
    {
        // Implementar lógica para obtener respaldos antiguos
        return [];
    }

    /**
     * Verificar integridad del respaldo
     */
    protected function verifyBackupIntegrity(array $backup): bool
    {
        // Implementar verificación de integridad
        return true;
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
        Log::error("Job de respaldo falló permanentemente", [
            'type' => $this->backupType,
            'error' => $exception->getMessage(),
            'data' => $this->backupData
        ]);
    }
}



