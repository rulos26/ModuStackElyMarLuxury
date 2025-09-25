<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckFooterLogsCommand extends Command
{
    protected $signature = 'check:footer-logs';
    protected $description = 'Revisa los logs del footer para debuggear.';

    public function handle()
    {
        $this->info('🔍 Revisando logs del footer...');

        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            $this->warn('⚠️ No se encontró el archivo de logs');
            return 0;
        }

        // Leer las últimas líneas del log
        $lines = file($logFile);
        $recentLines = array_slice($lines, -100); // Últimas 100 líneas

        $this->line('');
        $this->info('📋 Últimas entradas relacionadas con footer:');
        $this->line('');

        $foundFooterLogs = false;

        foreach ($recentLines as $line) {
            if (strpos($line, 'UPDATE SECTION') !== false ||
                strpos($line, 'UPDATE APPEARANCE') !== false ||
                strpos($line, 'DEBUG FOOTER') !== false ||
                strpos($line, 'PROCESANDO DATOS') !== false ||
                strpos($line, 'DATOS PROCESADOS') !== false ||
                strpos($line, 'RESULTADO DEL GUARDADO') !== false) {

                $this->line($line);
                $foundFooterLogs = true;
            }
        }

        if (!$foundFooterLogs) {
            $this->warn('⚠️ No se encontraron logs del footer');
            $this->line('Esto significa que el formulario no se está enviando al controlador.');
            $this->line('');
            $this->info('💡 Posibles causas:');
            $this->line('1. El formulario no se está enviando correctamente');
            $this->line('2. La ruta no está configurada correctamente');
            $this->line('3. Hay errores de validación que impiden el envío');
            $this->line('4. El JavaScript está interfiriendo con el envío');
        }

        return 0;
    }
}
