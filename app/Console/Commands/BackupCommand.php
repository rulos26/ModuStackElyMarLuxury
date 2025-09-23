<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\Backup;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create
                           {type : Tipo de backup (full, database, files)}
                           {--name= : Nombre personalizado del backup}
                           {--compress : Comprimir el backup}
                           {--encrypt : Encriptar el backup}
                           {--retention=30 : Días de retención}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear backup del sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $name = $this->option('name');
        $compress = $this->option('compress');
        $encrypt = $this->option('encrypt');
        $retention = (int) $this->option('retention');

        // Validar tipo de backup
        if (!in_array($type, ['full', 'database', 'files'])) {
            $this->error('❌ Tipo de backup inválido. Usa: full, database, o files');
            return 1;
        }

        $this->info("🗂️ Creando backup {$type}...");

        if ($name) {
            $this->line("   Nombre personalizado: {$name}");
        }

        if ($compress) {
            $this->line("   ✅ Compresión habilitada");
        }

        if ($encrypt) {
            $this->line("   🔐 Encriptación habilitada");
        }

        $this->line("   📅 Retención: {$retention} días");
        $this->line('');

        try {
            $backupService = app(BackupService::class);

            $options = [
                'compress' => $compress,
                'encrypt' => $encrypt,
                'retention_days' => $retention
            ];

            $startTime = now();

            switch ($type) {
                case 'full':
                    $backup = $backupService->createFullBackup($name, $options);
                    break;
                case 'database':
                    $backup = $backupService->createDatabaseBackup($name, $options);
                    break;
                case 'files':
                    $backup = $backupService->createFilesBackup($name, $options);
                    break;
            }

            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            if ($backup->status === 'completed') {
                $this->info('✅ ¡Backup completado exitosamente!');
                $this->line('');
                $this->line('📊 Detalles del backup:');
                $this->line("   ID: {$backup->id}");
                $this->line("   Nombre: {$backup->name}");
                $this->line("   Tipo: {$backup->type}");
                $this->line("   Archivo: {$backup->file_name}");
                $this->line("   Tamaño: {$backup->formatted_file_size}");
                $this->line("   Duración: {$backup->formatted_execution_time}");
                $this->line("   Hash: {$backup->file_hash}");
                $this->line("   Expira: {$backup->expires_at->format('Y-m-d H:i:s')}");

                return 0;
            } else {
                $this->error('❌ Error en el backup');
                $this->line("   Estado: {$backup->status}");
                if ($backup->error_message) {
                    $this->line("   Error: {$backup->error_message}");
                }

                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error creando backup: ' . $e->getMessage());
            return 1;
        }
    }
}
