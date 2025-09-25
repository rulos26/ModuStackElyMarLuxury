<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\JobService;
use App\Services\DynamicDriverService;
use App\Services\BackupService;
use App\Services\NotificationService;

class SystemStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:status
                            {--detailed : Mostrar información detallada}
                            {--json : Mostrar salida en formato JSON}
                            {--save : Guardar reporte en archivo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostrar estado completo del sistema integrado';

    protected $jobService;
    protected $dynamicDriverService;
    protected $backupService;
    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        JobService $jobService,
        DynamicDriverService $dynamicDriverService,
        BackupService $backupService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->jobService = $jobService;
        $this->dynamicDriverService = $dynamicDriverService;
        $this->backupService = $backupService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando estado del sistema integrado...');
        $this->line('');

        $status = $this->getSystemStatus();

        if ($this->option('json')) {
            $this->outputJson($status);
        } else {
            $this->outputStatus($status);
        }

        if ($this->option('save')) {
            $this->saveReport($status);
        }

        return 0;
    }

    /**
     * Obtener estado completo del sistema
     */
    protected function getSystemStatus(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'system' => $this->getSystemInfo(),
            'database' => $this->getDatabaseStatus(),
            'cache' => $this->getCacheStatus(),
            'drivers' => $this->getDriversStatus(),
            'jobs' => $this->getJobsStatus(),
            'backups' => $this->getBackupsStatus(),
            'notifications' => $this->getNotificationsStatus(),
            'performance' => $this->getPerformanceStatus(),
            'health' => $this->getHealthStatus()
        ];
    }

    /**
     * Obtener información del sistema
     */
    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'disk_usage' => $this->getDiskUsage(),
            'memory_usage' => $this->getMemoryUsage()
        ];
    }

    /**
     * Obtener estado de la base de datos
     */
    protected function getDatabaseStatus(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();

            $tables = DB::select("SHOW TABLES");
            $tableCount = count($tables);

            $sizeQuery = DB::select("
                SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
            ");

            $size = $sizeQuery[0]->size_mb ?? 0;

            return [
                'status' => 'connected',
                'driver' => $connection->getDriverName(),
                'database' => $connection->getDatabaseName(),
                'tables_count' => $tableCount,
                'size_mb' => $size,
                'connection_time' => $this->getConnectionTime()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado del cache
     */
    protected function getCacheStatus(): array
    {
        try {
            $driver = config('cache.default');
            $store = Cache::store();

            // Test cache
            $testKey = 'system_status_test_' . time();
            Cache::put($testKey, 'test', 60);
            $testResult = Cache::get($testKey) === 'test';
            Cache::forget($testKey);

            return [
                'driver' => $driver,
                'status' => $testResult ? 'working' : 'error',
                'prefix' => config('cache.prefix'),
                'ttl' => config('cache.ttl', 3600)
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado de los drivers
     */
    protected function getDriversStatus(): array
    {
        try {
            $driversStatus = $this->dynamicDriverService->getAllDriversStatus();

            $status = [];
            foreach ($driversStatus as $service => $driverStatus) {
                $status[$service] = [
                    'current' => $driverStatus['current'] ?? 'unknown',
                    'supported' => $driverStatus['supported'] ?? [],
                    'status' => $driverStatus['status'] ?? 'unknown'
                ];
            }

            return $status;

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado de los jobs
     */
    protected function getJobsStatus(): array
    {
        try {
            $jobStats = $this->jobService->getJobStatistics();
            $pendingJobs = $this->jobService->getPendingJobs();
            $health = $this->jobService->checkJobHealth();

            return [
                'statistics' => $jobStats,
                'pending_jobs' => $pendingJobs,
                'health' => $health
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado de los respaldos
     */
    protected function getBackupsStatus(): array
    {
        try {
            // Simular respaldos para testing
            return [
                'total_backups' => 0,
                'recent_backups' => [],
                'last_backup' => null
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado de las notificaciones
     */
    protected function getNotificationsStatus(): array
    {
        try {
            // Simular notificaciones para testing
            return [
                'total_notifications' => 0,
                'unread_notifications' => 0,
                'recent_notifications' => []
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estado de rendimiento
     */
    protected function getPerformanceStatus(): array
    {
        return [
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'load_average' => $this->getLoadAverage(),
            'uptime' => $this->getUptime()
        ];
    }

    /**
     * Obtener estado de salud
     */
    protected function getHealthStatus(): array
    {
        $health = [
            'overall' => 'healthy',
            'issues' => [],
            'timestamp' => now()->toISOString()
        ];

        // Verificar memoria
        $memoryUsage = $this->getMemoryUsage();
        if ($memoryUsage['percentage'] > 85) {
            $health['overall'] = 'warning';
            $health['issues'][] = 'Uso de memoria alto: ' . $memoryUsage['percentage'] . '%';
        }

        // Verificar disco
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage['percentage'] > 90) {
            $health['overall'] = 'critical';
            $health['issues'][] = 'Espacio en disco bajo: ' . $diskUsage['percentage'] . '%';
        }

        // Verificar jobs
        $jobHealth = $this->jobService->checkJobHealth();
        if (!$jobHealth['healthy']) {
            $health['overall'] = 'warning';
            $health['issues'] = array_merge($health['issues'], $jobHealth['issues']);
        }

        return $health;
    }

    /**
     * Obtener uso de memoria
     */
    protected function getMemoryUsage(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $percentage = ($currentUsage / $memoryLimitBytes) * 100;

        return [
            'current' => $this->formatBytes($currentUsage),
            'peak' => $this->formatBytes($peakUsage),
            'limit' => $memoryLimit,
            'percentage' => round($percentage, 2)
        ];
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
            'total' => $this->formatBytes($totalSpace),
            'used' => $this->formatBytes($usedSpace),
            'free' => $this->formatBytes($freeSpace),
            'percentage' => round($percentage, 2)
        ];
    }

    /**
     * Obtener tiempo de conexión
     */
    protected function getConnectionTime(): float
    {
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            return round((microtime(true) - $start) * 1000, 2);
        } catch (\Exception $e) {
            return -1;
        }
    }

    /**
     * Obtener carga promedio
     */
    protected function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => $load[0],
                '5min' => $load[1],
                '15min' => $load[2]
            ];
        }

        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    /**
     * Obtener tiempo de actividad
     */
    protected function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime)[0];
            return $this->formatUptime($uptime);
        }

        return 'N/A';
    }

    /**
     * Formatear tiempo de actividad
     */
    protected function formatUptime(float $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "{$days}d {$hours}h {$minutes}m";
    }

    /**
     * Convertir a bytes
     */
    protected function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
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
     * Mostrar estado en consola
     */
    protected function outputStatus(array $status): void
    {
        $this->info('📊 ESTADO DEL SISTEMA INTEGRADO');
        $this->line('================================');

        // Información del sistema
        $this->line('');
        $this->info('🖥️  Sistema:');
        $this->line("  PHP: {$status['system']['php_version']}");
        $this->line("  Laravel: {$status['system']['laravel_version']}");
        $this->line("  Entorno: {$status['system']['environment']}");
        $this->line("  Memoria: {$status['system']['memory_usage']['current']} / {$status['system']['memory_usage']['limit']}");

        // Base de datos
        $this->line('');
        $this->info('🗄️  Base de Datos:');
        if ($status['database']['status'] === 'connected') {
            $this->line("  Estado: ✅ Conectada");
            $this->line("  Driver: {$status['database']['driver']}");
            $this->line("  Tablas: {$status['database']['tables_count']}");
            $this->line("  Tamaño: {$status['database']['size_mb']} MB");
        } else {
            $this->line("  Estado: ❌ Error");
            $this->line("  Error: {$status['database']['error']}");
        }

        // Cache
        $this->line('');
        $this->info('💾 Cache:');
        if ($status['cache']['status'] === 'working') {
            $this->line("  Estado: ✅ Funcionando");
            $this->line("  Driver: {$status['cache']['driver']}");
        } else {
            $this->line("  Estado: ❌ Error");
            $this->line("  Error: {$status['cache']['error']}");
        }

        // Jobs
        $this->line('');
        $this->info('⚙️  Jobs:');
        $this->line("  Total: {$status['jobs']['statistics']['total_jobs']}");
        $this->line("  Exitosos: {$status['jobs']['statistics']['successful_jobs']}");
        $this->line("  Fallidos: {$status['jobs']['statistics']['failed_jobs']}");

        // Salud del sistema
        $this->line('');
        $this->info('🏥 Salud del Sistema:');
        $healthIcon = $status['health']['overall'] === 'healthy' ? '✅' :
                      ($status['health']['overall'] === 'warning' ? '⚠️' : '❌');
        $this->line("  Estado: {$healthIcon} " . strtoupper($status['health']['overall']));

        if (!empty($status['health']['issues'])) {
            $this->line("  Problemas:");
            foreach ($status['health']['issues'] as $issue) {
                $this->line("    - {$issue}");
            }
        }

        if ($this->option('detailed')) {
            $this->showDetailedInfo($status);
        }
    }

    /**
     * Mostrar información detallada
     */
    protected function showDetailedInfo(array $status): void
    {
        $this->line('');
        $this->info('📋 INFORMACIÓN DETALLADA');
        $this->line('========================');

        // Drivers
        $this->line('');
        $this->info('🔧 Drivers:');
        foreach ($status['drivers'] as $service => $driver) {
            $this->line("  {$service}: {$driver['current']} ({$driver['status']})");
        }

        // Respaldos
        $this->line('');
        $this->info('💾 Respaldos:');
        $this->line("  Total: {$status['backups']['total_backups']}");

        // Notificaciones
        $this->line('');
        $this->info('🔔 Notificaciones:');
        $this->line("  Total: {$status['notifications']['total_notifications']}");
        $this->line("  No leídas: {$status['notifications']['unread_notifications']}");
    }

    /**
     * Mostrar salida en JSON
     */
    protected function outputJson(array $status): void
    {
        $this->line(json_encode($status, JSON_PRETTY_PRINT));
    }

    /**
     * Guardar reporte en archivo
     */
    protected function saveReport(array $status): void
    {
        $filename = 'system_status_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('app/reports/' . $filename);

        // Crear directorio si no existe
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        file_put_contents($filepath, json_encode($status, JSON_PRETTY_PRINT));

        $this->info("📄 Reporte guardado en: {$filepath}");
    }
}
