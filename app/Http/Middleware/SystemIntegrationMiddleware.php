<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\DynamicDriverService;
use App\Services\BackupService;
use App\Services\EmailService;
use App\Services\NotificationService;
use App\Services\ActivityLogService;

class SystemIntegrationMiddleware
{
    protected $dynamicDriverService;
    protected $backupService;
    protected $emailService;
    protected $notificationService;
    protected $activityLogService;

    public function __construct(
        DynamicDriverService $dynamicDriverService,
        BackupService $backupService,
        EmailService $emailService,
        NotificationService $notificationService,
        ActivityLogService $activityLogService
    ) {
        $this->dynamicDriverService = $dynamicDriverService;
        $this->backupService = $backupService;
        $this->emailService = $emailService;
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

        try {
            // 1. Inicializar sistemas integrados
            $this->initializeIntegratedSystems($request);

            // 2. Aplicar configuraciones dinámicas
            $this->applyDynamicConfigurations($request);

            // 3. Verificar estado del sistema
            $this->checkSystemHealth($request);

            // 4. Procesar la request
            $response = $next($request);

            // 5. Post-procesamiento
            $this->postProcessRequest($request, $response, $startTime, $startMemory);

            return $response;

        } catch (\Exception $e) {
            // Manejo de errores integrado
            $this->handleIntegratedError($request, $e, $startTime, $startMemory);

            // Re-lanzar la excepción para que sea manejada por el sistema
            throw $e;
        }
    }

