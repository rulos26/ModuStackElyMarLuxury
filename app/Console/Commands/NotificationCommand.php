<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\JobService;
use App\Services\NotificationService;
use App\Models\User;

class NotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:manage
                            {action : Acción a realizar (send|list|clear|test|schedule)}
                            {--type=info : Tipo de notificación (info|warning|error|success)}
                            {--title= : Título de la notificación}
                            {--message= : Mensaje de la notificación}
                            {--user= : ID del usuario (opcional, si no se especifica se envía a todos)}
                            {--channels=database : Canales de envío (database,email,push,sms)}
                            {--priority=3 : Prioridad (1-5)}
                            {--schedule= : Programar notificación (formato: Y-m-d H:i:s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar notificaciones del sistema';

    protected $jobService;
    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        JobService $jobService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->jobService = $jobService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $type = $this->option('type');
        $title = $this->option('title');
        $message = $this->option('message');
        $user = $this->option('user');
        $channels = explode(',', $this->option('channels'));
        $priority = (int) $this->option('priority');
        $schedule = $this->option('schedule');

        switch ($action) {
            case 'send':
                return $this->sendNotification($type, $title, $message, $user, $channels, $priority);
            case 'list':
                return $this->listNotifications();
            case 'clear':
                return $this->clearNotifications();
            case 'test':
                return $this->testNotification($type, $title, $message, $channels);
            case 'schedule':
                return $this->scheduleNotification($type, $title, $message, $user, $channels, $priority, $schedule);
            default:
                $this->error('Acción no válida. Use: send, list, clear, test, schedule');
                return 1;
        }
    }

    /**
     * Enviar notificación
     */
    protected function sendNotification(string $type, ?string $title, ?string $message, ?string $user, array $channels, int $priority): int
    {
        if (!$title || !$message) {
            $this->error('❌ Debe especificar --title y --message');
            return 1;
        }

        $this->info("📧 Enviando notificación: {$title}");

        try {
            $notificationData = [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'channels' => $channels,
                'priority' => $priority,
                'created_by' => 'artisan_command'
            ];

            if ($user) {
                $notificationData['user_id'] = $user;
                $notificationData['notification_type'] = 'user_notification';
            } else {
                $notificationData['notification_type'] = 'system_alert';
            }

            // Despachar job de notificación
            $this->jobService->dispatchNotificationJob(
                $notificationData['notification_type'],
                $notificationData,
                $priority,
                $channels
            );

            $this->info('✅ Notificación programada exitosamente');
            $this->line("Título: {$title}");
            $this->line("Tipo: {$type}");
            $this->line("Canales: " . implode(', ', $channels));
            $this->line("Prioridad: {$priority}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al enviar notificación: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Listar notificaciones
     */
    protected function listNotifications(): int
    {
        $this->info('📋 LISTA DE NOTIFICACIONES');
        $this->line('==========================');

        try {
            // Simular notificaciones para testing
            $notifications = [];

            if (empty($notifications)) {
                $this->warn('⚠️  No hay notificaciones disponibles');
                return 0;
            }

            $this->table(
                ['ID', 'Título', 'Tipo', 'Usuario', 'Estado', 'Fecha'],
                array_map(function ($notification) {
                    return [
                        $notification['id'] ?? 'N/A',
                        $this->truncateString($notification['title'] ?? 'Sin título', 30),
                        $notification['type'] ?? 'unknown',
                        $notification['user_id'] ?? 'Sistema',
                        $notification['read_at'] ? 'Leída' : 'No leída',
                        $notification['created_at'] ?? 'N/A'
                    ];
                }, array_slice($notifications, 0, 20)) // Mostrar solo las primeras 20
            );

            $this->line('');
            $this->info("Total de notificaciones: " . count($notifications));

            // Mostrar estadísticas
            $this->showNotificationStats($notifications);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al listar notificaciones: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Limpiar notificaciones
     */
    protected function clearNotifications(): int
    {
        $this->info('🗑️  Limpiando notificaciones...');

        try {
            // Confirmar limpieza
            if (!$this->confirm('¿Está seguro de que desea limpiar todas las notificaciones?')) {
                $this->info('❌ Limpieza cancelada');
                return 0;
            }

            // Despachar job de limpieza
            $this->jobService->dispatchCleanupJob('notifications', [
                'clear_all' => true,
                'cleared_by' => 'artisan_command'
            ], 30);

            $this->info('✅ Limpieza de notificaciones programada');
            $this->warn('⚠️  La limpieza se ejecutará en segundo plano');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al limpiar notificaciones: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Probar notificación
     */
    protected function testNotification(string $type, ?string $title, ?string $message, array $channels): int
    {
        $this->info('🧪 Probando notificación...');

        $testTitle = $title ?: 'Notificación de Prueba';
        $testMessage = $message ?: 'Esta es una notificación de prueba del sistema';

        try {
            $notificationData = [
                'title' => $testTitle,
                'message' => $testMessage,
                'type' => $type,
                'channels' => $channels,
                'priority' => 1,
                'test' => true,
                'created_by' => 'artisan_command'
            ];

            // Despachar job de notificación de prueba
            $this->jobService->dispatchNotificationJob(
                'system_alert',
                $notificationData,
                1,
                $channels
            );

            $this->info('✅ Notificación de prueba programada');
            $this->line("Título: {$testTitle}");
            $this->line("Mensaje: {$testMessage}");
            $this->line("Tipo: {$type}");
            $this->line("Canales: " . implode(', ', $channels));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al probar notificación: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Programar notificación
     */
    protected function scheduleNotification(string $type, ?string $title, ?string $message, ?string $user, array $channels, int $priority, ?string $schedule): int
    {
        if (!$title || !$message || !$schedule) {
            $this->error('❌ Debe especificar --title, --message y --schedule');
            return 1;
        }

        $this->info("📅 Programando notificación para: {$schedule}");

        try {
            $scheduledData = [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'user_id' => $user,
                'channels' => $channels,
                'priority' => $priority,
                'scheduled_at' => $schedule,
                'created_by' => 'artisan_command'
            ];

            // Guardar programación
            $schedules = \Cache::get('notification_schedules', []);
            $schedules[] = $scheduledData;
            \Cache::put('notification_schedules', $schedules, 86400 * 30); // 30 días

            // Despachar job de programación
            $this->jobService->dispatchSystemJob('notification_schedule', $scheduledData, 3);

            $this->info('✅ Notificación programada exitosamente');
            $this->line("Título: {$title}");
            $this->line("Programada para: {$schedule}");
            $this->line("Canales: " . implode(', ', $channels));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al programar notificación: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Mostrar estadísticas de notificaciones
     */
    protected function showNotificationStats(array $notifications): void
    {
        $this->line('');
        $this->info('📊 ESTADÍSTICAS DE NOTIFICACIONES');
        $this->line('==================================');

        $total = count($notifications);
        $unread = count(array_filter($notifications, function($n) {
            return $n['read_at'] === null;
        }));
        $read = $total - $unread;

        $this->line("Total: {$total}");
        $this->line("Leídas: {$read}");
        $this->line("No leídas: {$unread}");

        // Estadísticas por tipo
        $types = [];
        foreach ($notifications as $notification) {
            $type = $notification['type'] ?? 'unknown';
            $types[$type] = ($types[$type] ?? 0) + 1;
        }

        $this->line('');
        $this->line('Por tipo:');
        foreach ($types as $type => $count) {
            $this->line("  {$type}: {$count}");
        }
    }

    /**
     * Truncar string
     */
    protected function truncateString(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length - 3) . '...';
    }
}
