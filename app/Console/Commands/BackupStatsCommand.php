<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Backup;

class BackupStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:stats
                           {--type= : Filtrar por tipo (full, database, files)}
                           {--days=30 : Período en días}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mostrar estadísticas de backups';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $days = (int) $this->option('days');

        $this->info('📊 Estadísticas de Backups');

        if ($type) {
            $this->line("   Tipo: {$type}");
        }

        $this->line("   Período: {$days} días");
        $this->line('');

        try {
            $backupService = app(BackupService::class);
            $stats = $backupService->getStats($type, $days);

            // Estadísticas generales
            $this->line('📈 Estadísticas Generales:');
            $this->line("   Total de backups: {$stats['total']}");
            $this->line("   ✅ Completados: {$stats['completed']}");
            $this->line("   ❌ Fallidos: {$stats['failed']}");
            $this->line("   🔄 En progreso: {$stats['in_progress']}");
            $this->line("   ⏰ Expirados: {$stats['expired']}");
            $this->line("   📊 Tasa de éxito: {$stats['success_rate']}%");
            $this->line("   💾 Tamaño total: {$stats['formatted_total_size']}");
            $this->line('');

            // Estadísticas por tipo
            if (!empty($stats['by_type'])) {
                $this->line('📋 Distribución por Tipo:');
                foreach ($stats['by_type'] as $typeName => $count) {
                    $percentage = $stats['total'] > 0 ? round(($count / $stats['total']) * 100, 1) : 0;
                    $this->line("   {$typeName}: {$count} ({$percentage}%)");
                }
                $this->line('');
            }

            // Backups recientes
            $recentBackups = Backup::getRecent(5);
            if ($recentBackups->isNotEmpty()) {
                $this->line('🕒 Backups Recientes:');
                $table = [];

                foreach ($recentBackups as $backup) {
                    $table[] = [
                        'ID' => $backup->id,
                        'Nombre' => substr($backup->name, 0, 20) . '...',
                        'Tipo' => $backup->type,
                        'Estado' => $backup->status,
                        'Tamaño' => $backup->formatted_file_size,
                        'Fecha' => $backup->created_at->format('Y-m-d H:i')
                    ];
                }

                $this->table(['ID', 'Nombre', 'Tipo', 'Estado', 'Tamaño', 'Fecha'], $table);
                $this->line('');
            }

            // Estado del sistema
            $this->line('🔍 Estado del Sistema:');
            $pendingBackups = Backup::inProgress()->count();
            $expiredBackups = Backup::expired()->count();

            if ($pendingBackups > 0) {
                $this->warn("   ⚠️  Hay {$pendingBackups} backups en progreso");
            }

            if ($expiredBackups > 0) {
                $this->warn("   ⚠️  Hay {$expiredBackups} backups expirados que pueden ser eliminados");
            }

            if ($pendingBackups === 0 && $expiredBackups === 0) {
                $this->info('   ✅ Sistema de backups en buen estado');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error obteniendo estadísticas: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
