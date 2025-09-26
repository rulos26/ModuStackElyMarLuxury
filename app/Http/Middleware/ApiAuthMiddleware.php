<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Verificar si el request tiene token de autenticación
            $token = $this->getTokenFromRequest($request);

            if (!$token) {
                return $this->unauthorizedResponse('Token de autenticación requerido');
            }

            // Verificar el token
            $user = $this->validateToken($token);

            if (!$user) {
                return $this->unauthorizedResponse('Token inválido o expirado');
            }

            // Autenticar al usuario
            Auth::setUser($user);

            // Agregar información del usuario a la request
            $request->merge(['api_user' => $user]);

            // Log de acceso
            Log::info('Acceso API autorizado', [
                'user_id' => $user->id,
                'email' => $user->email,
                'endpoint' => $request->path(),
                'ip' => $request->ip()
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Error en autenticación API', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'endpoint' => $request->path()
            ]);

            return $this->serverErrorResponse('Error interno de autenticación');
        }
    }

    /**
     * Obtiene el token del request
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Buscar token en el header Authorization
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Buscar token en el header X-API-Key
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return $apiKey;
        }

        // Buscar token en el parámetro api_token
        $apiToken = $request->input('api_token');
        if ($apiToken) {
            return $apiToken;
        }

        return null;
    }

    /**
     * Valida el token y retorna el usuario
     */
    protected function validateToken(string $token): ?User
    {
        try {
            // Verificar si es un token de Sanctum
            if (str_contains($token, '|')) {
                return $this->validateSanctumToken($token);
            }

            // Verificar si es un token personalizado
            return $this->validateCustomToken($token);

        } catch (\Exception $e) {
            Log::error('Error al validar token', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 10) . '...'
            ]);

            return null;
        }
    }

    /**
     * Valida token de Sanctum
     */
    protected function validateSanctumToken(string $token): ?User
    {
        try {
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

            if (!$personalAccessToken) {
                return null;
            }

            // Verificar si el token no ha expirado
            if ($personalAccessToken->expires_at && $personalAccessToken->expires_at->isPast()) {
                return null;
            }

            return $personalAccessToken->tokenable;

        } catch (\Exception $e) {
            Log::error('Error al validar token de Sanctum', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Valida token personalizado
     */
    protected function validateCustomToken(string $token): ?User
    {
        try {
            // Buscar usuario por token personalizado
            $user = User::where('api_token', $token)->first();

            if (!$user) {
                return null;
            }

            // Verificar si el usuario está activo
            if (!$user->is_active ?? true) {
                return null;
            }

            return $user;

        } catch (\Exception $e) {
            Log::error('Error al validar token personalizado', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Respuesta de no autorizado
     */
    protected function unauthorizedResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED'
        ], 401);
    }

    /**
     * Respuesta de error del servidor
     */
    protected function serverErrorResponse(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'SERVER_ERROR'
        ], 500);
    }

    /**
     * Verifica si el usuario tiene permisos para acceder al endpoint
     */
    public function checkPermissions(Request $request, User $user): bool
    {
        $endpoint = $request->path();

        // Definir permisos por endpoint
        $permissions = [
            'api/drivers' => 'manage-drivers',
            'api/backups' => 'manage-backups',
            'api/notifications' => 'manage-notifications',
            'api/settings' => 'manage-settings',
            'api/users' => 'manage-users',
            'api/system' => 'view-system-status'
        ];

        foreach ($permissions as $path => $permission) {
            if (str_starts_with($endpoint, $path)) {
                return $user->can($permission);
            }
        }

        // Por defecto, permitir acceso si el usuario está autenticado
        return true;
    }

    /**
     * Obtiene información del usuario autenticado
     */
    public function getAuthenticatedUser(Request $request): ?User
    {
        return $request->get('api_user');
    }

    /**
     * Registra el acceso a la API
     */
    public function logApiAccess(Request $request, User $user): void
    {
        Log::info('Acceso API', [
            'user_id' => $user->id,
            'email' => $user->email,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}



