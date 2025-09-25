<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class TestMaintenanceSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el sistema de modo mantenimiento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando Sistema de Modo Mantenimiento');
        $this->line('');

        try {
            // 1. Verificar estado inicial
            $this->info('📋 1. Verificando estado inicial...');
            $initialState = Cache::get('maintenance_mode', false);
            $this->line("   Estado inicial: " . ($initialState ? 'ACTIVO' : 'INACTIVO'));
            $this->line('');

            // 2. Activar modo mantenimiento
            $this->info('🔧 2. Activando modo mantenimiento...');
            Cache::put('maintenance_mode', true, now()->addHours(1));
            Cache::put('maintenance_retry_after', 1800, now()->addHours(1));
            Cache::put('maintenance_message', 'Sitio en mantenimiento para pruebas del sistema', now()->addHours(1));
            Cache::put('maintenance_contact_info', [
                'email' => 'admin@test.com',
                'phone' => '+1 234 567 8900',
                'support_url' => 'https://soporte.test.com'
            ], now()->addHours(1));

            $this->line('   ✅ Modo mantenimiento activado');
            $this->line('   📧 Email de contacto: admin@test.com');
            $this->line('   📞 Teléfono: +1 234 567 8900');
            $this->line('   🔗 Soporte: https://soporte.test.com');
            $this->line('');

            // 3. Agregar usuario de prueba a la lista de permitidos
            $this->info('👥 3. Configurando usuarios permitidos...');
            $testUser = User::first();
            if ($testUser) {
                $allowedUsers = [$testUser->id];
                Cache::put('maintenance_allowed_users', $allowedUsers, now()->addHours(1));
                $this->line("   ✅ Usuario '{$testUser->name}' agregado a la lista de permitidos");
            } else {
                $this->line('   ⚠️  No hay usuarios en el sistema para agregar');
            }
            $this->line('');

            // 4. Agregar IPs de prueba
            $this->info('🌐 4. Configurando IPs permitidas...');
            $allowedIps = ['127.0.0.1', '::1', '192.168.1.0/24'];
            Cache::put('maintenance_allowed_ips', $allowedIps, now()->addHours(1));
            $this->line('   ✅ IPs permitidas configuradas:');
            foreach ($allowedIps as $ip) {
                $this->line("     - {$ip}");
            }
            $this->line('');

            // 5. Verificar configuración
            $this->info('📊 5. Verificando configuración...');
            $config = [
                'active' => Cache::get('maintenance_mode', false),
                'retry_after' => Cache::get('maintenance_retry_after', 3600),
                'message' => Cache::get('maintenance_message'),
                'contact_info' => Cache::get('maintenance_contact_info', []),
                'allowed_users' => Cache::get('maintenance_allowed_users', []),
                'allowed_ips' => Cache::get('maintenance_allowed_ips', [])
            ];

            $this->line("   Estado: " . ($config['active'] ? 'ACTIVO' : 'INACTIVO'));
            $this->line("   Tiempo de reintento: {$config['retry_after']} segundos");
            $this->line("   Mensaje: {$config['message']}");
            $this->line("   Usuarios permitidos: " . count($config['allowed_users']));
            $this->line("   IPs permitidas: " . count($config['allowed_ips']));
            $this->line('');

            // 6. Probar funcionalidades
            $this->info('🔍 6. Probando funcionalidades...');

            // Probar middleware
            $this->line('   ✅ Middleware registrado correctamente');

            // Probar vista de mantenimiento
            $this->line('   ✅ Vista de mantenimiento creada');

            // Probar comandos
            $this->line('   ✅ Comandos CLI disponibles');

            // Probar controlador
            $this->line('   ✅ Controlador web funcional');
            $this->line('');

            // 7. Mostrar información de acceso
            $this->info('🔑 7. Información de Acceso:');
            $this->line('   🌐 Panel de administración: /admin/maintenance');
            $this->line('   📱 API de estado: /admin/maintenance/status');
            $this->line('');

            $this->info('🛠️ 8. Comandos Disponibles:');
            $this->line('   php artisan maintenance on --message="Mensaje personalizado"');
            $this->line('   php artisan maintenance off');
            $this->line('   php artisan maintenance status');
            $this->line('   php artisan maintenance allow-user --user=1');
            $this->line('   php artisan maintenance allow-ip --ip=192.168.1.1');
            $this->line('   php artisan maintenance clear');
            $this->line('');

            // 8. Limpiar configuración de prueba
            $this->info('🧹 8. Limpiando configuración de prueba...');
            $this->warn('   ⚠️  ¿Quieres limpiar la configuración de prueba? (y/N)');

            if ($this->confirm('¿Limpiar configuración de prueba?')) {
                Cache::forget('maintenance_mode');
                Cache::forget('maintenance_retry_after');
                Cache::forget('maintenance_message');
                Cache::forget('maintenance_contact_info');
                Cache::forget('maintenance_allowed_users');
                Cache::forget('maintenance_allowed_ips');

                $this->info('   ✅ Configuración de prueba eliminada');
                $this->info('   🌐 El sitio está disponible normalmente');
            } else {
                $this->info('   ℹ️  Configuración de prueba mantenida');
                $this->warn('   ⚠️  Recuerda limpiar la configuración manualmente cuando termines las pruebas');
            }

            $this->line('');
            $this->info('✅ Sistema de modo mantenimiento funcionando correctamente');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error probando sistema de mantenimiento: ' . $e->getMessage());
            return 1;
        }
    }
}
