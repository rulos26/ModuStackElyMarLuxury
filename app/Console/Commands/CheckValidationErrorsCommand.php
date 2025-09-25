<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckValidationErrorsCommand extends Command
{
    protected $signature = 'check:validation-errors';
    protected $description = 'Revisa los errores de validación en los logs.';

    public function handle()
    {
        $this->info('🔍 Revisando errores de validación...');

        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            $this->warn('⚠️ No se encontró el archivo de logs');
            return 0;
        }

        // Leer todo el archivo
        $content = file_get_contents($logFile);

        // Buscar errores de validación
        $this->line('');
        $this->info('📋 Errores de validación encontrados:');
        $this->line('');

        // Buscar patrones de error de validación
        $patterns = [
            '/ERROR DE VALIDACIÓN.*?Errores:.*?(\{.*?\})/s',
            '/ValidationException.*?(\{.*?\})/s',
            '/The given data was invalid.*?(\{.*?\})/s'
        ];

        $foundErrors = false;

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $this->line('Error encontrado:');
                    $this->line($match);
                    $this->line('');
                    $foundErrors = true;
                }
            }
        }

        if (!$foundErrors) {
            $this->warn('⚠️ No se encontraron errores de validación específicos');
            $this->line('');
            $this->info('💡 Buscando líneas con ERROR DE VALIDACIÓN...');

            // Buscar líneas simples
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                if (strpos($line, 'ERROR DE VALIDACIÓN') !== false) {
                    $this->line($line);
                    $foundErrors = true;
                }
            }
        }

        if (!$foundErrors) {
            $this->warn('⚠️ No se encontraron errores de validación');
            $this->line('');
            $this->info('💡 Últimas 20 líneas del log:');
            $lines = explode("\n", $content);
            $lastLines = array_slice($lines, -20);
            foreach ($lastLines as $line) {
                $this->line(trim($line));
            }
        }

        return 0;
    }
}
