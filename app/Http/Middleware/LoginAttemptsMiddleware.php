<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\BlockedIpService;
use Symfony\Component\HttpFoundation\Response;

class LoginAttemptsMiddleware
{
    protected $blockedIpService;

    public function __construct(BlockedIpService $blockedIpService)
    {
        $this->blockedIpService = $blockedIpService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en rutas de login
        if (!$this->isLoginRoute($request)) {
            return $next($request);
        }

        $ip = $request->ip();
        $email = $request->input('email');
        $userAgent = $request->userAgent();

        // Verificar si la IP está en whitelist
        if ($this->blockedIpService->isIpWhitelisted($ip)) {
            return $next($request);
        }

        // Verificar si la IP está bloqueada
        if ($this->blockedIpService->isIpBlocked($ip)) {
            $remainingTime = $this->blockedIpService->getBlockTimeRemaining($ip);

            return response()->json([
                'error' => 'IP bloqueada por intentos fallidos',
                'message' => "Tu IP ha sido bloqueada temporalmente. Intenta nuevamente en {$remainingTime} minutos.",
                'retry_after' => $remainingTime * 60,
                'blocked_until' => now()->addMinutes($remainingTime)->toISOString()
            ], 429)->header('Retry-After', $remainingTime * 60);
        }

        // Verificar si el email está bloqueado
        if ($email && $this->blockedIpService->isEmailBlocked($email)) {
            return response()->json([
                'error' => 'Email bloqueado por intentos fallidos',
                'message' => "Esta cuenta ha sido bloqueada temporalmente por múltiples intentos fallidos.",
                'retry_after' => config('auth.login_lockout_time', 15) * 60
            ], 429)->header('Retry-After', config('auth.login_lockout_time', 15) * 60);
        }

        // Procesar la solicitud
        $response = $next($request);

        // Registrar el intento después de procesar la respuesta
        $this->recordLoginAttempt($request, $response);

        return $response;
    }

    /**
     * Verificar si es una ruta de login
     */
    private function isLoginRoute(Request $request): bool
    {
        $loginRoutes = ['/login', '/admin/login', 'login'];

        foreach ($loginRoutes as $route) {
            if ($request->is($route) || $request->path() === ltrim($route, '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener máximo número de intentos desde configuración
     */
    private function getMaxAttempts(): int
    {
        return (int) config('auth.login_max_attempts', 5);
    }

    /**
     * Obtener tiempo de bloqueo desde configuración
     */
    private function getLockoutTime(): int
    {
        return (int) config('auth.login_lockout_time', 15);
    }

    /**
     * Verificar si el login falló
     */
    private function isFailedLogin(Response $response): bool
    {
        // Verificar códigos de estado que indican login fallido
        $failedStatusCodes = [401, 422, 302]; // 302 puede ser redirect con errores

        // Si es un redirect (302), verificar si hay errores en la sesión
        if ($response->getStatusCode() === 302) {
            $session = request()->session();
            return $session->has('errors') || $session->has('status');
        }

        return in_array($response->getStatusCode(), $failedStatusCodes) ||
               $this->hasValidationErrors($response);
    }

    /**
     * Verificar si hay errores de validación
     */
    private function hasValidationErrors(Response $response): bool
    {
        $content = $response->getContent();

        // Buscar indicadores de error en la respuesta
        $errorIndicators = [
            'invalid credentials',
            'authentication failed',
            'login failed',
            'error',
            'errors'
        ];

        foreach ($errorIndicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Incrementar contador de intentos
     */
    private function incrementAttempts(string $key, int $lockoutTime): void
    {
        $attempts = Cache::get($key, 0);
        Cache::put($key, $attempts + 1, now()->addMinutes($lockoutTime));
    }

    /**
     * Limpiar intentos de login
     */
    private function clearAttempts(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Log de intento bloqueado
     */
    private function logBlockedAttempt(string $ip, int $attempts): void
    {
        Log::warning('Login attempt blocked', [
            'ip' => $ip,
            'attempts' => $attempts,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log de intento fallido
     */
    private function logFailedAttempt(string $ip, int $attempts): void
    {
        Log::info('Login attempt failed', [
            'ip' => $ip,
            'attempts' => $attempts,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log de login exitoso
     */
    private function logSuccessfulLogin(string $ip): void
    {
        Log::info('Login successful', [
            'ip' => $ip,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Registrar intento de login
     */
    private function recordLoginAttempt(Request $request, Response $response): void
    {
        $ip = $request->ip();
        $email = $request->input('email') ?? $request->input('username') ?? '';
        $userAgent = $request->userAgent();

        $success = $this->isSuccessfulLogin($response);
        $reason = $success ? null : $this->getFailureReason($response);

        $this->blockedIpService->recordLoginAttempt(
            $ip,
            $email ?: 'unknown',
            $userAgent,
            $success,
            $reason
        );
    }

    /**
     * Verificar si el login fue exitoso
     */
    private function isSuccessfulLogin(Response $response): bool
    {
        return $response->getStatusCode() === 302 && $this->isAuthenticated();
    }

    /**
     * Verificar si el usuario está autenticado
     */
    private function isAuthenticated(): bool
    {
        return auth()->check();
    }

    /**
     * Obtener razón del fallo
     */
    private function getFailureReason(Response $response): string
    {
        if ($response->getStatusCode() === 401) {
            return 'Credenciales inválidas';
        }

        if ($response->getStatusCode() === 422) {
            return 'Datos de validación incorrectos';
        }

        if ($response->getStatusCode() === 302) {
            $session = request()->session();
            if ($session->has('errors')) {
                return 'Error de validación';
            }
        }

        return 'Error desconocido';
    }
}
