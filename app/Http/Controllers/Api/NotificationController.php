<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Obtener notificaciones del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 10);
        $unreadOnly = $request->boolean('unread_only', false);

        $notifications = $this->notificationService->getForUser($userId, $limit, $unreadOnly);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'stats' => $this->notificationService->getStats($userId)
        ]);
    }

    /**
     * Obtener notificaciones recientes para dashboard
     */
    public function recent(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 5);

        $notifications = $this->notificationService->getRecentForDashboard($userId, $limit);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $userId = Auth::id();

        $success = $this->notificationService->markAsRead($id, $userId);

        if ($success) {
            $this->notificationService->clearStatsCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notificación no encontrada'
        ], 404);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(): JsonResponse
    {
        $userId = Auth::id();

        $count = $this->notificationService->markAllAsRead($userId);
        $this->notificationService->clearStatsCache($userId);

        return response()->json([
            'success' => true,
            'message' => "{$count} notificaciones marcadas como leídas",
            'count' => $count
        ]);
    }

    /**
     * Eliminar notificación
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = Auth::id();

        $success = $this->notificationService->delete($id, $userId);

        if ($success) {
            $this->notificationService->clearStatsCache($userId);

            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notificación no encontrada'
        ], 404);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function stats(): JsonResponse
    {
        $userId = Auth::id();
        $stats = $this->notificationService->getStats($userId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Crear notificación (solo para administradores)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error',
            'icon' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'action_text' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'expires_in_hours' => 'nullable|integer|min:1|max:168' // Máximo 7 días
        ]);

        $userId = Auth::id();
        $targetUserId = $request->get('user_id');
        $expiresInHours = $request->get('expires_in_hours');

        if ($targetUserId) {
            $notification = $this->notificationService->createForUser(
                $targetUserId,
                $request->title,
                $request->message,
                $request->type,
                $request->icon,
                $request->url,
                $request->action_text,
                null,
                $userId,
                $expiresInHours
            );
        } else {
            $notification = $this->notificationService->createGlobal(
                $request->title,
                $request->message,
                $request->type,
                $request->icon,
                $request->url,
                $request->action_text,
                null,
                $userId,
                $expiresInHours
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificación creada exitosamente',
            'data' => $notification
        ], 201);
    }
}
