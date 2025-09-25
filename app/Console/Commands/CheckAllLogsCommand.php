<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckAllLogsCommand extends Command
{
    protected $signature = 'check:all-logs';
    protected $description = 'Revisa todos los logs recientes para debuggear.';

    public function handle()
    {
        $this->info('🔍 Revisando todos los logs recientes...');

        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            $this->warn('⚠️ No se encontró el archivo de logs');
            return 0;
        }

        // Leer las últimas líneas del log
        $lines = file($logFile);
        $recentLines = array_slice($lines, -200); // Últimas 200 líneas

        $this->line('');
        $this->info('📋 Últimas entradas del log:');
        $this->line('');

        $foundLogs = false;

        foreach ($recentLines as $line) {
            if (strpos($line, 'UPDATE SECTION') !== false ||
                strpos($line, 'VALIDACIÓN') !== false ||
                strpos($line, 'UPDATE SETTINGS BY SECTION') !== false ||
                strpos($line, 'UPDATE APPEARANCE') !== false ||
                strpos($line, 'DEBUG FOOTER') !== false ||
                strpos($line, 'PROCESANDO DATOS') !== false ||
                strpos($line, 'DATOS PROCESADOS') !== false ||
                strpos($line, 'RESULTADO DEL GUARDADO') !== false ||
                strpos($line, 'Ejecutando updateAppearanceSettings') !== false ||
                strpos($line, 'Sección: appearance') !== false) {

                $this->line(trim($line));
                $foundLogs = true;
            }
        }

        if (!$foundLogs) {
            $this->warn('⚠️ No se encontraron logs relevantes');
            $this->line('');
            $this->info('💡 Últimas 10 líneas del log:');
            $lastLines = array_slice($lines, -10);
            foreach ($lastLines as $line) {
                $this->line(trim($line));
            }
        }

        return 0;
    }
}
