<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\User;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test {--user-id= : ID del usuario para probar} {--global : Crear notificación global}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar sistema de notificaciones push básicas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔔 Probando Sistema de Notificaciones Push Básicas');
        $this->line('');

        try {
            $notificationService = app(NotificationService::class);

            // Probar notificación global
            if ($this->option('global')) {
                $this->testGlobalNotification($notificationService);
            }

            // Probar notificación para usuario específico
            $userId = $this->option('user-id');
            if ($userId) {
                $this->testUserNotification($notificationService, $userId);
            } else {
                // Usar el primer usuario disponible
                $user = User::first();
                if ($user) {
                    $this->testUserNotification($notificationService, $user->id);
                }
            }

            // Probar diferentes tipos de notificaciones
            $this->testDifferentTypes($notificationService);

            // Mostrar estadísticas
            $this->showStats($notificationService);

            $this->line('');
            $this->info('✅ ¡Sistema de notificaciones probado exitosamente!');

        } catch (\Exception $e) {
            $this->error('❌ Error probando notificaciones: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Probar notificación global
     */
    protected function testGlobalNotification(NotificationService $service)
    {
        $this->info('🌍 Probando notificación global...');

        $notification = $service->createGlobal(
            'Sistema de Notificaciones Probado',
            'El sistema de notificaciones push básicas está funcionando correctamente. Esta es una notificación de prueba.',
            'success',
            'fas fa-check-circle',
            route('home'),
            'Ver Dashboard',
            ['test' => true, 'timestamp' => now()],
            null,
            24
        );

        $this->line("   ✅ Notificación global creada: ID {$notification->id}");
    }

    /**
     * Probar notificación para usuario específico
     */
    protected function testUserNotification(NotificationService $service, int $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            $this->error("   ❌ Usuario con ID {$userId} no encontrado");
            return;
        }

        $this->info("👤 Probando notificación para usuario: {$user->name}");

        // Notificación de bienvenida
        $welcomeNotification = $service->createWelcomeNotification($user->id, $user->name);
        $this->line("   ✅ Notificación de bienvenida creada: ID {$welcomeNotification->id}");

        // Notificación de seguridad
        $securityNotification = $service->createSecurityNotification(
            $user->id,
            'Esta es una alerta de seguridad de prueba. Tu cuenta está segura.'
        );
        $this->line("   ✅ Alerta de seguridad creada: ID {$securityNotification->id}");

        // Notificación personalizada
        $customNotification = $service->createForUser(
            $user->id,
            'Notificación de Prueba',
            'Esta es una notificación personalizada para probar el sistema.',
            'info',
            'fas fa-bell',
            route('home'),
            'Ir al Dashboard',
            ['test_type' => 'custom'],
            null,
            12
        );
        $this->line("   ✅ Notificación personalizada creada: ID {$customNotification->id}");
    }

    /**
     * Probar diferentes tipos de notificaciones
     */
    protected function testDifferentTypes(NotificationService $service)
    {
        $this->info('🎨 Probando diferentes tipos de notificaciones...');

        $types = [
            'info' => ['fas fa-info-circle', 'Información del Sistema'],
            'success' => ['fas fa-check-circle', 'Operación Exitosa'],
            'warning' => ['fas fa-exclamation-triangle', 'Advertencia Importante'],
            'error' => ['fas fa-times-circle', 'Error del Sistema']
        ];

        $user = User::first();

        if ($user) {
            foreach ($types as $type => $config) {
                [$icon, $title] = $config;

                $notification = $service->createForUser(
                    $user->id,
                    $title,
                    "Esta es una notificación de tipo {$type} para probar el sistema.",
                    $type,
                    $icon,
                    null,
                    null,
                    ['test_type' => $type],
                    null,
                    6
                );

                $this->line("   ✅ Notificación {$type} creada: ID {$notification->id}");
            }
        }
    }

    /**
     * Mostrar estadísticas
     */
    protected function showStats(NotificationService $service)
    {
        $this->line('');
        $this->info('📊 Estadísticas del Sistema:');

        $stats = $service->getStats();

        $this->line("   Total de notificaciones: {$stats['total']}");
        $this->line("   No leídas: {$stats['unread']}");
        $this->line("   Leídas: {$stats['read']}");
        $this->line("   Pendientes de push: {$stats['pending_push']}");
        $this->line("   Expiradas: {$stats['expired']}");

        if (!empty($stats['by_type'])) {
            $this->line('');
            $this->line('   Por tipo:');
            foreach ($stats['by_type'] as $type => $count) {
                $this->line("     {$type}: {$count}");
            }
        }

        // Limpiar cache
        $service->clearStatsCache();
        $this->line('');
        $this->info('   Cache de estadísticas limpiado');
    }
}
