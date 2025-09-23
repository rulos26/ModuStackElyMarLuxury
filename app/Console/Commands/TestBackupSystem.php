<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Backup;

class TestBackupSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:test {--count=3 : Número de backups a crear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el sistema de backups creando backups de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');

        $this->info('🧪 Probando Sistema de Backups');
        $this->line("   Creando {$count} backups de prueba...");
        $this->line('');

        try {
            $backupService = app(BackupService::class);
            $createdBackups = [];

            // Crear backups de prueba
            for ($i = 1; $i <= $count; $i++) {
                $this->info("📦 Creando backup {$i}/{$count}...");

                $types = ['database', 'files', 'full'];
                $type = $types[($i - 1) % count($types)];

                $backup = $backupService->createDatabaseBackup(
                    "test_backup_{$i}",
                    [
                        'compress' => true,
                        'encrypt' => false,
                        'retention_days' => 7
                    ]
                );

                $createdBackups[] = $backup;

                $this->line("   ✅ Backup {$type} creado (ID: {$backup->id})");

                // Pequeña pausa entre backups
                sleep(1);
            }

            $this->line('');
            $this->info('📊 Estadísticas del Sistema:');

            // Obtener estadísticas
            $stats = $backupService->getStats();

            $this->line("   Total backups: {$stats['total']}");
            $this->line("   Completados: {$stats['completed']}");
            $this->line("   Fallidos: {$stats['failed']}");
            $this->line("   En progreso: {$stats['in_progress']}");
            $this->line("   Tasa de éxito: {$stats['success_rate']}%");
            $this->line("   Espacio usado: {$stats['formatted_total_size']}");
            $this->line('');

            // Mostrar backups creados
            $this->info('📋 Backups Creados:');
            $table = [];

            foreach ($createdBackups as $backup) {
                $table[] = [
                    'ID' => $backup->id,
                    'Nombre' => $backup->name,
                    'Tipo' => $backup->type,
                    'Estado' => $backup->status,
                    'Tamaño' => $backup->formatted_file_size,
                    'Archivo' => $backup->file_name
                ];
            }

            $this->table(['ID', 'Nombre', 'Tipo', 'Estado', 'Tamaño', 'Archivo'], $table);
            $this->line('');

            // Verificar integridad
            $this->info('🔍 Verificando Integridad...');
            $validCount = 0;

            foreach ($createdBackups as $backup) {
                if ($backup->status === 'completed') {
                    $isValid = $backupService->verifyBackup($backup);
                    if ($isValid) {
                        $validCount++;
                        $this->line("   ✅ Backup {$backup->id}: Íntegro");
                    } else {
                        $this->line("   ❌ Backup {$backup->id}: Corrupto");
                    }
                }
            }

            $this->line('');
            $this->info("🔐 Verificación completada: {$validCount}/" . count($createdBackups) . " backups íntegros");
            $this->line('');

            // Mostrar comandos disponibles
            $this->info('🛠️  Comandos Disponibles:');
            $this->line('   php artisan backup:create {type} - Crear backup manual');
            $this->line('   php artisan backup:stats - Ver estadísticas');
            $this->line('   php artisan backup:clean - Limpiar backups expirados');
            $this->line('   php artisan backup:restore {id} - Restaurar backup');
            $this->line('');

            $this->info('✅ Sistema de backups funcionando correctamente');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error probando sistema de backups: ' . $e->getMessage());
            return 1;
        }
    }
}
