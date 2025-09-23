<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;

class CleanBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:clean {--dry-run : Solo mostrar qué se eliminaría}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar backups expirados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🧹 Limpiando backups expirados...');

        if ($dryRun) {
            $this->warn('🔍 Modo dry-run: Solo se mostrará qué se eliminaría');
        }

        $this->line('');

        try {
            $backupService = app(BackupService::class);

            // Obtener backups expirados
            $expiredBackups = \App\Models\Backup::expired()->get();

            if ($expiredBackups->isEmpty()) {
                $this->info('✅ No hay backups expirados para limpiar');
                return 0;
            }

            $this->line("📋 Encontrados {$expiredBackups->count()} backups expirados:");
            $this->line('');

            $totalSize = 0;
            $table = [];

            foreach ($expiredBackups as $backup) {
                $size = $backup->file_size ?? 0;
                $totalSize += $size;

                $table[] = [
                    'ID' => $backup->id,
                    'Nombre' => $backup->name,
                    'Tipo' => $backup->type,
                    'Tamaño' => $backup->formatted_file_size,
                    'Creado' => $backup->created_at->format('Y-m-d'),
                    'Expira' => $backup->expires_at->format('Y-m-d')
                ];
            }

            $this->table(['ID', 'Nombre', 'Tipo', 'Tamaño', 'Creado', 'Expira'], $table);

            $this->line('');
            $this->line("💾 Espacio total a liberar: " . $this->formatBytes($totalSize));

            if ($dryRun) {
                $this->line('');
                $this->info('🔍 Modo dry-run completado. Para ejecutar la limpieza, ejecuta el comando sin --dry-run');
                return 0;
            }

            // Confirmar eliminación
            if (!$this->confirm('¿Estás seguro de que quieres eliminar estos backups?')) {
                $this->info('❌ Operación cancelada');
                return 0;
            }

            // Ejecutar limpieza
            $deletedCount = $backupService->cleanExpiredBackups();

            $this->line('');
            $this->info("✅ Limpieza completada: {$deletedCount} backups eliminados");
            $this->line("💾 Espacio liberado: " . $this->formatBytes($totalSize));

        } catch (\Exception $e) {
            $this->error('❌ Error limpiando backups: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Formatear bytes
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
