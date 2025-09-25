<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Services\NotificationService;
use App\Services\EmailService;
use App\Models\User;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationType;
    protected $notificationData;
    protected $priority;
    protected $channels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $notificationType,
        array $notificationData,
        int $priority = 3,
        array $channels = ['database']
    ) {
        $this->notificationType = $notificationType;
        $this->notificationData = $notificationData;
        $this->priority = $priority;
        $this->channels = $channels;

        // Establecer cola según prioridad
        $this->onQueue($this->getQueueName());
    }

    /**
     * Execute the job.
     */
    public function handle(
        NotificationService $notificationService,
        EmailService $emailService
    ): void {
        $startTime = microtime(true);

        try {
            Log::info("Iniciando job de notificación", [
                'type' => $this->notificationType,
                'data' => $this->notificationData,
                'channels' => $this->channels,
                'priority' => $this->priority
            ]);

            // Procesar notificación según el tipo
            switch ($this->notificationType) {
                case 'system_alert':
                    $this->sendSystemAlert($notificationService);
                    break;
                case 'user_notification':
                    $this->sendUserNotification($notificationService);
                    break;
                case 'email_bulk':
                    $this->sendBulkEmail($emailService);
                    break;
                case 'email_single':
                    $this->sendSingleEmail($emailService);
                    break;
                case 'push_notification':
                    $this->sendPushNotification($notificationService);
                    break;
                case 'sms_notification':
                    $this->sendSmsNotification($notificationService);
                    break;
                case 'maintenance_alert':
                    $this->sendMaintenanceAlert($notificationService);
                    break;
                case 'security_alert':
                    $this->sendSecurityAlert($notificationService);
                    break;
                default:
                    $this->sendGenericNotification($notificationService);
            }

            $executionTime = microtime(true) - $startTime;

            Log::info("Job de notificación completado exitosamente", [
                'type' => $this->notificationType,
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true)
            ]);

        } catch (\Exception $e) {
            Log::error("Error en job de notificación", [
                'type' => $this->notificationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Enviar alerta del sistema
     */
    protected function sendSystemAlert(NotificationService $notificationService): void
    {
        Log::info('Enviando alerta del sistema');

        $title = $this->notificationData['title'] ?? 'Alerta del Sistema';
        $message = $this->notificationData['message'] ?? 'Mensaje de alerta del sistema';
        $level = $this->notificationData['level'] ?? 'warning';

        // Crear notificación en base de datos
        $notificationService->createNotification($title, $message, $level);

        // Enviar por email si es crítico
        if ($level === 'critical' || $level === 'emergency') {
            $this->sendCriticalEmail($title, $message);
        }

        Log::info('Alerta del sistema enviada', [
            'title' => $title,
            'level' => $level
        ]);
    }

    /**
     * Enviar notificación a usuario
     */
    protected function sendUserNotification(NotificationService $notificationService): void
    {
        Log::info('Enviando notificación a usuario');

        $userId = $this->notificationData['user_id'] ?? null;
        $title = $this->notificationData['title'] ?? 'Notificación';
        $message = $this->notificationData['message'] ?? 'Mensaje de notificación';
        $type = $this->notificationData['type'] ?? 'info';

        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                // Crear notificación para el usuario
                $notificationService->createUserNotification($user, $title, $message, $type);

                // Enviar por canales configurados
                $this->sendToChannels($user, $title, $message, $type);
            }
        }

        Log::info('Notificación de usuario enviada', [
            'user_id' => $userId,
            'title' => $title
        ]);
    }

    /**
     * Enviar email masivo
     */
    protected function sendBulkEmail(EmailService $emailService): void
    {
        Log::info('Enviando email masivo');

        $recipients = $this->notificationData['recipients'] ?? [];
        $subject = $this->notificationData['subject'] ?? 'Notificación Masiva';
        $template = $this->notificationData['template'] ?? 'emails.notification';
        $data = $this->notificationData['data'] ?? [];

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            try {
                $emailService->sendEmail(
                    $recipient['email'],
                    $subject,
                    $template,
                    array_merge($data, ['recipient' => $recipient])
                );
                $sentCount++;

            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Error al enviar email masivo', [
                    'email' => $recipient['email'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Email masivo completado', [
            'total' => count($recipients),
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);
    }

    /**
     * Enviar email individual
     */
    protected function sendSingleEmail(EmailService $emailService): void
    {
        Log::info('Enviando email individual');

        $email = $this->notificationData['email'] ?? null;
        $subject = $this->notificationData['subject'] ?? 'Notificación';
        $template = $this->notificationData['template'] ?? 'emails.notification';
        $data = $this->notificationData['data'] ?? [];

        if ($email) {
            $emailService->sendEmail($email, $subject, $template, $data);

            Log::info('Email individual enviado', [
                'email' => $email,
                'subject' => $subject
            ]);
        }
    }

    /**
     * Enviar notificación push
     */
    protected function sendPushNotification(NotificationService $notificationService): void
    {
        Log::info('Enviando notificación push');

        $title = $this->notificationData['title'] ?? 'Notificación';
        $message = $this->notificationData['message'] ?? 'Mensaje de notificación';
        $tokens = $this->notificationData['tokens'] ?? [];

        foreach ($tokens as $token) {
            try {
                // Enviar notificación push
                $notificationService->sendPushNotification($token, $title, $message);

            } catch (\Exception $e) {
                Log::error('Error al enviar notificación push', [
                    'token' => $token,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Notificaciones push enviadas', [
            'count' => count($tokens)
        ]);
    }

    /**
     * Enviar notificación SMS
     */
    protected function sendSmsNotification(NotificationService $notificationService): void
    {
        Log::info('Enviando notificación SMS');

        $phone = $this->notificationData['phone'] ?? null;
        $message = $this->notificationData['message'] ?? 'Mensaje SMS';

        if ($phone) {
            $notificationService->sendSms($phone, $message);

            Log::info('SMS enviado', [
                'phone' => $phone
            ]);
        }
    }

    /**
     * Enviar alerta de mantenimiento
     */
    protected function sendMaintenanceAlert(NotificationService $notificationService): void
    {
        Log::info('Enviando alerta de mantenimiento');

        $title = 'Mantenimiento Programado';
        $message = $this->notificationData['message'] ?? 'Se realizará mantenimiento del sistema';
        $scheduledTime = $this->notificationData['scheduled_time'] ?? now()->addHours(1);

        // Crear notificación de mantenimiento
        $notificationService->createNotification($title, $message, 'warning');

        // Enviar email a administradores
        $this->sendMaintenanceEmail($title, $message, $scheduledTime);

        Log::info('Alerta de mantenimiento enviada', [
            'scheduled_time' => $scheduledTime
        ]);
    }

    /**
     * Enviar alerta de seguridad
     */
    protected function sendSecurityAlert(NotificationService $notificationService): void
    {
        Log::info('Enviando alerta de seguridad');

        $title = 'Alerta de Seguridad';
        $message = $this->notificationData['message'] ?? 'Se ha detectado una amenaza de seguridad';
        $threatLevel = $this->notificationData['threat_level'] ?? 'medium';

        // Crear notificación de seguridad
        $notificationService->createNotification($title, $message, 'error');

        // Enviar email crítico
        $this->sendSecurityEmail($title, $message, $threatLevel);

        Log::info('Alerta de seguridad enviada', [
            'threat_level' => $threatLevel
        ]);
    }

    /**
     * Enviar notificación genérica
     */
    protected function sendGenericNotification(NotificationService $notificationService): void
    {
        Log::info('Enviando notificación genérica');

        $title = $this->notificationData['title'] ?? 'Notificación';
        $message = $this->notificationData['message'] ?? 'Mensaje de notificación';
        $type = $this->notificationData['type'] ?? 'info';

        $notificationService->createNotification($title, $message, $type);

        Log::info('Notificación genérica enviada', [
            'title' => $title,
            'type' => $type
        ]);
    }

    /**
     * Enviar email crítico
     */
    protected function sendCriticalEmail(string $title, string $message): void
    {
        try {
            Mail::raw($message, function ($mail) use ($title) {
                $mail->to(config('mail.admin_email', 'admin@example.com'))
                     ->subject('CRÍTICO: ' . $title);
            });

        } catch (\Exception $e) {
            Log::error('Error al enviar email crítico', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar email de mantenimiento
     */
    protected function sendMaintenanceEmail(string $title, string $message, $scheduledTime): void
    {
        try {
            Mail::raw($message . "\n\nProgramado para: " . $scheduledTime, function ($mail) use ($title) {
                $mail->to(config('mail.admin_email', 'admin@example.com'))
                     ->subject('MANTENIMIENTO: ' . $title);
            });

        } catch (\Exception $e) {
            Log::error('Error al enviar email de mantenimiento', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar email de seguridad
     */
    protected function sendSecurityEmail(string $title, string $message, string $threatLevel): void
    {
        try {
            Mail::raw($message . "\n\nNivel de amenaza: " . strtoupper($threatLevel), function ($mail) use ($title) {
                $mail->to(config('mail.admin_email', 'admin@example.com'))
                     ->subject('SEGURIDAD: ' . $title);
            });

        } catch (\Exception $e) {
            Log::error('Error al enviar email de seguridad', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar a canales configurados
     */
    protected function sendToChannels(User $user, string $title, string $message, string $type): void
    {
        foreach ($this->channels as $channel) {
            try {
                switch ($channel) {
                    case 'database':
                        // Ya se creó en la base de datos
                        break;
                    case 'email':
                        $this->sendUserEmail($user, $title, $message);
                        break;
                    case 'push':
                        $this->sendUserPush($user, $title, $message);
                        break;
                    case 'sms':
                        $this->sendUserSms($user, $title, $message);
                        break;
                }

            } catch (\Exception $e) {
                Log::error('Error al enviar notificación por canal', [
                    'channel' => $channel,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Enviar email al usuario
     */
    protected function sendUserEmail(User $user, string $title, string $message): void
    {
        try {
            Mail::raw($message, function ($mail) use ($user, $title) {
                $mail->to($user->email)
                     ->subject($title);
            });

        } catch (\Exception $e) {
            Log::error('Error al enviar email al usuario', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar push al usuario
     */
    protected function sendUserPush(User $user, string $title, string $message): void
    {
        // Implementar lógica de push notification
        Log::info('Push notification enviada al usuario', [
            'user_id' => $user->id,
            'title' => $title
        ]);
    }

    /**
     * Enviar SMS al usuario
     */
    protected function sendUserSms(User $user, string $title, string $message): void
    {
        // Implementar lógica de SMS
        Log::info('SMS enviado al usuario', [
            'user_id' => $user->id,
            'title' => $title
        ]);
    }

    /**
     * Obtener nombre de la cola según prioridad
     */
    protected function getQueueName(): string
    {
        if ($this->priority <= 2) {
            return 'high';
        } elseif ($this->priority <= 4) {
            return 'normal';
        } else {
            return 'low';
        }
    }

    /**
     * Determinar si el job debe fallar
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de notificación falló permanentemente", [
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
            'data' => $this->notificationData
        ]);
    }
}

