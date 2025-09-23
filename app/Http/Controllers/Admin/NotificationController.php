<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
    }

    /**
     * Mostrar lista de notificaciones
     */
    public function index(Request $request)
    {
        $query = Notification::with(['user', 'creator'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        if ($request->boolean('expired_only')) {
            $query->where('expires_at', '<', now());
        }

        $notifications = $query->paginate(20);
        $users = User::select('id', 'name', 'email')->get();
        $stats = $this->notificationService->getStats();

        return view('admin.notifications.index', compact(
            'notifications',
            'users',
            'stats'
        ));
    }

    /**
     * Mostrar formulario para crear notificación
     */
    public function create()
    {
        $users = User::select('id', 'name', 'email')->get();

        return view('admin.notifications.create', compact('users'));
    }

    /**
     * Crear nueva notificación
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error',
            'icon' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'action_text' => 'nullable|string|max:255',
            'target_type' => 'required|in:specific,global',
            'user_id' => 'required_if:target_type,specific|exists:users,id',
            'expires_in_hours' => 'nullable|integer|min:1|max:168'
        ]);

        $userId = auth()->id();
        $targetUserId = $request->target_type === 'specific' ? $request->user_id : null;

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
                $request->expires_in_hours
            );

            $message = 'Notificación enviada al usuario exitosamente';
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
                $request->expires_in_hours
            );

            $message = 'Notificación global creada exitosamente';
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', $message);
    }

    /**
     * Mostrar notificación específica
     */
    public function show(Notification $notification)
    {
        $notification->load(['user', 'creator']);

        return view('admin.notifications.show', compact('notification'));
    }

    /**
     * Eliminar notificación
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notificación eliminada exitosamente');
    }

    /**
     * Marcar como leída
     */
    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }

    /**
     * Eliminar notificaciones expiradas
     */
    public function deleteExpired()
    {
        $count = $this->notificationService->deleteExpired();

        return redirect()->route('admin.notifications.index')
            ->with('success', "{$count} notificaciones expiradas eliminadas");
    }

    /**
     * Obtener estadísticas
     */
    public function stats()
    {
        $stats = $this->notificationService->getStats();
        $recentStats = [
            'last_24h' => Notification::where('created_at', '>=', now()->subDay())->count(),
            'last_week' => Notification::where('created_at', '>=', now()->subWeek())->count(),
            'last_month' => Notification::where('created_at', '>=', now()->subMonth())->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge($stats, $recentStats)
        ]);
    }

    /**
     * Enviar notificación de bienvenida
     */
    public function sendWelcome(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::find($request->user_id);
        $notification = $this->notificationService->createWelcomeNotification(
            $user->id,
            $user->name
        );

        return response()->json([
            'success' => true,
            'message' => 'Notificación de bienvenida enviada',
            'data' => $notification
        ]);
    }

    /**
     * Enviar alerta de seguridad
     */
    public function sendSecurityAlert(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string'
        ]);

        $notification = $this->notificationService->createSecurityNotification(
            $request->user_id,
            $request->message
        );

        return response()->json([
            'success' => true,
            'message' => 'Alerta de seguridad enviada',
            'data' => $notification
        ]);
    }

    /**
     * Enviar notificación del sistema
     */
    public function sendSystemAlert(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:info,success,warning,error'
        ]);

        $notification = $this->notificationService->createSystemAlert(
            $request->title,
            $request->message,
            $request->type
        );

        return response()->json([
            'success' => true,
            'message' => 'Notificación del sistema enviada',
            'data' => $notification
        ]);
    }
}
