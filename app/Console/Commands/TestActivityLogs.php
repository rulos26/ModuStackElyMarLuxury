<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActivityLogService;
use App\Models\User;

class TestActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:test {--count=10 : Número de logs a generar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar sistema de logs avanzado generando logs de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');

        $this->info("📊 Probando Sistema de Logs Avanzado - Generando {$count} logs");
        $this->line('');

        try {
            $activityLogService = app(ActivityLogService::class);

            // Obtener usuario de prueba
            $user = User::first();

            if (!$user) {
                $this->error('❌ No hay usuarios en el sistema. Ejecuta: php artisan user:create-test');
                return 1;
            }

            // Simular autenticación del usuario
            auth()->login($user);

            // Generar diferentes tipos de logs
            $this->generateAuthLogs($activityLogService, $count);
            $this->generateSystemLogs($activityLogService, $count);
            $this->generateApiLogs($activityLogService, $count);
            $this->generateModelLogs($activityLogService, $user, $count);
            $this->generateSecurityLogs($activityLogService, $count);
            $this->generateErrorLogs($activityLogService, $count);
            $this->generatePerformanceLogs($activityLogService, $count);

            // Mostrar estadísticas
            $this->showStats($activityLogService);

            $this->line('');
            $this->info('✅ ¡Sistema de logs probado exitosamente!');

        } catch (\Exception $e) {
            $this->error('❌ Error probando logs: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Generar logs de autenticación
     */
    protected function generateAuthLogs(ActivityLogService $service, int $count)
    {
        $this->info('🔐 Generando logs de autenticación...');

        $authEvents = ['login', 'logout', 'login_failed', 'password_reset', 'email_verified'];

        for ($i = 0; $i < min($count, 5); $i++) {
            $event = $authEvents[array_rand($authEvents)];

            $service->logAuth(
                $event,
                "Usuario {$event} exitoso",
                ['attempt' => $i + 1, 'ip' => fake()->ipv4()]
            );

            $this->line("   ✅ Log de autenticación creado: {$event}");
        }
    }

    /**
     * Generar logs del sistema
     */
    protected function generateSystemLogs(ActivityLogService $service, int $count)
    {
        $this->info('⚙️ Generando logs del sistema...');

        $systemEvents = ['backup', 'maintenance', 'update', 'config_change', 'cache_clear'];

        for ($i = 0; $i < min($count, 5); $i++) {
            $event = $systemEvents[array_rand($systemEvents)];
            $level = fake()->randomElement(['info', 'warning', 'error']);

            $service->logSystem(
                $event,
                "Evento del sistema: {$event}",
                $level,
                ['component' => 'system', 'version' => '1.0.0']
            );

            $this->line("   ✅ Log del sistema creado: {$event} ({$level})");
        }
    }

    /**
     * Generar logs de API
     */
    protected function generateApiLogs(ActivityLogService $service, int $count)
    {
        $this->info('🌐 Generando logs de API...');

        $apiEvents = ['view', 'create', 'update', 'delete', 'search'];

        for ($i = 0; $i < min($count, 5); $i++) {
            $event = $apiEvents[array_rand($apiEvents)];

            $service->logApi(
                $event,
                "API {$event} request",
                'info',
                ['endpoint' => '/api/test', 'method' => 'GET']
            );

            $this->line("   ✅ Log de API creado: {$event}");
        }
    }

    /**
     * Generar logs de modelo
     */
    protected function generateModelLogs(ActivityLogService $service, User $user, int $count)
    {
        $this->info('📝 Generando logs de modelo...');

        $modelEvents = ['created', 'updated', 'deleted', 'restored'];

        for ($i = 0; $i < min($count, 5); $i++) {
            $event = $modelEvents[array_rand($modelEvents)];

            $service->logModel(
                $event,
                $user,
                "Usuario {$event}",
                ['field' => 'name', 'old_value' => 'Old Name', 'new_value' => 'New Name']
            );

            $this->line("   ✅ Log de modelo creado: {$event}");
        }
    }

    /**
     * Generar logs de seguridad
     */
    protected function generateSecurityLogs(ActivityLogService $service, int $count)
    {
        $this->info('🛡️ Generando logs de seguridad...');

        $securityEvents = ['suspicious_activity', 'failed_login', 'permission_denied', 'ip_blocked'];

        for ($i = 0; $i < min($count, 5); $i++) {
            $event = $securityEvents[array_rand($securityEvents)];
            $level = fake()->randomElement(['warning', 'error']);

            $service->logSecurity(
                $event,
                "Evento de seguridad: {$event}",
                $level,
                ['ip' => fake()->ipv4(), 'user_agent' => fake()->userAgent()]
            );

            $this->line("   ✅ Log de seguridad creado: {$event} ({$level})");
        }
    }

    /**
     * Generar logs de error
     */
    protected function generateErrorLogs(ActivityLogService $service, int $count)
    {
        $this->info('❌ Generando logs de error...');

        for ($i = 0; $i < min($count, 3); $i++) {
            try {
                // Simular un error
                throw new \Exception("Error de prueba #{$i}: " . fake()->sentence());
            } catch (\Exception $e) {
                $service->logError(
                    $e,
                    "Error simulado para testing",
                    ['test' => true, 'iteration' => $i + 1]
                );

                $this->line("   ✅ Log de error creado: " . $e->getMessage());
            }
        }
    }

    /**
     * Generar logs de rendimiento
     */
    protected function generatePerformanceLogs(ActivityLogService $service, int $count)
    {
        $this->info('⚡ Generando logs de rendimiento...');

        $performanceEvents = ['slow_query', 'high_memory', 'cache_miss', 'database_timeout'];

        for ($i = 0; $i < min($count, 5); $i++) {
            $event = $performanceEvents[array_rand($performanceEvents)];
            $executionTime = fake()->numberBetween(100, 10000); // 100ms a 10s
            $memoryUsage = fake()->numberBetween(1024, 134217728); // 1KB a 128MB

            $service->logPerformance(
                $event,
                "Problema de rendimiento: {$event}",
                $executionTime,
                $memoryUsage,
                ['query' => 'SELECT * FROM users', 'table' => 'users']
            );

            $this->line("   ✅ Log de rendimiento creado: {$event} ({$executionTime}ms, " .
                       round($memoryUsage / 1024 / 1024, 2) . "MB)");
        }
    }

    /**
     * Mostrar estadísticas
     */
    protected function showStats(ActivityLogService $service)
    {
        $this->line('');
        $this->info('📊 Estadísticas del Sistema de Logs:');

        $stats = $service->getStats();

        $this->line("   Total de logs: {$stats['total']}");
        $this->line("   Promedio tiempo ejecución: " . ($stats['avg_execution_time'] ?? 'N/A') . "ms");
        $this->line("   Promedio uso memoria: " . ($stats['avg_memory_usage'] ? round($stats['avg_memory_usage'] / 1024 / 1024, 2) . "MB" : 'N/A'));

        if (!empty($stats['by_level'])) {
            $this->line('');
            $this->line('   Por nivel:');
            foreach ($stats['by_level'] as $level => $count) {
                $this->line("     {$level}: {$count}");
            }
        }

        if (!empty($stats['by_event'])) {
            $this->line('');
            $this->line('   Por evento:');
            foreach ($stats['by_event'] as $event => $count) {
                $this->line("     {$event}: {$count}");
            }
        }

        if (!empty($stats['top_ips'])) {
            $this->line('');
            $this->line('   Top IPs:');
            $topIps = array_slice($stats['top_ips'], 0, 5, true);
            foreach ($topIps as $ip => $count) {
                $this->line("     {$ip}: {$count} requests");
            }
        }

        // Obtener logs recientes
        $recentLogs = $service->getRecent(5);
        $this->line('');
        $this->line('   Logs recientes:');
        foreach ($recentLogs as $log) {
            $this->line("     [{$log->level}] {$log->description} - {$log->created_at->format('H:i:s')}");
        }
    }
}
