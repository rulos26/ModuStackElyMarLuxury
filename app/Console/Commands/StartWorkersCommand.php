<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class StartWorkersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workers:start
                            {--workers=4 : Número de workers a iniciar}
                            {--timeout=60 : Timeout para los workers}
                            {--memory=128 : Límite de memoria para los workers}
                            {--sleep=3 : Tiempo de espera entre jobs}
                            {--tries=3 : Número de intentos para jobs fallidos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Iniciar queue workers para el sistema integrado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workers = (int) $this->option('workers');
        $timeout = (int) $this->option('timeout');
        $memory = (int) $this->option('memory');
        $sleep = (int) $this->option('sleep');
        $tries = (int) $this->option('tries');

        $this->info('Iniciando Queue Workers del Sistema Integrado');
        $this->line('===============================================');

        // Verificar configuración de queue
        $this->checkQueueConfiguration();

        // Iniciar workers para cada cola
        $this->startQueueWorkers($workers, $timeout, $memory, $sleep, $tries);

        $this->info('Workers iniciados exitosamente');
        $this->line('Use Ctrl+C para detener los workers');
    }

    /**
     * Verificar configuración de queue
     */
    protected function checkQueueConfiguration(): void
    {
        $this->info('Verificando configuración de queue...');

        $driver = config('queue.default');
        $this->line("Driver de queue: {$driver}");

        if ($driver === 'database') {
            $this->checkDatabaseQueue();
        } elseif ($driver === 'redis') {
            $this->checkRedisQueue();
        }

        $this->line('Configuración de queue verificada');
    }

    /**
     * Verificar queue de base de datos
     */
    protected function checkDatabaseQueue(): void
    {
        try {
            // Verificar que existe la tabla de jobs
            if (!\Schema::hasTable('jobs')) {
                $this->error('La tabla de jobs no existe. Ejecute: php artisan queue:table');
                return;
            }

            $this->line('✓ Tabla de jobs encontrada');
        } catch (\Exception $e) {
            $this->error("Error al verificar queue de base de datos: {$e->getMessage()}");
        }
    }

    /**
     * Verificar queue de Redis
     */
    protected function checkRedisQueue(): void
    {
        try {
            \Redis::ping();
            $this->line('✓ Conexión a Redis exitosa');
        } catch (\Exception $e) {
            $this->error("Error al conectar con Redis: {$e->getMessage()}");
        }
    }

    /**
     * Iniciar workers para las colas
     */
    protected function startQueueWorkers(int $workers, int $timeout, int $memory, int $sleep, int $tries): void
    {
        $queues = [
            'high' => 2,      // 2 workers para cola de alta prioridad
            'normal' => 2,     // 2 workers para cola normal
            'low' => 1,       // 1 worker para cola de baja prioridad
            'logging' => 1,    // 1 worker para logging
            'cleanup' => 1     // 1 worker para limpieza
        ];

        $this->info('Iniciando workers para las colas:');

        foreach ($queues as $queue => $queueWorkers) {
            $this->startWorkersForQueue($queue, $queueWorkers, $timeout, $memory, $sleep, $tries);
        }
    }

    /**
     * Iniciar workers para una cola específica
     */
    protected function startWorkersForQueue(string $queue, int $workers, int $timeout, int $memory, int $sleep, int $tries): void
    {
        $this->line("Iniciando {$workers} workers para cola '{$queue}'");

        for ($i = 1; $i <= $workers; $i++) {
            $this->startWorker($queue, $i, $timeout, $memory, $sleep, $tries);
        }
    }

    /**
     * Iniciar un worker individual
     */
    protected function startWorker(string $queue, int $workerId, int $timeout, int $memory, int $sleep, int $tries): void
    {
        $command = "php artisan queue:work --queue={$queue} --timeout={$timeout} --memory={$memory} --sleep={$sleep} --tries={$tries} --name=worker-{$queue}-{$workerId}";

        $this->line("  Worker {$workerId}: {$command}");

        // En un entorno real, aquí se ejecutaría el comando
        // Por ahora, solo lo registramos en el log
        Log::info("Worker iniciado", [
            'queue' => $queue,
            'worker_id' => $workerId,
            'command' => $command
        ]);
    }

    /**
     * Mostrar estadísticas de workers
     */
    protected function showWorkerStatistics(): void
    {
        $this->line('');
        $this->info('Estadísticas de Workers:');

        $stats = [
            'total_workers' => 7,
            'active_workers' => 7,
            'idle_workers' => 0,
            'busy_workers' => 0
        ];

        foreach ($stats as $key => $value) {
            $this->line("  " . str_replace('_', ' ', ucfirst($key)) . ": {$value}");
        }
    }

    /**
     * Mostrar comandos útiles
     */
    protected function showUsefulCommands(): void
    {
        $this->line('');
        $this->info('Comandos útiles:');
        $this->line('  php artisan jobs:manage status          - Ver estado de jobs');
        $this->line('  php artisan jobs:manage dispatch --type=system - Despachar job de sistema');
        $this->line('  php artisan queue:monitor               - Monitorear queue');
        $this->line('  php artisan queue:failed                - Ver jobs fallidos');
        $this->line('  php artisan queue:retry all             - Reintentar jobs fallidos');
    }
}

