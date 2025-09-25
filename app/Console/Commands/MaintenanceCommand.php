<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class MaintenanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance
                           {action : Acción a realizar (on, off, status, allow-user, allow-ip, remove-user, remove-ip, clear)}
                           {--user= : ID o email del usuario para permitir acceso}
                           {--ip= : IP para permitir acceso}
                           {--retry-after=3600 : Tiempo en segundos para reintentar}
                           {--message= : Mensaje personalizado de mantenimiento}
                           {--contact-email= : Email de contacto durante mantenimiento}
                           {--contact-phone= : Teléfono de contacto durante mantenimiento}
                           {--support-url= : URL de soporte durante mantenimiento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar el modo mantenimiento del sitio';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'on':
                return $this->enableMaintenance();
            case 'off':
                return $this->disableMaintenance();
            case 'status':
                return $this->showStatus();
            case 'allow-user':
                return $this->allowUser();
            case 'allow-ip':
                return $this->allowIp();
            case 'remove-user':
                return $this->removeUser();
            case 'remove-ip':
                return $this->removeIp();
            case 'clear':
                return $this->clearAll();
            default:
                $this->error('❌ Acción no válida. Usa: on, off, status, allow-user, allow-ip, remove-user, remove-ip, clear');
                return 1;
        }
    }

    /**
     * Activar modo mantenimiento
     */
    protected function enableMaintenance(): int
    {
        $this->info('🔧 Activando modo mantenimiento...');

        try {
            // Configurar modo mantenimiento
            Cache::put('maintenance_mode', true, now()->addHours(24));

            // Configurar tiempo de reintento
            $retryAfter = (int) $this->option('retry-after');
            Cache::put('maintenance_retry_after', $retryAfter, now()->addHours(24));

            // Configurar mensaje personalizado
            $message = $this->option('message');
            if ($message) {
                Cache::put('maintenance_message', $message, now()->addHours(24));
            }

            // Configurar información de contacto
            $contactInfo = [];
            if ($this->option('contact-email')) {
                $contactInfo['email'] = $this->option('contact-email');
            }
            if ($this->option('contact-phone')) {
                $contactInfo['phone'] = $this->option('contact-phone');
            }
            if ($this->option('support-url')) {
                $contactInfo['support_url'] = $this->option('support-url');
            }

            if (!empty($contactInfo)) {
                Cache::put('maintenance_contact_info', $contactInfo, now()->addHours(24));
            }

            $this->info('✅ Modo mantenimiento activado');
            $this->line('');
            $this->line('📋 Configuración:');
            $this->line("   Tiempo de reintento: {$retryAfter} segundos");
            if ($message) {
                $this->line("   Mensaje: {$message}");
            }
            if (!empty($contactInfo)) {
                $this->line('   Información de contacto configurada');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error activando modo mantenimiento: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Desactivar modo mantenimiento
     */
    protected function disableMaintenance(): int
    {
        $this->info('🔧 Desactivando modo mantenimiento...');

        try {
            Cache::forget('maintenance_mode');
            Cache::forget('maintenance_retry_after');
            Cache::forget('maintenance_message');
            Cache::forget('maintenance_contact_info');

            $this->info('✅ Modo mantenimiento desactivado');
            $this->info('🌐 El sitio está disponible nuevamente');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error desactivando modo mantenimiento: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Mostrar estado del modo mantenimiento
     */
    protected function showStatus(): int
    {
        $this->info('📊 Estado del Modo Mantenimiento');
        $this->line('');

        $isActive = Cache::get('maintenance_mode', false);

        if ($isActive) {
            $this->line('🔧 Estado: <fg=red>ACTIVO</>');

            $retryAfter = Cache::get('maintenance_retry_after', 3600);
            $message = Cache::get('maintenance_message');
            $contactInfo = Cache::get('maintenance_contact_info', []);

            $this->line("   Tiempo de reintento: {$retryAfter} segundos");

            if ($message) {
                $this->line("   Mensaje personalizado: {$message}");
            }

            if (!empty($contactInfo)) {
                $this->line('   Información de contacto:');
                foreach ($contactInfo as $key => $value) {
                    $this->line("     {$key}: {$value}");
                }
            }

        } else {
            $this->line('🌐 Estado: <fg=green>INACTIVO</>');
            $this->line('   El sitio está disponible normalmente');
        }

        $this->line('');

        // Mostrar usuarios permitidos
        $allowedUsers = Cache::get('maintenance_allowed_users', []);
        if (!empty($allowedUsers)) {
            $this->line('👥 Usuarios permitidos:');
            foreach ($allowedUsers as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $this->line("   - {$user->name} ({$user->email}) [ID: {$userId}]");
                } else {
                    $this->line("   - Usuario ID: {$userId} (no encontrado)");
                }
            }
        } else {
            $this->line('👥 Usuarios permitidos: Ninguno');
        }

        $this->line('');

        // Mostrar IPs permitidas
        $allowedIps = Cache::get('maintenance_allowed_ips', []);
        if (!empty($allowedIps)) {
            $this->line('🌐 IPs permitidas:');
            foreach ($allowedIps as $ip) {
                $this->line("   - {$ip}");
            }
        } else {
            $this->line('🌐 IPs permitidas: Ninguna');
        }

        return 0;
    }

    /**
     * Permitir acceso a un usuario
     */
    protected function allowUser(): int
    {
        $userInput = $this->option('user');

        if (!$userInput) {
            $this->error('❌ Debes especificar un usuario con --user=ID o --user=email');
            return 1;
        }

        try {
            // Buscar usuario por ID o email
            $user = is_numeric($userInput)
                ? User::find($userInput)
                : User::where('email', $userInput)->first();

            if (!$user) {
                $this->error('❌ Usuario no encontrado');
                return 1;
            }

            $allowedUsers = Cache::get('maintenance_allowed_users', []);

            if (!in_array($user->id, $allowedUsers)) {
                $allowedUsers[] = $user->id;
                Cache::put('maintenance_allowed_users', $allowedUsers, now()->addDays(30));

                $this->info("✅ Usuario '{$user->name}' ({$user->email}) agregado a la lista de permitidos");
            } else {
                $this->info("ℹ️  Usuario '{$user->name}' ({$user->email}) ya está en la lista de permitidos");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error agregando usuario: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Permitir acceso desde una IP
     */
    protected function allowIp(): int
    {
        $ip = $this->option('ip');

        if (!$ip) {
            $this->error('❌ Debes especificar una IP con --ip=IP_ADDRESS');
            return 1;
        }

        try {
            $allowedIps = Cache::get('maintenance_allowed_ips', []);

            if (!in_array($ip, $allowedIps)) {
                $allowedIps[] = $ip;
                Cache::put('maintenance_allowed_ips', $allowedIps, now()->addDays(30));

                $this->info("✅ IP '{$ip}' agregada a la lista de permitidas");
            } else {
                $this->info("ℹ️  IP '{$ip}' ya está en la lista de permitidas");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error agregando IP: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Remover usuario de la lista de permitidos
     */
    protected function removeUser(): int
    {
        $userInput = $this->option('user');

        if (!$userInput) {
            $this->error('❌ Debes especificar un usuario con --user=ID o --user=email');
            return 1;
        }

        try {
            $allowedUsers = Cache::get('maintenance_allowed_users', []);

            if (is_numeric($userInput)) {
                $userId = (int) $userInput;
                $key = array_search($userId, $allowedUsers);

                if ($key !== false) {
                    unset($allowedUsers[$key]);
                    Cache::put('maintenance_allowed_users', array_values($allowedUsers), now()->addDays(30));
                    $this->info("✅ Usuario ID {$userId} removido de la lista de permitidos");
                } else {
                    $this->info("ℹ️  Usuario ID {$userId} no estaba en la lista de permitidos");
                }
            } else {
                $user = User::where('email', $userInput)->first();

                if ($user) {
                    $key = array_search($user->id, $allowedUsers);

                    if ($key !== false) {
                        unset($allowedUsers[$key]);
                        Cache::put('maintenance_allowed_users', array_values($allowedUsers), now()->addDays(30));
                        $this->info("✅ Usuario '{$user->name}' removido de la lista de permitidos");
                    } else {
                        $this->info("ℹ️  Usuario '{$user->name}' no estaba en la lista de permitidos");
                    }
                } else {
                    $this->error('❌ Usuario no encontrado');
                    return 1;
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error removiendo usuario: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Remover IP de la lista de permitidas
     */
    protected function removeIp(): int
    {
        $ip = $this->option('ip');

        if (!$ip) {
            $this->error('❌ Debes especificar una IP con --ip=IP_ADDRESS');
            return 1;
        }

        try {
            $allowedIps = Cache::get('maintenance_allowed_ips', []);
            $key = array_search($ip, $allowedIps);

            if ($key !== false) {
                unset($allowedIps[$key]);
                Cache::put('maintenance_allowed_ips', array_values($allowedIps), now()->addDays(30));
                $this->info("✅ IP '{$ip}' removida de la lista de permitidas");
            } else {
                $this->info("ℹ️  IP '{$ip}' no estaba en la lista de permitidas");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error removiendo IP: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Limpiar toda la configuración de mantenimiento
     */
    protected function clearAll(): int
    {
        $this->warn('⚠️  Esta acción eliminará toda la configuración de mantenimiento');

        if (!$this->confirm('¿Estás seguro de que quieres continuar?')) {
            $this->info('❌ Operación cancelada');
            return 0;
        }

        try {
            Cache::forget('maintenance_mode');
            Cache::forget('maintenance_retry_after');
            Cache::forget('maintenance_message');
            Cache::forget('maintenance_contact_info');
            Cache::forget('maintenance_allowed_users');
            Cache::forget('maintenance_allowed_ips');

            $this->info('✅ Toda la configuración de mantenimiento ha sido eliminada');
            $this->info('🌐 El sitio está disponible normalmente');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error limpiando configuración: ' . $e->getMessage());
            return 1;
        }
    }
}
