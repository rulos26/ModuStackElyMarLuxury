<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\JobService;
use App\Services\DynamicDriverService;
use App\Services\BackupService;

class SystemMaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:maintenance
                            {action : Acción a realizar (start|stop|status|schedule)}
                            {--reason= : Razón del mantenimiento}
                            {--duration=60 : Duración en minutos}
                            {--notify : Enviar notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar modo de mantenimiento del sistema';

    protected $jobService;
    protected $dynamicDriverService;
    protected $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        JobService $jobService,
        DynamicDriverService $dynamicDriverService,
        BackupService $backupService
    ) {
        parent::__construct();
        $this->jobService = $jobService;
        $this->dynamicDriverService = $dynamicDriverService;
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $reason = $this->option('reason');
        $duration = (int) $this->option('duration');
        $notify = $this->option('notify');

        switch ($action) {
            case 'start':
                return $this->startMaintenance($reason, $duration, $notify);
            case 'stop':
                return $this->stopMaintenance();
            case 'status':
                return $this->showMaintenanceStatus();
            case 'schedule':
                return $this->scheduleMaintenance($reason, $duration, $notify);
            default:
                $this->error('Acción no válida. Use: start, stop, status, schedule');
                return 1;
        }
    }

    /**
     * Iniciar modo de mantenimiento
     */
    protected function startMaintenance(string $reason, int $duration, bool $notify): int
    {
        $this->info('🔧 Iniciando modo de mantenimiento...');

        try {
            // Activar modo de mantenimiento de Laravel
            Artisan::call('down', [
                '--message' => $reason ?: 'Mantenimiento programado del sistema',
                '--retry' => $duration * 60
            ]);

            // Registrar en cache
            $maintenanceData = [
                'started_at' => now()->toISOString(),
                'reason' => $reason ?: 'Mantenimiento programado',
                'duration' => $duration,
                'scheduled_end' => now()->addMinutes($duration)->toISOString(),
                'status' => 'active'
            ];

            Cache::put('system_maintenance', $maintenanceData, $duration * 60);

            // Pausar jobs
            $this->pauseJobs();

            // Crear respaldo antes del mantenimiento
            $this->createPreMaintenanceBackup();

            // Enviar notificaciones si se solicita
            if ($notify) {
                $this->sendMaintenanceNotifications($maintenanceData);
            }

            // Log del mantenimiento
            Log::info('Modo de mantenimiento iniciado', $maintenanceData);

            $this->info('✅ Modo de mantenimiento activado exitosamente');
            $this->line("Duración: {$duration} minutos");
            $this->line("Finaliza: {$maintenanceData['scheduled_end']}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al iniciar mantenimiento: {$e->getMessage()}");
            Log::error('Error al iniciar mantenimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Detener modo de mantenimiento
     */
    protected function stopMaintenance(): int
    {
        $this->info('🔧 Deteniendo modo de mantenimiento...');

        try {
            // Desactivar modo de mantenimiento de Laravel
            Artisan::call('up');

            // Actualizar cache
            $maintenanceData = Cache::get('system_maintenance', []);
            $maintenanceData['status'] = 'completed';
            $maintenanceData['ended_at'] = now()->toISOString();
            $maintenanceData['actual_duration'] = $this->calculateActualDuration($maintenanceData);

            Cache::put('system_maintenance', $maintenanceData, 3600);

            // Reanudar jobs
            $this->resumeJobs();

            // Ejecutar tareas post-mantenimiento
            $this->runPostMaintenanceTasks();

            // Log del mantenimiento
            Log::info('Modo de mantenimiento detenido', $maintenanceData);

            $this->info('✅ Modo de mantenimiento desactivado exitosamente');
            $this->line("Duración real: {$maintenanceData['actual_duration']} minutos");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al detener mantenimiento: {$e->getMessage()}");
            Log::error('Error al detener mantenimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Mostrar estado del mantenimiento
     */
    protected function showMaintenanceStatus(): int
    {
        $maintenanceData = Cache::get('system_maintenance', []);

        if (empty($maintenanceData) || $maintenanceData['status'] !== 'active') {
            $this->info('✅ Sistema en funcionamiento normal');
            return 0;
        }

        $this->info('🔧 SISTEMA EN MODO DE MANTENIMIENTO');
        $this->line('====================================');

        $this->line("Iniciado: {$maintenanceData['started_at']}");
        $this->line("Razón: {$maintenanceData['reason']}");
        $this->line("Duración programada: {$maintenanceData['duration']} minutos");
        $this->line("Finaliza: {$maintenanceData['scheduled_end']}");

        $remaining = $this->calculateRemainingTime($maintenanceData);
        $this->line("Tiempo restante: {$remaining} minutos");

        return 0;
    }

    /**
     * Programar mantenimiento
     */
    protected function scheduleMaintenance(string $reason, int $duration, bool $notify): int
    {
        $this->info('📅 Programando mantenimiento...');

        $scheduledData = [
            'scheduled_at' => now()->addHours(2)->toISOString(), // Programar para 2 horas después
            'reason' => $reason ?: 'Mantenimiento programado',
            'duration' => $duration,
            'notify' => $notify,
            'status' => 'scheduled'
        ];

        Cache::put('scheduled_maintenance', $scheduledData, 86400); // 24 horas

        // Despachar job para ejecutar mantenimiento
        $this->jobService->dispatchSystemJob('scheduled_maintenance', $scheduledData, 1);

        $this->info('✅ Mantenimiento programado exitosamente');
        $this->line("Programado para: {$scheduledData['scheduled_at']}");
        $this->line("Duración: {$duration} minutos");

        return 0;
    }

    /**
     * Pausar jobs
     */
    protected function pauseJobs(): void
    {
        $this->line('⏸️  Pausando jobs...');

        // Marcar jobs como pausados
        Cache::put('jobs_paused', true, 3600);

        // Despachar job de pausa
        $this->jobService->dispatchSystemJob('pause_jobs', [
            'paused_at' => now()->toISOString(),
            'reason' => 'maintenance_mode'
        ], 1);

        $this->line('✅ Jobs pausados');
    }

    /**
     * Reanudar jobs
     */
    protected function resumeJobs(): void
    {
        $this->line('▶️  Reanudando jobs...');

        // Marcar jobs como activos
        Cache::forget('jobs_paused');

        // Despachar job de reanudación
        $this->jobService->dispatchSystemJob('resume_jobs', [
            'resumed_at' => now()->toISOString(),
            'reason' => 'maintenance_completed'
        ], 1);

        $this->line('✅ Jobs reanudados');
    }

    /**
     * Crear respaldo antes del mantenimiento
     */
    protected function createPreMaintenanceBackup(): void
    {
        $this->line('💾 Creando respaldo pre-mantenimiento...');

        try {
            $this->jobService->dispatchBackupJob('full_system', [
                'name' => 'Pre-Maintenance Backup - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'Respaldo automático antes del mantenimiento',
                'maintenance' => true
            ], 1, 30);

            $this->line('✅ Respaldo programado');

        } catch (\Exception $e) {
            $this->warn("⚠️  Error al programar respaldo: {$e->getMessage()}");
        }
    }

    /**
     * Enviar notificaciones de mantenimiento
     */
    protected function sendMaintenanceNotifications(array $maintenanceData): void
    {
        $this->line('📧 Enviando notificaciones...');

        try {
            $this->jobService->dispatchNotificationJob('maintenance_alert', [
                'title' => 'Mantenimiento del Sistema',
                'message' => "El sistema entrará en modo de mantenimiento por {$maintenanceData['duration']} minutos. Razón: {$maintenanceData['reason']}",
                'type' => 'warning',
                'channels' => ['database', 'email']
            ], 1, ['database', 'email']);

            $this->line('✅ Notificaciones enviadas');

        } catch (\Exception $e) {
            $this->warn("⚠️  Error al enviar notificaciones: {$e->getMessage()}");
        }
    }

    /**
     * Ejecutar tareas post-mantenimiento
     */
    protected function runPostMaintenanceTasks(): void
    {
        $this->line('🔧 Ejecutando tareas post-mantenimiento...');

        try {
            // Limpiar cache
            Cache::flush();
            $this->line('✅ Cache limpiado');

            // Optimizar base de datos
            $this->optimizeDatabase();
            $this->line('✅ Base de datos optimizada');

            // Verificar drivers
            $this->verifyDrivers();
            $this->line('✅ Drivers verificados');

            // Despachar job de limpieza
            $this->jobService->dispatchCleanupJob('full_cleanup', [
                'post_maintenance' => true,
                'timestamp' => now()->toISOString()
            ], 30);

            $this->line('✅ Tareas post-mantenimiento completadas');

        } catch (\Exception $e) {
            $this->warn("⚠️  Error en tareas post-mantenimiento: {$e->getMessage()}");
        }
    }

    /**
     * Optimizar base de datos
     */
    protected function optimizeDatabase(): void
    {
        try {
            $tables = ['users', 'sessions', 'notifications', 'activity_logs'];

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::statement("OPTIMIZE TABLE {$table}");
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al optimizar base de datos', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verificar drivers
     */
    protected function verifyDrivers(): void
    {
        try {
            $driversStatus = $this->dynamicDriverService->getAllDriversStatus();

            foreach ($driversStatus as $service => $status) {
                if (empty($status['current'])) {
                    Log::warning("Driver no configurado para {$service}");
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al verificar drivers', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calcular duración real
     */
    protected function calculateActualDuration(array $maintenanceData): int
    {
        if (!isset($maintenanceData['started_at'])) {
            return 0;
        }

        $started = \Carbon\Carbon::parse($maintenanceData['started_at']);
        $ended = now();

        return $started->diffInMinutes($ended);
    }

    /**
     * Calcular tiempo restante
     */
    protected function calculateRemainingTime(array $maintenanceData): int
    {
        if (!isset($maintenanceData['scheduled_end'])) {
            return 0;
        }

        $scheduledEnd = \Carbon\Carbon::parse($maintenanceData['scheduled_end']);
        $now = now();

        if ($scheduledEnd->isPast()) {
            return 0;
        }

        return $now->diffInMinutes($scheduledEnd);
    }
}

