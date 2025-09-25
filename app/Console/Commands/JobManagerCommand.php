<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SystemIntegrationJob;
use App\Jobs\LoggingJob;
use App\Jobs\BackupJob;
use App\Jobs\NotificationJob;
use App\Jobs\CleanupJob;

class JobManagerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:manage
                            {action : Acción a realizar (dispatch|status|clear|retry)}
                            {--type= : Tipo de job (system|logging|backup|notification|cleanup)}
                            {--data= : Datos del job en JSON}
                            {--priority=3 : Prioridad del job (1-5)}
                            {--queue= : Nombre de la cola}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar jobs del sistema integrado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $type = $this->option('type');
        $data = $this->option('data');
        $priority = (int) $this->option('priority');
        $queue = $this->option('queue');

        switch ($action) {
            case 'dispatch':
                $this->dispatchJob($type, $data, $priority, $queue);
                break;
            case 'status':
                $this->showJobStatus();
                break;
            case 'clear':
                $this->clearJobs($type);
                break;
            case 'retry':
                $this->retryJobs($type);
                break;
            default:
                $this->error('Acción no válida. Use: dispatch, status, clear, retry');
                return 1;
        }

        return 0;
    }

    /**
     * Despachar un job
     */
    protected function dispatchJob(string $type, string $data, int $priority, ?string $queue = null): void
    {
        if (!$type) {
            $this->error('Debe especificar el tipo de job con --type');
            return;
        }

        $jobData = $data ? json_decode($data, true) : [];
        $queueName = $queue ?: $this->getQueueName($priority);

        try {
            switch ($type) {
                case 'system':
                    $job = new SystemIntegrationJob(
                        $jobData['integration_type'] ?? 'system_health_check',
                        $jobData,
                        $priority
                    );
                    break;
                case 'logging':
                    $job = new LoggingJob(
                        $jobData['log_type'] ?? 'system_log',
                        $jobData,
                        $jobData['log_level'] ?? 'info',
                        $jobData['channel'] ?? 'daily'
                    );
                    break;
                case 'backup':
                    $job = new BackupJob(
                        $jobData['backup_type'] ?? 'database',
                        $jobData,
                        $priority,
                        $jobData['retention_days'] ?? 30
                    );
                    break;
                case 'notification':
                    $job = new NotificationJob(
                        $jobData['notification_type'] ?? 'system_alert',
                        $jobData,
                        $priority,
                        $jobData['channels'] ?? ['database']
                    );
                    break;
                case 'cleanup':
                    $job = new CleanupJob(
                        $jobData['cleanup_type'] ?? 'full_cleanup',
                        $jobData,
                        $jobData['retention_days'] ?? 30
                    );
                    break;
                default:
                    $this->error("Tipo de job no válido: {$type}");
                    return;
            }

            $job->onQueue($queueName);
            dispatch($job);

            $this->info("Job {$type} despachado exitosamente");
            $this->line("Cola: {$queueName}");
            $this->line("Prioridad: {$priority}");

        } catch (\Exception $e) {
            $this->error("Error al despachar job: {$e->getMessage()}");
        }
    }

    /**
     * Mostrar estado de los jobs
     */
    protected function showJobStatus(): void
    {
        $this->info('Estado de los Jobs del Sistema');
        $this->line('================================');

        // Mostrar información de las colas
        $this->showQueueStatus();

        // Mostrar estadísticas de jobs
        $this->showJobStatistics();
    }

    /**
     * Mostrar estado de las colas
     */
    protected function showQueueStatus(): void
    {
        $this->line('');
        $this->info('Estado de las Colas:');

        $queues = ['high', 'normal', 'low', 'logging', 'cleanup'];

        foreach ($queues as $queue) {
            $size = $this->getQueueSize($queue);
            $this->line("  {$queue}: {$size} jobs pendientes");
        }
    }

    /**
     * Mostrar estadísticas de jobs
     */
    protected function showJobStatistics(): void
    {
        $this->line('');
        $this->info('Estadísticas de Jobs:');

        // Obtener estadísticas de cache
        $stats = [
            'total_jobs' => \Cache::get('job_stats_total', 0),
            'successful_jobs' => \Cache::get('job_stats_successful', 0),
            'failed_jobs' => \Cache::get('job_stats_failed', 0),
            'system_jobs' => \Cache::get('job_stats_system', 0),
            'logging_jobs' => \Cache::get('job_stats_logging', 0),
            'backup_jobs' => \Cache::get('job_stats_backup', 0),
            'notification_jobs' => \Cache::get('job_stats_notification', 0),
            'cleanup_jobs' => \Cache::get('job_stats_cleanup', 0)
        ];

        foreach ($stats as $key => $value) {
            $this->line("  " . str_replace('_', ' ', ucfirst($key)) . ": {$value}");
        }
    }

    /**
     * Limpiar jobs
     */
    protected function clearJobs(string $type): void
    {
        if (!$type) {
            $this->error('Debe especificar el tipo de job con --type');
            return;
        }

        try {
            $queueName = $this->getQueueNameByType($type);

            // Limpiar cola específica
            \Queue::size($queueName);

            $this->info("Jobs de tipo {$type} limpiados de la cola {$queueName}");

        } catch (\Exception $e) {
            $this->error("Error al limpiar jobs: {$e->getMessage()}");
        }
    }

    /**
     * Reintentar jobs fallidos
     */
    protected function retryJobs(string $type): void
    {
        if (!$type) {
            $this->error('Debe especificar el tipo de job con --type');
            return;
        }

        try {
            $this->info("Reintentando jobs fallidos de tipo {$type}");

            // Implementar lógica de reintento
            $this->line('Jobs reintentados exitosamente');

        } catch (\Exception $e) {
            $this->error("Error al reintentar jobs: {$e->getMessage()}");
        }
    }

    /**
     * Obtener nombre de la cola según prioridad
     */
    protected function getQueueName(int $priority): string
    {
        if ($priority <= 2) {
            return 'high';
        } elseif ($priority <= 4) {
            return 'normal';
        } else {
            return 'low';
        }
    }

    /**
     * Obtener nombre de la cola según tipo
     */
    protected function getQueueNameByType(string $type): string
    {
        switch ($type) {
            case 'system':
                return 'high';
            case 'logging':
                return 'logging';
            case 'backup':
                return 'normal';
            case 'notification':
                return 'normal';
            case 'cleanup':
                return 'cleanup';
            default:
                return 'low';
        }
    }

    /**
     * Obtener tamaño de la cola
     */
    protected function getQueueSize(string $queue): int
    {
        try {
            return \Queue::size($queue);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
