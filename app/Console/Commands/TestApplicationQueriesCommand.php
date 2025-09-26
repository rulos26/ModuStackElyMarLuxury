<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\AppSetting;

class TestApplicationQueriesCommand extends Command
{
    protected $signature = 'app:test-queries';
    protected $description = 'Test application queries that might cause database connection issues';

    public function handle()
    {
        $this->info('🧪 Probando consultas de la aplicación...');
        
        try {
            // Probar consultas de modelos
            $this->info('📋 Probando consultas de modelos...');
            
            try {
                $users = User::all();
                $this->info('   ✅ Consulta User::all() exitosa');
                $this->info('   📊 Usuarios encontrados: ' . $users->count());
            } catch (\Exception $e) {
                $this->error('   ❌ Error en User::all(): ' . $e->getMessage());
            }
            
            try {
                $settings = AppSetting::all();
                $this->info('   ✅ Consulta AppSetting::all() exitosa');
                $this->info('   📊 Configuraciones encontradas: ' . $settings->count());
            } catch (\Exception $e) {
                $this->error('   ❌ Error en AppSetting::all(): ' . $e->getMessage());
            }
            
            // Probar consultas específicas de AdminLTE
            $this->info('📋 Probando consultas de AdminLTE...');
            
            try {
                // Simular consulta que podría ejecutar AdminLTE
                $user = User::first();
                if ($user) {
                    $this->info('   ✅ Usuario encontrado: ' . $user->name);
                    
                    // Probar consultas de permisos
                    $permissions = $user->getAllPermissions();
                    $this->info('   ✅ Consulta de permisos exitosa');
                    $this->info('   📊 Permisos: ' . $permissions->count());
                }
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consultas de permisos: ' . $e->getMessage());
            }
            
            // Probar consultas de configuración
            $this->info('📋 Probando consultas de configuración...');
            
            try {
                $themeColor = AppSetting::getValue('theme_color', '#007bff');
                $this->info('   ✅ Consulta theme_color exitosa: ' . $themeColor);
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta theme_color: ' . $e->getMessage());
            }
            
            try {
                $sidebarStyle = AppSetting::getValue('sidebar_style', 'light');
                $this->info('   ✅ Consulta sidebar_style exitosa: ' . $sidebarStyle);
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta sidebar_style: ' . $e->getMessage());
            }
            
            // Probar consultas de menú
            $this->info('📋 Probando consultas de menú...');
            
            try {
                // Simular consulta que podría ejecutar el menú
                $menuSettings = AppSetting::where('key', 'like', 'menu_%')->get();
                $this->info('   ✅ Consulta de configuraciones de menú exitosa');
                $this->info('   📊 Configuraciones de menú: ' . $menuSettings->count());
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de menú: ' . $e->getMessage());
            }
            
            // Probar consultas complejas
            $this->info('📋 Probando consultas complejas...');
            
            try {
                // Consulta con joins
                $usersWithRoles = DB::table('users')
                    ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->select('users.name', 'roles.name as role_name')
                    ->get();
                
                $this->info('   ✅ Consulta con joins exitosa');
                $this->info('   📊 Usuarios con roles: ' . $usersWithRoles->count());
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta con joins: ' . $e->getMessage());
            }
            
            // Probar transacciones
            $this->info('📋 Probando transacciones...');
            
            try {
                DB::transaction(function () {
                    $user = User::first();
                    if ($user) {
                        $user->touch();
                    }
                });
                
                $this->info('   ✅ Transacción exitosa');
            } catch (\Exception $e) {
                $this->error('   ❌ Error en transacción: ' . $e->getMessage());
            }
            
            $this->info('🎯 Prueba de consultas completada');
            
        } catch (\Exception $e) {
            $this->error('❌ Error en prueba de consultas: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('📍 Traza: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}