    /**
     * Inicializa todos los sistemas integrados
     */
    protected function initializeIntegratedSystems(Request $request): void
    {
        try {
            // Inicializar drivers dinámicos
            $this->initializeDynamicDrivers();

            // Inicializar servicios de respaldo
            $this->initializeBackupServices();

            // Inicializar servicios de notificación
            $this->initializeNotificationServices();

            // Inicializar logging de actividad
            $this->initializeActivityLogging($request);

        } catch (\Exception $e) {
            Log::error('Error al inicializar sistemas integrados', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Aplica configuraciones dinámicas
     */
    protected function applyDynamicConfigurations(Request $request): void
    {
        try {
            // Aplicar configuraciones de drivers
            $this->applyDriverConfigurations($request);

            // Aplicar configuraciones de cache
            $this->applyCacheConfigurations($request);

            // Aplicar configuraciones de sesión
            $this->applySessionConfigurations($request);

        } catch (\Exception $e) {
            Log::warning('Error al aplicar configuraciones dinámicas', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Verifica el estado de salud del sistema
     */
    protected function checkSystemHealth(Request $request): void
    {
        try {
            $healthStatus = $this->getSystemHealthStatus();

            // Si el sistema no está saludable, registrar el problema
            if (!$healthStatus['healthy']) {
                $this->handleSystemHealthIssue($request, $healthStatus);
            }

        } catch (\Exception $e) {
            Log::error('Error al verificar estado del sistema', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Post-procesamiento de la request
     */
    protected function postProcessRequest(Request $request, $response, float $startTime, int $startMemory): void
    {
        try {
            // Calcular métricas de rendimiento
            $performanceMetrics = $this->calculatePerformanceMetrics($startTime, $startMemory);

            // Registrar actividad
            $this->logRequestActivity($request, $response, $performanceMetrics);

            // Actualizar estadísticas del sistema
            $this->updateSystemStatistics($request, $performanceMetrics);

            // Limpiar recursos si es necesario
            $this->cleanupResources($request);

        } catch (\Exception $e) {
            Log::error('Error en post-procesamiento', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Maneja errores de manera integrada
     */
    protected function handleIntegratedError(Request $request, \Exception $e, float $startTime, int $startMemory): void
    {
        try {
            // Registrar el error
            $this->logError($request, $e, $startTime, $startMemory);

            // Enviar notificación de error si es crítico
            if ($this->isCriticalError($e)) {
                $this->sendCriticalErrorNotification($request, $e);
            }

            // Crear respaldo de emergencia si es necesario
            if ($this->shouldCreateEmergencyBackup($e)) {
                $this->createEmergencyBackup($request, $e);
            }

        } catch (\Exception $logError) {
            // Si incluso el logging falla, registrar en archivo de log
            error_log("Error crítico en middleware de integración: " . $logError->getMessage());
        }
    }

    // Métodos de inicialización
    protected function initializeDynamicDrivers(): void
    {
        // Restaurar configuraciones de drivers desde base de datos
        $services = ['cache', 'session', 'queue', 'mail', 'database'];

        foreach ($services as $service) {
            try {
                $this->dynamicDriverService->restoreDriverConfig($service);
            } catch (\Exception $e) {
                Log::warning("No se pudo restaurar driver para {$service}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function initializeBackupServices(): void
    {
        // Verificar si hay respaldos pendientes
        try {
            // Simular verificación de respaldos
            Log::info('Servicios de respaldo inicializados');
        } catch (\Exception $e) {
            Log::warning('Error al inicializar servicios de respaldo', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function initializeNotificationServices(): void
    {
        // Verificar notificaciones pendientes
        try {
            // Simular verificación de notificaciones
            Log::info('Servicios de notificación inicializados');
        } catch (\Exception $e) {
            Log::warning('Error al inicializar servicios de notificación', [
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function initializeActivityLogging(Request $request): void
    {
        // Inicializar logging de actividad
        Log::info('Sistema integrado inicializado', [
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }

    // Métodos de configuración
    protected function applyDriverConfigurations(Request $request): void
    {
        $route = $request->route();

        if (!$route) {
            return;
        }

        $routeName = $route->getName();

        // Aplicar configuraciones específicas por ruta
        $this->applyRouteSpecificDrivers($routeName);
    }

    protected function applyRouteSpecificDrivers(string $routeName): void
    {
        $driverConfigs = $this->getRouteDriverConfigurations($routeName);

        foreach ($driverConfigs as $service => $config) {
            try {
                $this->dynamicDriverService->changeDriver(
                    $service,
                    $config['driver'],
                    $config['config']
                );
            } catch (\Exception $e) {
                Log::warning("No se pudo aplicar driver específico para {$service}", [
                    'route' => $routeName,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    protected function applyCacheConfigurations(Request $request): void
    {
        // Aplicar configuraciones de cache específicas
        $cacheConfig = $this->getCacheConfiguration($request);

        if ($cacheConfig) {
            Cache::store($cacheConfig['store'])->put(
                $cacheConfig['key'],
                $cacheConfig['value'],
                $cacheConfig['ttl']
            );
        }
    }

    protected function applySessionConfigurations(Request $request): void
    {
        // Aplicar configuraciones de sesión específicas
        $sessionConfig = $this->getSessionConfiguration($request);

        if ($sessionConfig) {
            session($sessionConfig);
        }
    }

    // Métodos de salud del sistema
    protected function getSystemHealthStatus(): array
    {
        $health = [
            'healthy' => true,
            'issues' => [],
            'timestamp' => now()->toISOString()
        ];

        // Verificar drivers
        $driverStatus = $this->dynamicDriverService->getAllDriversStatus();
        foreach ($driverStatus as $service => $status) {
            if (empty($status['current'])) {
                $health['healthy'] = false;
                $health['issues'][] = "Driver no configurado para {$service}";
            }
        }

        // Verificar espacio en disco
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage['percentage'] > 90) {
            $health['healthy'] = false;
            $health['issues'][] = "Espacio en disco bajo: {$diskUsage['percentage']}%";
        }

        // Verificar memoria
        $memoryUsage = $this->getMemoryUsage();
        if ($memoryUsage['percentage'] > 85) {
            $health['healthy'] = false;
            $health['issues'][] = "Uso de memoria alto: {$memoryUsage['percentage']}%";
        }

        return $health;
    }

    protected function handleSystemHealthIssue(Request $request, array $healthStatus): void
    {
        // Registrar problema de salud
        Log::warning('Problema de salud del sistema detectado', [
            'issues' => $healthStatus['issues'],
            'url' => $request->url()
        ]);
    }

    // Métodos de métricas
    protected function calculatePerformanceMetrics(float $startTime, int $startMemory): array
    {
        return [
            'execution_time' => microtime(true) - $startTime,
            'memory_usage' => memory_get_usage(true) - $startMemory,
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => now()->toISOString()
        ];
    }

    protected function logRequestActivity(Request $request, $response, array $metrics): void
    {
        Log::info('Request procesada', [
            'url' => $request->url(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'execution_time' => $metrics['execution_time'],
            'memory_usage' => $metrics['memory_usage'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }

    protected function updateSystemStatistics(Request $request, array $metrics): void
    {
        // Actualizar estadísticas en cache
        $statsKey = 'system_stats_' . date('Y-m-d');
        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'total_execution_time' => 0,
            'total_memory_usage' => 0,
            'average_response_time' => 0
        ]);

        $stats['total_requests']++;
        $stats['total_execution_time'] += $metrics['execution_time'];
        $stats['total_memory_usage'] += $metrics['memory_usage'];
        $stats['average_response_time'] = $stats['total_execution_time'] / $stats['total_requests'];

        Cache::put($statsKey, $stats, 86400); // 24 horas
    }

    protected function cleanupResources(Request $request): void
    {
        // Limpiar cache si es necesario
        if ($this->shouldCleanupCache($request)) {
            Cache::flush();
        }

        // Limpiar sesiones expiradas
        if ($this->shouldCleanupSessions($request)) {
            $this->cleanupExpiredSessions();
        }
    }

    // Métodos de error handling
    protected function logError(Request $request, \Exception $e, float $startTime, int $startMemory): void
    {
        Log::error('Error en middleware de integración', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'url' => $request->url(),
            'method' => $request->method(),
            'execution_time' => microtime(true) - $startTime,
            'memory_usage' => memory_get_usage(true) - $startMemory,
            'ip' => $request->ip()
        ]);
    }

    protected function isCriticalError(\Exception $e): bool
    {
        $criticalErrors = [
            'Database connection failed',
            'Cache system unavailable',
            'Session storage failed',
            'File system error'
        ];

        foreach ($criticalErrors as $criticalError) {
            if (str_contains($e->getMessage(), $criticalError)) {
                return true;
            }
        }

        return false;
    }

    protected function sendCriticalErrorNotification(Request $request, \Exception $e): void
    {
        Log::critical('Error crítico del sistema', [
            'error' => $e->getMessage(),
            'url' => $request->url()
        ]);
    }

    protected function shouldCreateEmergencyBackup(\Exception $e): bool
    {
        return str_contains($e->getMessage(), 'Database') ||
               str_contains($e->getMessage(), 'Storage');
    }

    protected function createEmergencyBackup(Request $request, \Exception $e): void
    {
        Log::warning('Respaldo de emergencia requerido', [
            'error' => $e->getMessage(),
            'url' => $request->url()
        ]);
    }

    // Métodos auxiliares
    protected function getRouteDriverConfigurations(string $routeName): array
    {
        $configurations = [];

        // Configuraciones específicas por ruta
        switch ($routeName) {
            case 'admin.drivers.*':
                $configurations['session'] = [
                    'driver' => 'database',
                    'config' => ['table' => 'sessions']
                ];
                break;

            case 'api.*':
                $configurations['cache'] = [
                    'driver' => 'redis',
                    'config' => ['connection' => 'default']
                ];
                break;
        }

        return $configurations;
    }

    protected function getCacheConfiguration(Request $request): ?array
    {
        // Configuración de cache específica por request
        if ($request->is('api/*')) {
            return [
                'store' => 'redis',
                'key' => 'api_request_' . $request->fingerprint(),
                'value' => $request->all(),
                'ttl' => 300
            ];
        }

        return null;
    }

    protected function getSessionConfiguration(Request $request): ?array
    {
        // Configuración de sesión específica
        if ($request->is('admin/*')) {
            return [
                'admin_session' => true,
                'session_timeout' => 3600
            ];
        }

        return null;
    }

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

    protected function getMemoryUsage(): array
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $currentUsage = memory_get_usage(true);
        $percentage = ($currentUsage / $memoryLimitBytes) * 100;

        return [
            'limit' => $memoryLimitBytes,
            'current' => $currentUsage,
            'percentage' => round($percentage, 2)
        ];
    }

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

    protected function shouldCleanupCache(Request $request): bool
    {
        // Limpiar cache cada 100 requests
        $cacheCleanupKey = 'cache_cleanup_counter';
        $counter = Cache::get($cacheCleanupKey, 0);
        $counter++;

        if ($counter >= 100) {
            Cache::forget($cacheCleanupKey);
            return true;
        }

        Cache::put($cacheCleanupKey, $counter, 3600);
        return false;
    }

    protected function shouldCleanupSessions(Request $request): bool
    {
        // Limpiar sesiones cada 50 requests
        $sessionCleanupKey = 'session_cleanup_counter';
        $counter = Cache::get($sessionCleanupKey, 0);
        $counter++;

        if ($counter >= 50) {
            Cache::forget($sessionCleanupKey);
            return true;
        }

        Cache::put($sessionCleanupKey, $counter, 3600);
        return false;
    }

    protected function cleanupExpiredSessions(): void
    {
        // Limpiar sesiones expiradas
        \DB::table('sessions')
            ->where('last_activity', '<', now()->subMinutes(config('session.lifetime', 120))->timestamp)
            ->delete();
    }
}
