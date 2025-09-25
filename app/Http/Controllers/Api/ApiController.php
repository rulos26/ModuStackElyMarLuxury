<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\AppSetting;
use App\Services\DynamicDriverService;
use App\Services\BackupService;
use App\Services\EmailService;
use App\Services\NotificationService;

class ApiController extends Controller
{
    protected $dynamicDriverService;
    protected $backupService;
    protected $emailService;
    protected $notificationService;

    public function __construct(
        DynamicDriverService $dynamicDriverService,
        BackupService $backupService,
        EmailService $emailService,
        NotificationService $notificationService
    ) {
        $this->dynamicDriverService = $dynamicDriverService;
        $this->backupService = $backupService;
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
    }

    /**
     * Información general de la API
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'name' => 'ModuStack ElyMar Luxury API',
                'version' => '1.0.0',
                'description' => 'API completa para administración del sistema',
                'endpoints' => [
                    'drivers' => '/api/drivers',
                    'backups' => '/api/backups',
                    'notifications' => '/api/notifications',
                    'settings' => '/api/settings',
                    'users' => '/api/users',
                    'system' => '/api/system'
                ],
                'authentication' => 'Bearer Token',
                'rate_limit' => '100 requests per minute'
            ]
        ]);
    }

    /**
     * Estado del sistema
     */
    public function systemStatus(): JsonResponse
    {
        try {
            $status = [
                'system' => [
                    'status' => 'online',
                    'uptime' => $this->getSystemUptime(),
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak' => memory_get_peak_usage(true),
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version()
                ],
                'drivers' => $this->dynamicDriverService->getAllDriversStatus(),
                'database' => $this->getDatabaseStatus(),
                'cache' => $this->getCacheStatus(),
                'storage' => $this->getStorageStatus()
            ];

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estado del sistema', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado del sistema'
            ], 500);
        }
    }

    /**
     * Configuración de drivers
     */
    public function drivers(Request $request): JsonResponse
    {
        try {
            $action = $request->input('action', 'status');

            switch ($action) {
                case 'status':
                    return $this->getDriversStatus();
                case 'change':
                    return $this->changeDriver($request);
                case 'validate':
                    return $this->validateDriverConfig($request);
                case 'restore':
                    return $this->restoreDriver($request);
                case 'restart':
                    return $this->restartServices($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Acción no válida'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en API de drivers', [
                'action' => $request->input('action'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Gestión de respaldos
     */
    public function backups(Request $request): JsonResponse
    {
        try {
            $action = $request->input('action', 'list');

            switch ($action) {
                case 'list':
                    return $this->listBackups();
                case 'create':
                    return $this->createBackup($request);
                case 'restore':
                    return $this->restoreBackup($request);
                case 'delete':
                    return $this->deleteBackup($request);
                case 'download':
                    return $this->downloadBackup($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Acción no válida'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en API de respaldos', [
                'action' => $request->input('action'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Gestión de notificaciones
     */
    public function notifications(Request $request): JsonResponse
    {
        try {
            $action = $request->input('action', 'list');

            switch ($action) {
                case 'list':
                    return $this->listNotifications($request);
                case 'send':
                    return $this->sendNotification($request);
                case 'mark_read':
                    return $this->markAsRead($request);
                case 'delete':
                    return $this->deleteNotification($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Acción no válida'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en API de notificaciones', [
                'action' => $request->input('action'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Configuración del sistema
     */
    public function settings(Request $request): JsonResponse
    {
        try {
            $action = $request->input('action', 'get');

            switch ($action) {
                case 'get':
                    return $this->getSettings($request);
                case 'update':
                    return $this->updateSettings($request);
                case 'reset':
                    return $this->resetSettings($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Acción no válida'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en API de configuración', [
                'action' => $request->input('action'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Gestión de usuarios
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $action = $request->input('action', 'list');

            switch ($action) {
                case 'list':
                    return $this->listUsers($request);
                case 'create':
                    return $this->createUser($request);
                case 'update':
                    return $this->updateUser($request);
                case 'delete':
                    return $this->deleteUser($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Acción no válida'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en API de usuarios', [
                'action' => $request->input('action'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    // Métodos privados para drivers
    private function getDriversStatus(): JsonResponse
    {
        $status = $this->dynamicDriverService->getAllDriversStatus();

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    private function changeDriver(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|in:cache,session,queue,mail,database',
            'driver' => 'required|string',
            'config' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = $request->input('service');
        $driver = $request->input('driver');
        $config = $request->input('config', []);

        $result = $this->dynamicDriverService->changeDriver($service, $driver, $config);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => "Driver cambiado exitosamente a {$driver} para {$service}"
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el driver'
            ], 500);
        }
    }

    private function validateDriverConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|in:cache,session,queue,mail,database',
            'driver' => 'required|string',
            'config' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = $request->input('service');
        $driver = $request->input('driver');
        $config = $request->input('config');

        $errors = $this->dynamicDriverService->validateDriverConfig($service, $driver, $config);

        return response()->json([
            'success' => true,
            'valid' => empty($errors),
            'errors' => $errors
        ]);
    }

    private function restoreDriver(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|in:cache,session,queue,mail,database'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $service = $request->input('service');
        $result = $this->dynamicDriverService->restoreDriverConfig($service);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => "Configuración restaurada para {$service}"
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró configuración guardada'
            ], 404);
        }
    }

    private function restartServices(Request $request): JsonResponse
    {
        $services = $request->input('services', []);
        $result = $this->dynamicDriverService->restartServices($services);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Servicios reiniciados exitosamente'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar servicios'
            ], 500);
        }
    }

    // Métodos privados para respaldos
    private function listBackups(): JsonResponse
    {
        $backups = $this->backupService->getAllBackups();

        return response()->json([
            'success' => true,
            'data' => $backups
        ]);
    }

    private function createBackup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $name = $request->input('name', 'Backup ' . now()->format('Y-m-d H:i:s'));
        $description = $request->input('description', '');

        $result = $this->backupService->createBackup($name, $description);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Respaldo creado exitosamente',
                'data' => $result
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear respaldo'
            ], 500);
        }
    }

    private function restoreBackup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'backup_id' => 'required|integer|exists:backups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $backupId = $request->input('backup_id');
        $result = $this->backupService->restoreBackup($backupId);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Respaldo restaurado exitosamente'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar respaldo'
            ], 500);
        }
    }

    private function deleteBackup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'backup_id' => 'required|integer|exists:backups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $backupId = $request->input('backup_id');
        $result = $this->backupService->deleteBackup($backupId);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Respaldo eliminado exitosamente'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar respaldo'
            ], 500);
        }
    }

    private function downloadBackup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'backup_id' => 'required|integer|exists:backups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $backupId = $request->input('backup_id');
        $backup = $this->backupService->getBackup($backupId);

        if ($backup) {
            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => route('backups.download', $backupId),
                    'filename' => $backup->filename,
                    'size' => $backup->size
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Respaldo no encontrado'
            ], 404);
        }
    }

    // Métodos privados para notificaciones
    private function listNotifications(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $notifications = $this->notificationService->getAllNotifications($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    private function sendNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|string|in:info,warning,error,success',
            'user_id' => 'sometimes|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $title = $request->input('title');
        $message = $request->input('message');
        $type = $request->input('type');
        $userId = $request->input('user_id');

        $result = $this->notificationService->createNotification($title, $message, $type, $userId);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación enviada exitosamente',
                'data' => $result
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar notificación'
            ], 500);
        }
    }

    private function markAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|integer|exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $notificationId = $request->input('notification_id');
        $result = $this->notificationService->markAsRead($notificationId);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación'
            ], 500);
        }
    }

    private function deleteNotification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|integer|exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $notificationId = $request->input('notification_id');
        $result = $this->notificationService->deleteNotification($notificationId);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada exitosamente'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar notificación'
            ], 500);
        }
    }

    // Métodos privados para configuración
    private function getSettings(Request $request): JsonResponse
    {
        $category = $request->input('category', 'all');
        $settings = AppSetting::when($category !== 'all', function ($query) use ($category) {
            return $query->where('category', $category);
        })->get();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    private function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $settings = $request->input('settings');
        $updated = [];

        foreach ($settings as $setting) {
            $appSetting = AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
            $updated[] = $appSetting;
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada exitosamente',
            'data' => $updated
        ]);
    }

    private function resetSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = $request->input('category');
        $deleted = AppSetting::where('category', $category)->delete();

        return response()->json([
            'success' => true,
            'message' => "Configuración de {$category} restablecida",
            'deleted_count' => $deleted
        ]);
    }

    // Métodos privados para usuarios
    private function listUsers(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $users = User::paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    private function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password'))
        ]);

        if ($request->has('role')) {
            $user->assignRole($request->input('role'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => $user
        ]);
    }

    private function updateUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255',
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);

        $updateData = $request->only(['name', 'email']);
        if ($request->has('password')) {
            $updateData['password'] = bcrypt($request->input('password'));
        }

        $user->update($updateData);

        if ($request->has('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => $user
        ]);
    }

    private function deleteUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $user = User::findOrFail($userId);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }

    // Métodos auxiliares
    private function getSystemUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = shell_exec('uptime');
            return trim($uptime) ?: 'No disponible';
        }
        return 'No disponible';
    }

    private function getDatabaseStatus(): array
    {
        try {
            \DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'driver' => \DB::connection()->getDriverName()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'disconnected',
                'error' => $e->getMessage()
            ];
        }
    }

    private function getCacheStatus(): array
    {
        try {
            \Cache::put('test_key', 'test_value', 1);
            $value = \Cache::get('test_key');
            \Cache::forget('test_key');

            return [
                'status' => $value === 'test_value' ? 'working' : 'error',
                'driver' => config('cache.default')
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function getStorageStatus(): array
    {
        $disk = \Storage::disk('local');

        return [
            'status' => $disk->exists('.') ? 'accessible' : 'error',
            'free_space' => disk_free_space(storage_path()),
            'total_space' => disk_total_space(storage_path())
        ];
    }
}

