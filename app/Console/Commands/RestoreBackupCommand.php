<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Backup;

class RestoreBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore
                           {id : ID del backup a restaurar}
                           {--force : Forzar restauración sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaurar backup del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backupId = $this->argument('id');
        $force = $this->option('force');

        $this->info("🔄 Restaurando backup ID: {$backupId}");

        try {
            // Buscar backup
            $backup = Backup::find($backupId);

            if (!$backup) {
                $this->error("❌ Backup con ID {$backupId} no encontrado");
                return 1;
            }

            // Verificar estado del backup
            if ($backup->status !== 'completed') {
                $this->error("❌ El backup no está completado. Estado: {$backup->status}");
                return 1;
            }

            if ($backup->isExpired()) {
                $this->error("❌ El backup ha expirado: {$backup->expires_at}");
                return 1;
            }

            // Mostrar información del backup
            $this->line('');
            $this->line('📋 Información del backup:');
            $this->line("   Nombre: {$backup->name}");
            $this->line("   Tipo: {$backup->type}");
            $this->line("   Archivo: {$backup->file_name}");
            $this->line("   Tamaño: {$backup->formatted_file_size}");
            $this->line("   Creado: {$backup->created_at->format('Y-m-d H:i:s')}");
            $this->line("   Hash: {$backup->file_hash}");
            $this->line('');

            // Verificar integridad
            $backupService = app(BackupService::class);

            if (!$backupService->verifyBackup($backup)) {
                $this->error("❌ El archivo de backup está corrupto o no existe");
                return 1;
            }

            $this->info("✅ Integridad del backup verificada");

            // Confirmar restauración
            if (!$force) {
                $this->warn('⚠️  ADVERTENCIA: La restauración puede sobrescribir datos existentes');

                if (!$this->confirm('¿Estás seguro de que quieres continuar con la restauración?')) {
                    $this->info('❌ Restauración cancelada');
                    return 0;
                }
            }

            $this->line('');
            $this->info('🚀 Iniciando restauración...');

            $startTime = now();
            $success = $backupService->restoreBackup($backup);
            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            if ($success) {
                $this->info('✅ ¡Restauración completada exitosamente!');
                $this->line("   Duración: {$duration} segundos");
                $this->line('');
                $this->info('🎉 El sistema ha sido restaurado desde el backup');

                return 0;
            } else {
                $this->error('❌ Error durante la restauración');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error restaurando backup: ' . $e->getMessage());
            return 1;
        }
    }
}
