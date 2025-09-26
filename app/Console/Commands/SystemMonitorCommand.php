<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\JobService;
use App\Services\DynamicDriverService;

class SystemMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:monitor
                            {action : Acción a realizar (start|stop|status|alerts|health)}
                            {--interval=60 : Intervalo de monitoreo en segundos}
                            {--duration=3600 : Duración del monitoreo en segundos}
                            {--threshold=80 : Umbral de alertas (porcentaje)}
                            {--log : Guardar logs de monitoreo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorear el sistema en tiempo real';

    protected $jobService;
    protected $dynamicDriverService;
    protected $isMonitoring = false;

    /**
     * Create a new command instance.
     */
    public function __construct(
        JobService $jobService,
        DynamicDriverService $dynamicDriverService
    ) {
        parent::__construct();
        $this->jobService = $jobService;
        $this->dynamicDriverService = $dynamicDriverService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $interval = (int) $this->option('interval');
        $duration = (int) $this->option('duration');
        $threshold = (int) $this->option('threshold');
        $log = $this->option('log');

        switch ($action) {
            case 'start':
                return $this->startMonitoring($interval, $duration, $threshold, $log);
            case 'stop':
                return $this->stopMonitoring();
            case 'status':
                return $this->showMonitoringStatus();
            case 'alerts':
                return $this->showAlerts();
            case 'health':
                return $this->checkHealth();
            default:
                $this->error('Acción no válida. Use: start, stop, status, alerts, health');
                return 1;
        }
    }

    /**
     * Iniciar monitoreo
     */
    protected function startMonitoring(int $interval, int $duration, int $threshold, bool $log): int
    {
        $this->info('🔍 Iniciando monitoreo del sistema...');

        try {
            $monitoringData = [
                'started_at' => now()->toISOString(),
                'interval' => $interval,
                'duration' => $duration,
                'threshold' => $threshold,
                'log' => $log,
                'status' => 'active',
                'end_time' => now()->addSeconds($duration)->toISOString()
            ];

            Cache::put('system_monitoring', $monitoringData, $duration);

            // Despachar job de monitoreo
            $this->jobService->dispatchSystemJob('system_monitoring', $monitoringData, 2);

            $this->info('✅ Monitoreo iniciado exitosamente');
            $this->line("Intervalo: {$interval} segundos");
            $this->line("Duración: {$duration} segundos");
            $this->line("Umbral: {$threshold}%");
            $this->line("Finaliza: {$monitoringData['end_time']}");

            if ($log) {
                $this->line('📝 Logs de monitoreo habilitados');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al iniciar monitoreo: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Detener monitoreo
     */
    protected function stopMonitoring(): int
    {
        $this->info('🔍 Deteniendo monitoreo del sistema...');

        try {
            $monitoringData = Cache::get('system_monitoring', []);

            if (empty($monitoringData) || $monitoringData['status'] !== 'active') {
                $this->warn('⚠️  No hay monitoreo activo');
                return 0;
            }

            $monitoringData['status'] = 'stopped';
            $monitoringData['stopped_at'] = now()->toISOString();
            $monitoringData['actual_duration'] = $this->calculateActualDuration($monitoringData);

            Cache::put('system_monitoring', $monitoringData, 3600);

            $this->info('✅ Monitoreo detenido exitosamente');
            $this->line("Duración real: {$monitoringData['actual_duration']} segundos");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al detener monitoreo: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Mostrar estado del monitoreo
     */
    protected function showMonitoringStatus(): int
    {
        $monitoringData = Cache::get('system_monitoring', []);

        if (empty($monitoringData) || $monitoringData['status'] !== 'active') {
            $this->info('📊 No hay monitoreo activo');
            return 0;
        }

        $this->info('📊 ESTADO DEL MONITOREO');
        $this->line('========================');

        $this->line("Estado: {$monitoringData['status']}");
        $this->line("Iniciado: {$monitoringData['started_at']}");
        $this->line("Intervalo: {$monitoringData['interval']} segundos");
        $this->line("Umbral: {$monitoringData['threshold']}%");
        $this->line("Finaliza: {$monitoringData['end_time']}");

        $remaining = $this->calculateRemainingTime($monitoringData);
        $this->line("Tiempo restante: {$remaining} segundos");

        // Mostrar métricas actuales
        $this->showCurrentMetrics();

        return 0;
    }

    /**
     * Mostrar alertas
     */
    protected function showAlerts(): int
    {
        $this->info('🚨 ALERTAS DEL SISTEMA');
        $this->line('======================');

        $alerts = $this->getSystemAlerts();

        if (empty($alerts)) {
            $this->info('✅ No hay alertas activas');
            return 0;
        }

        foreach ($alerts as $alert) {
            $icon = $alert['level'] === 'critical' ? '🔴' :
                   ($alert['level'] === 'warning' ? '🟡' : '🟢');

            $this->line("{$icon} {$alert['title']}");
            $this->line("   {$alert['message']}");
            $this->line("   Nivel: {$alert['level']}");
            $this->line("   Timestamp: {$alert['timestamp']}");
            $this->line('');
        }

        return 0;
    }

    /**
     * Verificar salud del sistema
     */
    protected function checkHealth(): int
    {
        $this->info('🏥 VERIFICANDO SALUD DEL SISTEMA');
        $this->line('==================================');

        $health = $this->getSystemHealth();

        $icon = $health['overall'] === 'healthy' ? '✅' :
               ($health['overall'] === 'warning' ? '⚠️' : '❌');

        $this->line("Estado general: {$icon} " . strtoupper($health['overall']));

        if (!empty($health['issues'])) {
            $this->line('');
            $this->line('Problemas detectados:');
            foreach ($health['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }

        // Mostrar métricas detalladas
        $this->showDetailedHealth($health);

        return $health['overall'] === 'healthy' ? 0 : 1;
    }

    /**
     * Mostrar métricas actuales
     */
    protected function showCurrentMetrics(): void
    {
        $this->line('');
        $this->info('📈 MÉTRICAS ACTUALES');
        $this->line('====================');

        // Memoria
        $memory = $this->getMemoryUsage();
        $this->line("Memoria: {$memory['current']} / {$memory['limit']} ({$memory['percentage']}%)");

        // Disco
        $disk = $this->getDiskUsage();
        $this->line("Disco: {$disk['used']} / {$disk['total']} ({$disk['percentage']}%)");

        // Base de datos
        $db = $this->getDatabaseStatus();
        $this->line("Base de datos: {$db['status']}");

        // Jobs
        $jobs = $this->getJobsStatus();
        $this->line("Jobs: {$jobs['total']} total, {$jobs['pending']} pendientes");
    }

    /**
     * Mostrar salud detallada
     */
    protected function showDetailedHealth(array $health): void
    {
        $this->line('');
        $this->info('📊 SALUD DETALLADA');
        $this->line('==================');

        // Memoria
        $memory = $this->getMemoryUsage();
        $memoryIcon = $memory['percentage'] > 85 ? '🔴' :
                     ($memory['percentage'] > 70 ? '🟡' : '🟢');
        $this->line("{$memoryIcon} Memoria: {$memory['percentage']}%");

        // Disco
        $disk = $this->getDiskUsage();
        $diskIcon = $disk['percentage'] > 90 ? '🔴' :
                    ($disk['percentage'] > 80 ? '🟡' : '🟢');
        $this->line("{$diskIcon} Disco: {$disk['percentage']}%");

        // Base de datos
        $db = $this->getDatabaseStatus();
        $dbIcon = $db['status'] === 'connected' ? '🟢' : '🔴';
        $this->line("{$dbIcon} Base de datos: {$db['status']}");

        // Cache
        $cache = $this->getCacheStatus();
        $cacheIcon = $cache['status'] === 'working' ? '🟢' : '🔴';
        $this->line("{$cacheIcon} Cache: {$cache['status']}");
    }

    /**
     * Obtener alertas del sistema
     */
    protected function getSystemAlerts(): array
    {
        $alerts = [];

        // Verificar memoria
        $memory = $this->getMemoryUsage();
        if ($memory['percentage'] > 85) {
            $alerts[] = [
                'level' => 'critical',
                'title' => 'Uso de memoria alto',
                'message' => "Memoria utilizada: {$memory['percentage']}%",
                'timestamp' => now()->toISOString()
            ];
        }

        // Verificar disco
        $disk = $this->getDiskUsage();
        if ($disk['percentage'] > 90) {
            $alerts[] = [
                'level' => 'critical',
                'title' => 'Espacio en disco bajo',
                'message' => "Disco utilizado: {$disk['percentage']}%",
                'timestamp' => now()->toISOString()
            ];
        }

        // Verificar base de datos
        $db = $this->getDatabaseStatus();
        if ($db['status'] !== 'connected') {
            $alerts[] = [
                'level' => 'critical',
                'title' => 'Error de conexión a base de datos',
                'message' => $db['error'] ?? 'Conexión fallida',
                'timestamp' => now()->toISOString()
            ];
        }

        // Verificar jobs
        $jobHealth = $this->jobService->checkJobHealth();
        if (!$jobHealth['healthy']) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Problemas con jobs',
                'message' => implode(', ', $jobHealth['issues']),
                'timestamp' => now()->toISOString()
            ];
        }

        return $alerts;
    }

    /**
     * Obtener salud del sistema
     */
    protected function getSystemHealth(): array
    {
        $health = [
            'overall' => 'healthy',
            'issues' => [],
            'timestamp' => now()->toISOString()
        ];

        // Verificar memoria
        $memory = $this->getMemoryUsage();
        if ($memory['percentage'] > 85) {
            $health['overall'] = 'critical';
            $health['issues'][] = "Uso de memoria alto: {$memory['percentage']}%";
        } elseif ($memory['percentage'] > 70) {
            $health['overall'] = 'warning';
            $health['issues'][] = "Uso de memoria moderado: {$memory['percentage']}%";
        }

        // Verificar disco
        $disk = $this->getDiskUsage();
        if ($disk['percentage'] > 90) {
            $health['overall'] = 'critical';
            $health['issues'][] = "Espacio en disco bajo: {$disk['percentage']}%";
        } elseif ($disk['percentage'] > 80) {
            $health['overall'] = 'warning';
            $health['issues'][] = "Espacio en disco moderado: {$disk['percentage']}%";
        }

        // Verificar base de datos
        $db = $this->getDatabaseStatus();
        if ($db['status'] !== 'connected') {
            $health['overall'] = 'critical';
            $health['issues'][] = 'Error de conexión a base de datos';
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
        $percentage = ($currentUsage / $memoryLimitBytes) * 100;

        return [
            'current' => $this->formatBytes($currentUsage),
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
     * Obtener estado de la base de datos
     */
    protected function getDatabaseStatus(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'connected'];
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
            $testKey = 'monitor_test_' . time();
            Cache::put($testKey, 'test', 60);
            $result = Cache::get($testKey) === 'test';
            Cache::forget($testKey);

            return ['status' => $result ? 'working' : 'error'];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
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
            $stats = $this->jobService->getJobStatistics();
            $pending = $this->jobService->getPendingJobs();

            return [
                'total' => $stats['total_jobs'] ?? 0,
                'pending' => array_sum($pending),
                'successful' => $stats['successful_jobs'] ?? 0,
                'failed' => $stats['failed_jobs'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'pending' => 0,
                'successful' => 0,
                'failed' => 0
            ];
        }
    }

    /**
     * Calcular duración real
     */
    protected function calculateActualDuration(array $monitoringData): int
    {
        if (!isset($monitoringData['started_at'])) {
            return 0;
        }

        $started = \Carbon\Carbon::parse($monitoringData['started_at']);
        $stopped = now();

        return $started->diffInSeconds($stopped);
    }

    /**
     * Calcular tiempo restante
     */
    protected function calculateRemainingTime(array $monitoringData): int
    {
        if (!isset($monitoringData['end_time'])) {
            return 0;
        }

        $endTime = \Carbon\Carbon::parse($monitoringData['end_time']);
        $now = now();

        if ($endTime->isPast()) {
            return 0;
        }

        return $now->diffInSeconds($endTime);
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
}



