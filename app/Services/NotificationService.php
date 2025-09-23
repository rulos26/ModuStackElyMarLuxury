<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    /**
     * Crear notificación del sistema
     */
    public function createSystemNotification(
        string $title,
        string $message,
        string $type = 'info',
        ?string $icon = null,
        ?string $url = null,
        ?string $actionText = null,
        ?array $data = null,
        ?int $userId = null,
        ?int $expiresInHours = null
    ): Notification {
        $notification = Notification::createSystemNotification(
            $title,
            $message,
            $type,
            $icon,
            $url,
            $actionText,
            $data,
            $userId,
            $expiresInHours
        );

        Log::info('Notificación del sistema creada', [
            'id' => $notification->id,
            'title' => $title,
            'type' => $type,
            'user_id' => $userId
        ]);

        return $notification;
    }

    /**
     * Crear notificación para usuario específico
     */
    public function createForUser(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $icon = null,
        ?string $url = null,
        ?string $actionText = null,
        ?array $data = null,
        ?int $createdBy = null,
        ?int $expiresInHours = null
    ): Notification {
        $notification = Notification::createForUser(
            $userId,
            $title,
            $message,
            $type,
            $icon,
            $url,
            $actionText,
            $data,
            $createdBy,
            $expiresInHours
        );

        Log::info('Notificación para usuario creada', [
            'id' => $notification->id,
            'user_id' => $userId,
            'title' => $title,
            'type' => $type
        ]);

        return $notification;
    }

    /**
     * Crear notificación global
     */
    public function createGlobal(
        string $title,
        string $message,
        string $type = 'info',
        ?string $icon = null,
        ?string $url = null,
        ?string $actionText = null,
        ?array $data = null,
        ?int $createdBy = null,
        ?int $expiresInHours = null
    ): Notification {
        $notification = Notification::createGlobal(
            $title,
            $message,
            $type,
            $icon,
            $url,
            $actionText,
            $data,
            $createdBy,
            $expiresInHours
        );

        Log::info('Notificación global creada', [
            'id' => $notification->id,
            'title' => $title,
            'type' => $type
        ]);

        return $notification;
    }

    /**
     * Obtener notificaciones para un usuario
     */
    public function getForUser(int $userId, int $limit = 10, bool $unreadOnly = false): array
    {
        $query = Notification::forUser($userId)
            ->notExpired()
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::forUser($userId)
            ->find($notificationId);

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    /**
     * Eliminar notificación
     */
    public function delete(int $notificationId, int $userId): bool
    {
        $notification = Notification::forUser($userId)
            ->find($notificationId);

        if (!$notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Eliminar notificaciones expiradas
     */
    public function deleteExpired(): int
    {
        $count = Notification::where('expires_at', '<', now())->count();

        Notification::where('expires_at', '<', now())->delete();

        if ($count > 0) {
            Log::info("Notificaciones expiradas eliminadas", ['count' => $count]);
        }

        return $count;
    }

    /**
     * Obtener estadísticas
     */
    public function getStats(?int $userId = null): array
    {
        $cacheKey = $userId ? "notification_stats_user_{$userId}" : 'notification_stats_global';

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            return Notification::getStats($userId);
        });
    }

    /**
     * Limpiar cache de estadísticas
     */
    public function clearStatsCache(?int $userId = null): void
    {
        $cacheKey = $userId ? "notification_stats_user_{$userId}" : 'notification_stats_global';
        Cache::forget($cacheKey);
    }

    /**
     * Obtener notificaciones pendientes de push
     */
    public function getPendingPush(): array
    {
        return Notification::pendingPush()
            ->notExpired()
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Marcar notificación como enviada por push
     */
    public function markAsPushSent(int $notificationId): bool
    {
        $notification = Notification::find($notificationId);

        if (!$notification) {
            return false;
        }

        return $notification->markAsPushSent();
    }

    /**
     * Crear notificación de bienvenida
     */
    public function createWelcomeNotification(int $userId, string $userName): Notification
    {
        return $this->createForUser(
            $userId,
            '¡Bienvenido a ' . config('app.name') . '!',
            "Hola {$userName}, ¡bienvenido a nuestra plataforma! Esperamos que tengas una excelente experiencia.",
            'success',
            'fas fa-heart',
            route('home'),
            'Comenzar',
            ['user_name' => $userName],
            null,
            24 // Expira en 24 horas
        );
    }

    /**
     * Crear notificación de seguridad
     */
    public function createSecurityNotification(int $userId, string $message): Notification
    {
        return $this->createForUser(
            $userId,
            'Alerta de Seguridad',
            $message,
            'warning',
            'fas fa-shield-alt',
            route('home'),
            'Ver Dashboard',
            ['security_alert' => true],
            null,
            48 // Expira en 48 horas
        );
    }

    /**
     * Crear notificación de sistema
     */
    public function createSystemAlert(string $title, string $message, string $type = 'info'): Notification
    {
        return $this->createGlobal(
            $title,
            $message,
            $type,
            'fas fa-cog',
            null,
            null,
            ['system_alert' => true],
            null,
            72 // Expira en 72 horas
        );
    }

    /**
     * Obtener notificaciones recientes para dashboard
     */
    public function getRecentForDashboard(int $userId, int $limit = 5): array
    {
        $notifications = $this->getForUser($userId, $limit, false);

        return array_map(function ($notification) {
            return [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => substr($notification['message'], 0, 100) . (strlen($notification['message']) > 100 ? '...' : ''),
                'type' => $notification['type'],
                'icon' => $notification['icon'] ?: Notification::find($notification['id'])->default_icon,
                'url' => $notification['url'],
                'action_text' => $notification['action_text'],
                'is_read' => $notification['is_read'],
                'created_at' => $notification['created_at'],
                'time_ago' => \Carbon\Carbon::parse($notification['created_at'])->diffForHumans()
            ];
        }, $notifications);
    }
}
