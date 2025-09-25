<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnoseDatabaseErrorCommand extends Command
{
    protected $signature = 'db:diagnose-error';
    protected $description = 'Diagnose specific database error in Connection.php line 822';

    public function handle()
    {
        $this->info('🔍 Diagnosticando error específico de base de datos...');

        try {
            // Verificar configuración de la base de datos
            $this->info('📋 Verificando configuración...');
            $config = config('database.connections.mysql');

            $this->info('   Host: ' . ($config['host'] ?? 'N/A'));
            $this->info('   Puerto: ' . ($config['port'] ?? 'N/A'));
            $this->info('   Base de datos: ' . ($config['database'] ?? 'N/A'));
            $this->info('   Usuario: ' . ($config['username'] ?? 'N/A'));
            $this->info('   Charset: ' . ($config['charset'] ?? 'N/A'));
            $this->info('   Collation: ' . ($config['collation'] ?? 'N/A'));
            $this->info('   Strict: ' . ($config['strict'] ? 'Sí' : 'No'));

            // Verificar conexión
            $this->info('📋 Verificando conexión...');
            $connection = DB::connection();
            $this->info('   ✅ Conexión establecida');

            // Verificar información del servidor
            $this->info('📋 Información del servidor...');
            $serverInfo = DB::select('SELECT VERSION() as version, @@sql_mode as sql_mode');
            if ($serverInfo) {
                $this->info('   Versión MySQL: ' . $serverInfo[0]->version);
                $this->info('   SQL Mode: ' . $serverInfo[0]->sql_mode);
            }

            // Verificar configuración de MySQL
            $this->info('📋 Configuración de MySQL...');
            $variables = [
                'max_connections',
                'wait_timeout',
                'interactive_timeout',
                'max_allowed_packet',
                'innodb_buffer_pool_size'
            ];

            foreach ($variables as $variable) {
                try {
                    $result = DB::select("SHOW VARIABLES LIKE '{$variable}'");
                    if ($result) {
                        $this->info("   {$variable}: " . $result[0]->Value);
                    }
                } catch (\Exception $e) {
                    $this->warn("   ⚠️ No se pudo obtener {$variable}: " . $e->getMessage());
                }
            }

            // Verificar estado de la conexión
            $this->info('📋 Estado de la conexión...');
            $status = DB::select('SHOW STATUS LIKE "Connections"');
            if ($status) {
                $this->info('   Conexiones: ' . $status[0]->Value);
            }

            $status = DB::select('SHOW STATUS LIKE "Threads_connected"');
            if ($status) {
                $this->info('   Hilos conectados: ' . $status[0]->Value);
            }

            // Verificar tablas y índices
            $this->info('📋 Verificando tablas y índices...');
            $tables = ['users', 'app_settings', 'permissions', 'roles'];

            foreach ($tables as $table) {
                try {
                    $indexes = DB::select("SHOW INDEX FROM {$table}");
                    $this->info("   ✅ Tabla {$table} - Índices: " . count($indexes));
                } catch (\Exception $e) {
                    $this->warn("   ⚠️ Error en tabla {$table}: " . $e->getMessage());
                }
            }

            // Probar consultas que podrían causar problemas
            $this->info('📋 Probando consultas problemáticas...');

            try {
                // Consulta con ORDER BY
                $users = DB::table('users')->orderBy('id')->get();
                $this->info('   ✅ Consulta ORDER BY exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en ORDER BY: ' . $e->getMessage());
            }

            try {
                // Consulta con LIMIT
                $users = DB::table('users')->limit(10)->get();
                $this->info('   ✅ Consulta LIMIT exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en LIMIT: ' . $e->getMessage());
            }

            try {
                // Consulta con WHERE
                $users = DB::table('users')->where('id', '>', 0)->get();
                $this->info('   ✅ Consulta WHERE exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en WHERE: ' . $e->getMessage());
            }

            try {
                // Consulta con JOIN
                $users = DB::table('users')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->get();
                $this->info('   ✅ Consulta JOIN exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en JOIN: ' . $e->getMessage());
            }

            // Verificar logs de error
            $this->info('📋 Verificando logs de error...');
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                $errorCount = substr_count($logContent, 'ERROR');
                $this->info("   Logs de error encontrados: {$errorCount}");

                if ($errorCount > 0) {
                    $this->warn('   ⚠️ Hay errores en los logs');
                }
            } else {
                $this->info('   ✅ No hay archivo de log');
            }

            $this->info('🎯 Diagnóstico completado');

        } catch (\Exception $e) {
            $this->error('❌ Error en diagnóstico: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('📍 Traza: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
