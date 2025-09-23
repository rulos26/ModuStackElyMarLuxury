<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class QueueMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor {--stats : Mostrar estadísticas detalladas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorear el estado del sistema de colas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Monitoreo del Sistema de Colas');
        $this->line('');

        try {
            // Verificar estado de la cola
            $this->checkQueueStatus();

            if ($this->option('stats')) {
                $this->showDetailedStats();
            }

            $this->showQueueHealth();

        } catch (\Exception $e) {
            $this->error('❌ Error monitoreando colas: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Verificar estado de la cola
     */
    protected function checkQueueStatus()
    {
        $this->info('🔍 Estado de la Cola:');

        try {
            // Verificar si hay jobs pendientes
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $this->line('   Jobs pendientes: ' . $pendingJobs);
            $this->line('   Jobs fallidos: ' . $failedJobs);

            if ($pendingJobs > 0) {
                $this->warn('   ⚠️  Hay jobs pendientes en la cola');
            } else {
                $this->info('   ✅ Cola vacía - sin jobs pendientes');
            }

            if ($failedJobs > 0) {
                $this->error('   ❌ Hay jobs fallidos que requieren atención');
            } else {
                $this->info('   ✅ Sin jobs fallidos');
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Error accediendo a la base de datos: ' . $e->getMessage());
        }

        $this->line('');
    }

    /**
     * Mostrar estadísticas detalladas
     */
    protected function showDetailedStats()
    {
        $this->info('📈 Estadísticas Detalladas:');

        try {
            // Estadísticas por tipo de job
            $emailJobs = DB::table('jobs')
                ->where('payload', 'like', '%SendEmailJob%')
                ->count();

            $bulkEmailJobs = DB::table('jobs')
                ->where('payload', 'like', '%SendBulkEmailJob%')
                ->count();

            $this->line('   Jobs de email individual: ' . $emailJobs);
            $this->line('   Jobs de email masivo: ' . $bulkEmailJobs);
            $this->line('');

            // Jobs más antiguos
            $oldestJob = DB::table('jobs')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($oldestJob) {
                $this->line('   Job más antiguo: ' . $oldestJob->created_at);
            }

            // Estadísticas de jobs fallidos
            if (DB::table('failed_jobs')->count() > 0) {
                $this->line('');
                $this->warn('   📋 Jobs Fallidos Recientes:');

                $recentFailures = DB::table('failed_jobs')
                    ->orderBy('failed_at', 'desc')
                    ->limit(3)
                    ->get();

                foreach ($recentFailures as $failure) {
                    $this->line('   - ' . $failure->failed_at . ': ' . substr($failure->exception, 0, 100) . '...');
                }
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Error obteniendo estadísticas: ' . $e->getMessage());
        }

        $this->line('');
    }

    /**
     * Mostrar salud del sistema
     */
    protected function showQueueHealth()
    {
        $this->info('🏥 Salud del Sistema:');

        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            // Calcular salud del sistema
            if ($failedJobs == 0 && $pendingJobs < 10) {
                $health = 'Excelente';
                $color = 'info';
            } elseif ($failedJobs < 5 && $pendingJobs < 50) {
                $health = 'Buena';
                $color = 'info';
            } elseif ($failedJobs < 10 && $pendingJobs < 100) {
                $health = 'Regular';
                $color = 'warn';
            } else {
                $health = 'Crítica';
                $color = 'error';
            }

            $this->line('   Estado general: ' . $health);
            $this->line('   Jobs pendientes: ' . $pendingJobs);
            $this->line('   Jobs fallidos: ' . $failedJobs);

            if ($health === 'Crítica') {
                $this->line('');
                $this->error('   🚨 ACCIÓN REQUERIDA:');
                $this->line('   - Revisar jobs fallidos: php artisan queue:failed');
                $this->line('   - Procesar cola: php artisan queue:work');
                $this->line('   - Limpiar jobs fallidos: php artisan queue:flush');
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Error evaluando salud: ' . $e->getMessage());
        }

        $this->line('');
        $this->info('💡 Comandos útiles:');
        $this->line('   php artisan queue:work          - Procesar cola');
        $this->line('   php artisan queue:failed        - Ver jobs fallidos');
        $this->line('   php artisan queue:retry all      - Reintentar jobs fallidos');
        $this->line('   php artisan queue:monitor --stats - Estadísticas detalladas');
    }
}
