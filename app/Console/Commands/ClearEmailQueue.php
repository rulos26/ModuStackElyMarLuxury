<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearEmailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:clear-queue {--failed : Limpiar también jobs fallidos} {--force : Forzar limpieza sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar cola de emails (jobs pendientes y opcionalmente fallidos)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clearFailed = $this->option('failed');
        $force = $this->option('force');

        try {
            // Verificar estado actual
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $this->info('📊 Estado actual de la cola:');
            $this->line("   Jobs pendientes: {$pendingJobs}");
            $this->line("   Jobs fallidos: {$failedJobs}");
            $this->line('');

            if ($pendingJobs == 0 && (!$clearFailed || $failedJobs == 0)) {
                $this->info('✅ La cola ya está limpia');
                return 0;
            }

            // Confirmación
            if (!$force) {
                $message = "¿Estás seguro de que quieres limpiar {$pendingJobs} jobs pendientes";
                if ($clearFailed && $failedJobs > 0) {
                    $message .= " y {$failedJobs} jobs fallidos";
                }
                $message .= "?";

                if (!$this->confirm($message)) {
                    $this->info('❌ Operación cancelada');
                    return 0;
                }
            }

            // Limpiar jobs pendientes
            if ($pendingJobs > 0) {
                $this->info("🧹 Limpiando {$pendingJobs} jobs pendientes...");
                DB::table('jobs')->delete();
                $this->info('✅ Jobs pendientes eliminados');
            }

            // Limpiar jobs fallidos si se solicita
            if ($clearFailed && $failedJobs > 0) {
                $this->info("🧹 Limpiando {$failedJobs} jobs fallidos...");
                DB::table('failed_jobs')->delete();
                $this->info('✅ Jobs fallidos eliminados');
            }

            $this->line('');
            $this->info('🎉 Cola de emails limpiada exitosamente');

            // Mostrar estado final
            $finalPending = DB::table('jobs')->count();
            $finalFailed = DB::table('failed_jobs')->count();

            $this->line('');
            $this->info('📊 Estado final:');
            $this->line("   Jobs pendientes: {$finalPending}");
            $this->line("   Jobs fallidos: {$finalFailed}");

        } catch (\Exception $e) {
            $this->error('❌ Error limpiando cola: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
