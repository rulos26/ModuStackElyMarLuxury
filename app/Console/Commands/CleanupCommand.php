<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Services\JobService;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:manage
                            {action : Acción a realizar (run|schedule|status|logs|cache|sessions|temp|full)}
                            {--type=full : Tipo de limpieza (logs|cache|sessions|temp|backups|notifications|full)}
                            {--retention=30 : Días de retención}
                            {--force : Forzar limpieza sin confirmación}
                            {--dry-run : Simular limpieza sin ejecutar}
                            {--schedule= : Programar limpieza (daily|weekly|monthly)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar limpieza del sistema';

    protected $jobService;

    /**
     * Create a new command instance.
     */
    public function __construct(JobService $jobService)
    {
        parent::__construct();
        $this->jobService = $jobService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $type = $this->option('type');
        $retention = (int) $this->option('retention');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $schedule = $this->option('schedule');

        switch ($action) {
            case 'run':
                return $this->runCleanup($type, $retention, $force, $dryRun);
            case 'schedule':
                return $this->scheduleCleanup($type, $schedule, $retention);
            case 'status':
                return $this->showCleanupStatus();
            case 'logs':
                return $this->cleanupLogs($retention, $force, $dryRun);
            case 'cache':
                return $this->cleanupCache($force, $dryRun);
            case 'sessions':
                return $this->cleanupSessions($retention, $force, $dryRun);
            case 'temp':
                return $this->cleanupTempFiles($retention, $force, $dryRun);
            case 'full':
                return $this->fullCleanup($retention, $force, $dryRun);
            default:
                $this->error('Acción no válida. Use: run, schedule, status, logs, cache, sessions, temp, full');
                return 1;
        }
    }

    /**
     * Ejecutar limpieza
     */
    protected function runCleanup(string $type, int $retention, bool $force, bool $dryRun): int
    {
        $this->info("🧹 Ejecutando limpieza de tipo: {$type}");

        if ($dryRun) {
            $this->warn('🔍 Modo de simulación activado');
        }

        try {
            $cleanupData = [
                'type' => $type,
                'retention_days' => $retention,
                'force' => $force,
                'dry_run' => $dryRun,
                'executed_by' => 'artisan_command'
            ];

            // Despachar job de limpieza
            $this->jobService->dispatchCleanupJob($type, $cleanupData, $retention);

            $this->info('✅ Limpieza programada exitosamente');
            $this->line("Tipo: {$type}");
            $this->line("Retención: {$retention} días");

            if ($dryRun) {
                $this->line('🔍 Se ejecutará en modo de simulación');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al ejecutar limpieza: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Programar limpieza
     */
    protected function scheduleCleanup(string $type, ?string $schedule, int $retention): int
    {
        if (!$schedule) {
            $this->error('❌ Debe especificar --schedule');
            return 1;
        }

        $this->info("📅 Programando limpieza {$schedule} de tipo: {$type}");

        try {
            $scheduleData = [
                'type' => $type,
                'schedule' => $schedule,
                'retention_days' => $retention,
                'created_by' => 'artisan_command',
                'created_at' => now()->toISOString()
            ];

            // Guardar programación
            $schedules = \Cache::get('cleanup_schedules', []);
            $schedules[] = $scheduleData;
            \Cache::put('cleanup_schedules', $schedules, 86400 * 30); // 30 días

            // Despachar job de programación
            $this->jobService->dispatchSystemJob('cleanup_schedule', $scheduleData, 3);

            $this->info('✅ Limpieza programada exitosamente');
            $this->line("Tipo: {$type}");
            $this->line("Horario: {$schedule}");
            $this->line("Retención: {$retention} días");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al programar limpieza: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Mostrar estado de limpieza
     */
    protected function showCleanupStatus(): int
    {
        $this->info('📊 ESTADO DE LIMPIEZA DEL SISTEMA');
        $this->line('==================================');

        try {
            // Mostrar programaciones
            $schedules = \Cache::get('cleanup_schedules', []);
            if (!empty($schedules)) {
                $this->line('');
                $this->info('📅 Limpiezas programadas:');
                foreach ($schedules as $schedule) {
                    $this->line("  - {$schedule['type']} ({$schedule['schedule']}) - {$schedule['retention_days']} días");
                }
            } else {
                $this->line('No hay limpiezas programadas');
            }

            // Mostrar estadísticas del sistema
            $this->showSystemStats();

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al obtener estado: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Limpiar logs
     */
    protected function cleanupLogs(int $retention, bool $force, bool $dryRun): int
    {
        $this->info("📝 Limpiando logs antiguos (retención: {$retention} días)");

        if ($dryRun) {
            $this->warn('🔍 Modo de simulación activado');
        }

        try {
            $logPath = storage_path('logs');
            $deletedFiles = 0;
            $freedSpace = 0;

            if (!is_dir($logPath)) {
                $this->warn('⚠️  Directorio de logs no encontrado');
                return 0;
            }

            $logFiles = glob($logPath . '/laravel-*.log');
            $cutoffDate = now()->subDays($retention);

            foreach ($logFiles as $logFile) {
                if (filemtime($logFile) < $cutoffDate->timestamp) {
                    $fileSize = filesize($logFile);

                    if (!$dryRun) {
                        if (unlink($logFile)) {
                            $deletedFiles++;
                            $freedSpace += $fileSize;
                        }
                    } else {
                        $deletedFiles++;
                        $freedSpace += $fileSize;
                    }
                }
            }

            $this->info('✅ Limpieza de logs completada');
            $this->line("Archivos eliminados: {$deletedFiles}");
            $this->line("Espacio liberado: " . $this->formatBytes($freedSpace));

            if ($dryRun) {
                $this->line('🔍 Simulación completada - no se eliminaron archivos');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al limpiar logs: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Limpiar cache
     */
    protected function cleanupCache(bool $force, bool $dryRun): int
    {
        $this->info('💾 Limpiando cache del sistema');

        if ($dryRun) {
            $this->warn('🔍 Modo de simulación activado');
        }

        try {
            if (!$dryRun) {
                Cache::flush();
                $this->info('✅ Cache limpiado exitosamente');
            } else {
                $this->info('🔍 Simulación: Cache sería limpiado');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al limpiar cache: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Limpiar sesiones
     */
    protected function cleanupSessions(int $retention, bool $force, bool $dryRun): int
    {
        $this->info("🔐 Limpiando sesiones expiradas (retención: {$retention} días)");

        if ($dryRun) {
            $this->warn('🔍 Modo de simulación activado');
        }

        try {
            $deletedSessions = 0;

            if (config('session.driver') === 'database') {
                $cutoffTime = now()->subDays($retention)->timestamp;

                if (!$dryRun) {
                    $deletedSessions = DB::table('sessions')
                        ->where('last_activity', '<', $cutoffTime)
                        ->delete();
                } else {
                    $deletedSessions = DB::table('sessions')
                        ->where('last_activity', '<', $cutoffTime)
                        ->count();
                }
            }

            $this->info('✅ Limpieza de sesiones completada');
            $this->line("Sesiones eliminadas: {$deletedSessions}");

            if ($dryRun) {
                $this->line('🔍 Simulación completada - no se eliminaron sesiones');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al limpiar sesiones: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Limpiar archivos temporales
     */
    protected function cleanupTempFiles(int $retention, bool $force, bool $dryRun): int
    {
        $this->info("🗂️  Limpiando archivos temporales (retención: {$retention} días)");

        if ($dryRun) {
            $this->warn('🔍 Modo de simulación activado');
        }

        try {
            $deletedFiles = 0;
            $freedSpace = 0;

            $tempPaths = [
                storage_path('app/temp'),
                storage_path('app/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views')
            ];

            $cutoffDate = now()->subDays($retention);

            foreach ($tempPaths as $tempPath) {
                if (is_dir($tempPath)) {
                    $files = glob($tempPath . '/*');
                    foreach ($files as $file) {
                        if (is_file($file) && filemtime($file) < $cutoffDate->timestamp) {
                            $fileSize = filesize($file);

                            if (!$dryRun) {
                                if (unlink($file)) {
                                    $deletedFiles++;
                                    $freedSpace += $fileSize;
                                }
                            } else {
                                $deletedFiles++;
                                $freedSpace += $fileSize;
                            }
                        }
                    }
                }
            }

            $this->info('✅ Limpieza de archivos temporales completada');
            $this->line("Archivos eliminados: {$deletedFiles}");
            $this->line("Espacio liberado: " . $this->formatBytes($freedSpace));

            if ($dryRun) {
                $this->line('🔍 Simulación completada - no se eliminaron archivos');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al limpiar archivos temporales: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Limpieza completa
     */
    protected function fullCleanup(int $retention, bool $force, bool $dryRun): int
    {
        $this->info("🧹 Ejecutando limpieza completa del sistema");

        if ($dryRun) {
            $this->warn('🔍 Modo de simulación activado');
        }

        try {
            $cleanupData = [
                'type' => 'full_cleanup',
                'retention_days' => $retention,
                'force' => $force,
                'dry_run' => $dryRun,
                'executed_by' => 'artisan_command'
            ];

            // Despachar job de limpieza completa
            $this->jobService->dispatchCleanupJob('full_cleanup', $cleanupData, $retention);

            $this->info('✅ Limpieza completa programada exitosamente');
            $this->line("Retención: {$retention} días");

            if ($dryRun) {
                $this->line('🔍 Se ejecutará en modo de simulación');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al ejecutar limpieza completa: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Mostrar estadísticas del sistema
     */
    protected function showSystemStats(): void
    {
        $this->line('');
        $this->info('📊 ESTADÍSTICAS DEL SISTEMA');
        $this->line('============================');

        // Espacio en disco
        $totalSpace = disk_total_space(storage_path());
        $freeSpace = disk_free_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;
        $diskUsage = ($usedSpace / $totalSpace) * 100;

        $this->line("Disco utilizado: " . $this->formatBytes($usedSpace) . " / " . $this->formatBytes($totalSpace) . " ({$diskUsage}%)");

        // Memoria
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $this->line("Memoria utilizada: " . $this->formatBytes($memoryUsage) . " / {$memoryLimit}");

        // Archivos de log
        $logPath = storage_path('logs');
        if (is_dir($logPath)) {
            $logFiles = glob($logPath . '/laravel-*.log');
            $this->line("Archivos de log: " . count($logFiles));
        }

        // Sesiones
        if (config('session.driver') === 'database') {
            $sessionCount = DB::table('sessions')->count();
            $this->line("Sesiones activas: {$sessionCount}");
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
}

