<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $limit = '100', string $decay = '60')
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $limit;
        $decayMinutes = (int) $decay;

        try {
            // Verificar rate limit usando RateLimiter de Laravel
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $retryAfter = RateLimiter::availableIn($key);

                Log::warning('Rate limit excedido', [
                    'key' => $key,
                    'ip' => $request->ip(),
                    'endpoint' => $request->path(),
                    'retry_after' => $retryAfter
                ]);

                return $this->buildResponse($maxAttempts, $retryAfter);
            }

            // Incrementar contador de intentos
            RateLimiter::hit($key, $decayMinutes * 60);

            // Agregar headers de rate limit a la respuesta
            $response = $next($request);

            return $this->addRateLimitHeaders($response, $key, $maxAttempts);

        } catch (\Exception $e) {
            Log::error('Error en rate limiting', [
                'error' => $e->getMessage(),
                'key' => $key,
                'ip' => $request->ip()
            ]);

            // En caso de error, permitir la request pero logear el error
            return $next($request);
        }
    }

    /**
     * Resuelve la clave única para el request
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            // Si hay usuario autenticado, usar su ID
            return 'api:' . $user->id;
        }

        // Si no hay usuario, usar IP
        return 'api:ip:' . $request->ip();
    }

    /**
     * Construye la respuesta de rate limit excedido
     */
    protected function buildResponse(int $maxAttempts, int $retryAfter): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Demasiadas solicitudes. Intenta de nuevo más tarde.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'max_attempts' => $maxAttempts
        ], 429);
    }

    /**
     * Agrega headers de rate limit a la respuesta
     */
    protected function addRateLimitHeaders($response, string $key, int $maxAttempts)
    {
        $remaining = RateLimiter::remaining($key, $maxAttempts);
        $retryAfter = RateLimiter::availableIn($key);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);

        return $response;
    }

    /**
     * Obtiene información del rate limit para un usuario
     */
    public function getRateLimitInfo(string $key): array
    {
        return [
            'attempts' => RateLimiter::attempts($key),
            'remaining' => RateLimiter::remaining($key, 100),
            'available_in' => RateLimiter::availableIn($key)
        ];
    }

    /**
     * Limpia el rate limit para un usuario específico
     */
    public function clearRateLimit(string $key): bool
    {
        try {
            RateLimiter::clear($key);
            return true;
        } catch (\Exception $e) {
            Log::error('Error al limpiar rate limit', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtiene estadísticas de rate limiting
     */
    public function getRateLimitStats(): array
    {
        try {
            $keys = Cache::get('rate_limiter_keys', []);
            $stats = [];

            foreach ($keys as $key) {
                $stats[$key] = $this->getRateLimitInfo($key);
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de rate limit', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Configura rate limits específicos por endpoint
     */
    public function getEndpointRateLimit(string $endpoint): array
    {
        $limits = [
            'api/drivers/change' => ['limit' => 10, 'decay' => 60],
            'api/backups/create' => ['limit' => 5, 'decay' => 300],
            'api/notifications/send' => ['limit' => 20, 'decay' => 60],
            'api/settings/update' => ['limit' => 15, 'decay' => 60],
            'api/users/create' => ['limit' => 5, 'decay' => 300],
            'api/system/status' => ['limit' => 30, 'decay' => 60]
        ];

        return $limits[$endpoint] ?? ['limit' => 100, 'decay' => 60];
    }

    /**
     * Aplica rate limit específico por endpoint
     */
    public function handleEndpointRateLimit(Request $request, Closure $next): \Illuminate\Http\Response
    {
        $endpoint = $request->path();
        $rateLimit = $this->getEndpointRateLimit($endpoint);

        $key = $this->resolveRequestSignature($request) . ':' . $endpoint;
        $maxAttempts = $rateLimit['limit'];
        $decayMinutes = $rateLimit['decay'];

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            Log::warning('Rate limit excedido para endpoint específico', [
                'endpoint' => $endpoint,
                'key' => $key,
                'ip' => $request->ip(),
                'retry_after' => $retryAfter
            ]);

            return $this->buildResponse($maxAttempts, $retryAfter);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);
        return $this->addRateLimitHeaders($response, $key, $maxAttempts);
    }
}
