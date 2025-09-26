<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use JeroenNoten\LaravelAdminLte\AdminLte;

class SimulateWebContextCommand extends Command
{
    protected $signature = 'app:simulate-web-context';
    protected $description = 'Simulate web context to test database queries';

    public function handle()
    {
        $this->info('🌐 Simulando contexto web...');
        
        try {
            // Autenticar usuario
            $this->info('📋 Autenticando usuario...');
            $user = User::first();
            if ($user) {
                Auth::login($user);
                $this->info('   ✅ Usuario autenticado: ' . $user->name);
            } else {
                $this->error('   ❌ No hay usuarios en la base de datos');
                return 1;
            }
            
            // Simular consultas de AdminLTE
            $this->info('📋 Simulando consultas de AdminLTE...');
            
            try {
                $adminlte = app(AdminLte::class);
                $this->info('   ✅ Instancia de AdminLTE creada');
                
                // Probar menú
                $menu = $adminlte->menu('sidebar');
                $this->info('   ✅ Menú sidebar generado');
                $this->info('   📊 Elementos del menú: ' . count($menu));
                
                // Probar navbar
                $navbar = $adminlte->menu('navbar-left');
                $this->info('   ✅ Menú navbar-left generado');
                $this->info('   📊 Elementos del navbar: ' . count($navbar));
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en AdminLTE: ' . $e->getMessage());
            }
            
            // Simular consultas de middleware
            $this->info('📋 Simulando consultas de middleware...');
            
            try {
                // Simular consulta de ThemeMiddleware
                $themeColor = \App\Models\AppSetting::getValue('theme_color', '#007bff');
                $this->info('   ✅ Consulta theme_color exitosa: ' . $themeColor);
                
                $sidebarStyle = \App\Models\AppSetting::getValue('sidebar_style', 'light');
                $this->info('   ✅ Consulta sidebar_style exitosa: ' . $sidebarStyle);
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consultas de middleware: ' . $e->getMessage());
            }
            
            // Simular consultas de permisos
            $this->info('📋 Simulando consultas de permisos...');
            
            try {
                $permissions = $user->getAllPermissions();
                $this->info('   ✅ Consulta de permisos exitosa');
                $this->info('   📊 Permisos del usuario: ' . $permissions->count());
                
                $roles = $user->getAllRoles();
                $this->info('   ✅ Consulta de roles exitosa');
                $this->info('   📊 Roles del usuario: ' . $roles->count());
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consultas de permisos: ' . $e->getMessage());
            }
            
            // Simular consultas de configuración
            $this->info('📋 Simulando consultas de configuración...');
            
            try {
                $settings = \App\Models\AppSetting::all();
                $this->info('   ✅ Consulta de configuraciones exitosa');
                $this->info('   📊 Configuraciones: ' . $settings->count());
                
                // Probar consultas específicas
                foreach ($settings as $setting) {
                    $value = \App\Models\AppSetting::getValue($setting->key);
                    $this->info("   ✅ Consulta {$setting->key}: {$value}");
                }
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consultas de configuración: ' . $e->getMessage());
            }
            
            // Simular consultas de actividad
            $this->info('📋 Simulando consultas de actividad...');
            
            try {
                // Simular consulta de logs de actividad
                $activityLogs = DB::table('activity_logs')->count();
                $this->info('   ✅ Consulta de logs de actividad exitosa');
                $this->info('   📊 Logs de actividad: ' . $activityLogs);
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de logs: ' . $e->getMessage());
            }
            
            // Simular consultas de notificaciones
            $this->info('📋 Simulando consultas de notificaciones...');
            
            try {
                $notifications = DB::table('notifications')->count();
                $this->info('   ✅ Consulta de notificaciones exitosa');
                $this->info('   📊 Notificaciones: ' . $notifications);
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de notificaciones: ' . $e->getMessage());
            }
            
            // Simular consultas de respaldos
            $this->info('📋 Simulando consultas de respaldos...');
            
            try {
                $backups = DB::table('backups')->count();
                $this->info('   ✅ Consulta de respaldos exitosa');
                $this->info('   📊 Respaldos: ' . $backups);
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de respaldos: ' . $e->getMessage());
            }
            
            // Simular consultas de intentos de login
            $this->info('📋 Simulando consultas de intentos de login...');
            
            try {
                $loginAttempts = DB::table('login_attempts')->count();
                $this->info('   ✅ Consulta de intentos de login exitosa');
                $this->info('   📊 Intentos de login: ' . $loginAttempts);
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de intentos de login: ' . $e->getMessage());
            }
            
            // Simular consultas de IPs permitidas
            $this->info('📋 Simulando consultas de IPs permitidas...');
            
            try {
                $allowedIPs = DB::table('allowed_ips')->count();
                $this->info('   ✅ Consulta de IPs permitidas exitosa');
                $this->info('   📊 IPs permitidas: ' . $allowedIPs);
                
            } catch (\Exception $e) {
                $this->error('   ❌ Error en consulta de IPs permitidas: ' . $e->getMessage());
            }
            
            $this->info('🎯 Simulación de contexto web completada');
            
        } catch (\Exception $e) {
            $this->error('❌ Error en simulación: ' . $e->getMessage());
            $this->error('📍 Archivo: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('📍 Traza: ' . $e->getTraceAsString());
            return 1;
        } finally {
            // Cerrar sesión
            Auth::logout();
        }
        
        return 0;
    }
}



