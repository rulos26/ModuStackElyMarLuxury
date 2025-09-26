<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseDatabaseConnectionCommand extends Command
{
    protected $signature = 'db:diagnose-connection';
    protected $description = 'Diagnose database connection issues';

    public function handle()
    {
        $this->info('🔍 Diagnosticando conexión a la base de datos...');
        
        try {
            // Verificar conexión básica
            $this->info('📋 Verificando conexión básica...');
            $connection = DB::connection();
            $this->info('   ✅ Conexión establecida');
            
            // Verificar información de la conexión
            $this->info('📋 Información de la conexión:');
            $this->info('   Driver: ' . $connection->getDriverName());
            $this->info('   Base de datos: ' . $connection->getDatabaseName());
            $this->info('   Host: ' . config('database.connections.mysql.host'));
            $this->info('   Puerto: ' . config('database.connections.mysql.port'));
            
            // Probar consulta simple
            $this->info('📋 Probando consulta simple...');
            $result = DB::select('SELECT 1 as test');
            $this->info('   ✅ Consulta simple exitosa');
            
            // Verificar tablas principales
            $this->info('📋 Verificando tablas principales...');
            $tables = [
                'users',
                'app_settings',
                'permissions',
                'roles',
                'model_has_permissions',
                'model_has_roles',
                'role_has_permissions'
            ];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $this->info("   ✅ Tabla {$table} existe");
                    
                    // Contar registros
                    try {
                        $count = DB::table($table)->count();
                        $this->info("      Registros: {$count}");
                    } catch (\Exception $e) {
                        $this->warn("      ⚠️ Error contando registros: " . $e->getMessage());
                    }
                } else {
                    $this->warn("   ⚠️ Tabla {$table} NO existe");
                }
            }
            
            // Probar consultas específicas que pueden causar problemas
            $this->info('📋 Probando consultas específicas...');
            
            try {
                // Consulta de usuarios
                $users = DB::table('users')->limit(1)->get();
                $this->info('   ✅ Consulta de usuarios exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de usuarios: ' . $e->getMessage());
            }
            
            try {
                // Consulta de configuraciones
                $settings = DB::table('app_settings')->limit(1)->get();
                $this->info('   ✅ Consulta de configuraciones exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de configuraciones: ' . $e->getMessage());
            }
            
            try {
                // Consulta de permisos
                $permissions = DB::table('permissions')->limit(1)->get();
                $this->info('   ✅ Consulta de permisos exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de permisos: ' . $e->getMessage());
            }
            
            // Verificar configuración de la base de datos
            $this->info('📋 Verificando configuración...');
            $config = config('database.connections.mysql');
            $this->info('   Host: ' . ($config['host'] ?? 'N/A'));
            $this->info('   Puerto: ' . ($config['port'] ?? 'N/A'));
            $this->info('   Base de datos: ' . ($config['database'] ?? 'N/A'));
            $this->info('   Usuario: ' . ($config['username'] ?? 'N/A'));
            $this->info('   Charset: ' . ($config['charset'] ?? 'N/A'));
            $this->info('   Collation: ' . ($config['collation'] ?? 'N/A'));
            
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



