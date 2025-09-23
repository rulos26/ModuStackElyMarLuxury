<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendEmailJob;
use App\Jobs\SendBulkEmailJob;

class ProcessEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process-queue {--limit=10 : Número máximo de jobs a procesar} {--timeout=60 : Timeout en segundos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar cola de emails de forma controlada';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $timeout = (int) $this->option('timeout');

        $this->info("📧 Procesando cola de emails (límite: {$limit}, timeout: {$timeout}s)");
        $this->line('');

        try {
            // Verificar estado inicial
            $initialJobs = DB::table('jobs')->count();
            $this->line("Jobs en cola al inicio: {$initialJobs}");

            if ($initialJobs == 0) {
                $this->info('✅ No hay jobs pendientes en la cola');
                return 0;
            }

            // Procesar jobs
            $processed = 0;
            $startTime = time();

            while ($processed < $limit && (time() - $startTime) < $timeout) {
                $currentJobs = DB::table('jobs')->count();

                if ($currentJobs == 0) {
                    $this->info('✅ Todos los jobs han sido procesados');
                    break;
                }

                // Obtener el siguiente job
                $job = DB::table('jobs')->orderBy('id', 'asc')->first();

                if (!$job) {
                    break;
                }

                try {
                    $this->line("Procesando job ID: {$job->id}");

                    // Decodificar payload para obtener información del job
                    $payload = json_decode($job->payload, true);
                    $jobClass = $payload['displayName'] ?? 'Unknown';

                    $this->line("   Tipo: {$jobClass}");
                    $this->line("   Cola: {$job->queue}");
                    $this->line("   Intentos: {$job->attempts}");

                    // Procesar job usando el worker de Laravel
                    $this->call('queue:work', [
                        '--once' => true,
                        '--timeout' => 30,
                        '--memory' => 128
                    ]);

                    $processed++;
                    $this->info("   ✅ Job procesado exitosamente");

                } catch (\Exception $e) {
                    $this->error("   ❌ Error procesando job: " . $e->getMessage());
                }

                $this->line('');
            }

            // Mostrar resumen
            $finalJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $this->info('📊 Resumen del Procesamiento:');
            $this->line("   Jobs procesados: {$processed}");
            $this->line("   Jobs restantes: {$finalJobs}");
            $this->line("   Jobs fallidos: {$failedJobs}");
            $this->line("   Tiempo transcurrido: " . (time() - $startTime) . "s");

            if ($finalJobs > 0) {
                $this->warn("⚠️  Aún hay {$finalJobs} jobs pendientes");
                $this->line('   Ejecuta nuevamente: php artisan email:process-queue');
            }

            if ($failedJobs > 0) {
                $this->error("❌ Hay {$failedJobs} jobs fallidos");
                $this->line('   Revisa: php artisan queue:failed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error procesando cola: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
