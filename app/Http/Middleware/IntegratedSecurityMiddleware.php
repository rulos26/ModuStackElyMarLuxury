<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\BlockedIpService;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use App\Models\LoginAttempt;
use App\Models\AllowedIp;

class IntegratedSecurityMiddleware
{
    protected $blockedIpService;
    protected $activityLogService;
    protected $notificationService;

    public function __construct(
        BlockedIpService $blockedIpService,
        ActivityLogService $activityLogService,
        NotificationService $notificationService
    ) {
        $this->blockedIpService = $blockedIpService;
        $this->activityLogService = $activityLogService;
        $this->notificationService = $notificationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        try {
            // 1. Verificar IP bloqueada
            if ($this->isIpBlocked($request)) {
                return $this->handleBlockedIp($request);
            }

            // 2. Verificar IP permitida
            if (!$this->isIpAllowed($request)) {
                return $this->handleUnauthorizedIp($request);
            }

            // 3. Verificar rate limiting
            if ($this->isRateLimited($request)) {
                return $this->handleRateLimited($request);
            }

            // 4. Verificar patrones sospechosos
            if ($this->hasSuspiciousPatterns($request)) {
                return $this->handleSuspiciousActivity($request);
            }

            // 5. Verificar headers de seguridad
            if (!$this->hasValidSecurityHeaders($request)) {
                return $this->handleInvalidSecurityHeaders($request);
            }

            // 6. Procesar la request
            $response = $next($request);

            // 7. Post-procesamiento de seguridad
            $this->postProcessSecurity($request, $response, $startTime);

            return $response;

        } catch (\Exception $e) {
            // Manejo de errores de seguridad
            $this->handleSecurityError($request, $e, $startTime);

            // Re-lanzar la excepción
            throw $e;
        }
    }

    /**
     * Verifica si la IP está bloqueada
     */
    protected function isIpBlocked(Request $request): bool
    {
        $ip = $request->ip();

        // Verificar en cache primero
        $cacheKey = "blocked_ip_{$ip}";
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Verificar en base de datos
        $isBlocked = $this->blockedIpService->isIpBlocked($ip);

        if ($isBlocked) {
            // Cachear el resultado
            Cache::put($cacheKey, true, 3600); // 1 hora
        }

        return $isBlocked;
    }

    /**
     * Verifica si la IP está permitida
     */
    protected function isIpAllowed(Request $request): bool
    {
        $ip = $request->ip();

        // Si no hay restricciones de IP, permitir
        $allowedIps = AllowedIp::where('is_active', true)->pluck('ip_address')->toArray();

        if (empty($allowedIps)) {
            return true;
        }

        // Verificar si la IP está en la lista de permitidas
        return in_array($ip, $allowedIps);
    }

    /**
     * Verifica si la request está limitada por rate
     */
    protected function isRateLimited(Request $request): bool
    {
        $ip = $request->ip();
        $key = "security_rate_limit_{$ip}";

        // Rate limiting más estricto para seguridad
        $maxAttempts = 60; // 60 requests por minuto
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return true;
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        return false;
    }

    /**
     * Verifica patrones sospechosos en la request
     */
    protected function hasSuspiciousPatterns(Request $request): bool
    {
        $suspiciousPatterns = [
            // Patrones de inyección SQL
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/update\s+set/i',
            '/or\s+1\s*=\s*1/i',
            '/and\s+1\s*=\s*1/i',

            // Patrones de XSS
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',

            // Patrones de path traversal
            '/\.\.\//',
            '/\.\.\\\\/',

            // Patrones de comandos del sistema
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',

            // Patrones de archivos peligrosos
            '/\.php$/i',
            '/\.exe$/i',
            '/\.bat$/i',
            '/\.sh$/i',

            // Patrones de headers sospechosos
            '/user-agent.*bot/i',
            '/user-agent.*crawler/i',
            '/user-agent.*spider/i'
        ];

        $requestData = [
            $request->fullUrl(),
            $request->getContent(),
            $request->userAgent(),
            json_encode($request->all()),
            json_encode($request->headers->all())
        ];

        foreach ($suspiciousPatterns as $pattern) {
            foreach ($requestData as $data) {
                if (preg_match($pattern, $data)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verifica headers de seguridad válidos
     */
    protected function hasValidSecurityHeaders(Request $request): bool
    {
        // Verificar User-Agent
        if (empty($request->userAgent())) {
            return false;
        }

        // Verificar Referer para requests POST
        if ($request->isMethod('POST') && !$request->is('api/*')) {
            $referer = $request->header('Referer');
            $origin = $request->header('Origin');

            if (empty($referer) && empty($origin)) {
                return false;
            }
        }

        // Verificar Content-Type para requests POST
        if ($request->isMethod('POST')) {
            $contentType = $request->header('Content-Type');
            if (empty($contentType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Maneja IP bloqueada
     */
    protected function handleBlockedIp(Request $request)
    {
        $ip = $request->ip();

        // Log de intento de acceso desde IP bloqueada
        Log::channel('daily')->warning('Blocked IP access attempt', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('blocked_ip_access', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Acceso denegado',
            'error_code' => 'IP_BLOCKED'
        ], 403);
    }

    /**
     * Maneja IP no autorizada
     */
    protected function handleUnauthorizedIp(Request $request)
    {
        $ip = $request->ip();

        // Log de intento de acceso desde IP no autorizada
        Log::channel('daily')->warning('Unauthorized IP access attempt', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('unauthorized_ip_access', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Acceso no autorizado',
            'error_code' => 'IP_NOT_ALLOWED'
        ], 403);
    }

    /**
     * Maneja rate limiting
     */
    protected function handleRateLimited(Request $request)
    {
        $ip = $request->ip();

        // Log de rate limiting
        Log::channel('daily')->warning('Rate limit exceeded', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('rate_limit_exceeded', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Demasiadas solicitudes',
            'error_code' => 'RATE_LIMITED'
        ], 429);
    }

    /**
     * Maneja actividad sospechosa
     */
    protected function handleSuspiciousActivity(Request $request)
    {
        $ip = $request->ip();

        // Log de actividad sospechosa
        Log::channel('daily')->warning('Suspicious activity detected', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'request_data' => $request->all(),
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('suspicious_activity', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'request_data' => $request->all()
        ]);

        // Incrementar contador de intentos sospechosos
        $this->incrementSuspiciousAttempts($ip);

        // Enviar notificación de seguridad
        $this->notificationService->createNotification(
            'Actividad Sospechosa Detectada',
            "Se ha detectado actividad sospechosa desde la IP: {$ip}",
            'warning'
        );

        return response()->json([
            'success' => false,
            'message' => 'Actividad sospechosa detectada',
            'error_code' => 'SUSPICIOUS_ACTIVITY'
        ], 403);
    }

    /**
     * Maneja headers de seguridad inválidos
     */
    protected function handleInvalidSecurityHeaders(Request $request)
    {
        $ip = $request->ip();

        // Log de headers inválidos
        Log::channel('daily')->warning('Invalid security headers', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('invalid_security_headers', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Headers de seguridad inválidos',
            'error_code' => 'INVALID_HEADERS'
        ], 400);
    }

    /**
     * Post-procesamiento de seguridad
     */
    protected function postProcessSecurity(Request $request, $response, float $startTime): void
    {
        try {
            // Calcular métricas de seguridad
            $securityMetrics = $this->calculateSecurityMetrics($request, $response, $startTime);

            // Actualizar estadísticas de seguridad
            $this->updateSecurityStatistics($request, $securityMetrics);

            // Verificar si hay patrones de ataque
            $this->checkForAttackPatterns($request, $response);

        } catch (\Exception $e) {
            Log::error('Error en post-procesamiento de seguridad', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Maneja errores de seguridad
     */
    protected function handleSecurityError(Request $request, \Exception $e, float $startTime): void
    {
        try {
            // Log de error de seguridad
            Log::channel('daily')->error('Security error occurred', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'timestamp' => now()->toISOString()
            ]);

            // Log de actividad
            $this->activityLogService->logSystemActivity('security_error', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Enviar notificación de error de seguridad
            $this->notificationService->createNotification(
                'Error de Seguridad',
                "Se ha producido un error de seguridad: {$e->getMessage()}",
                'error'
            );

        } catch (\Exception $logError) {
            // Si incluso el logging falla, registrar en archivo de log
            error_log("Error crítico en middleware de seguridad: " . $logError->getMessage());
        }
    }

    /**
     * Incrementa el contador de intentos sospechosos
     */
    protected function incrementSuspiciousAttempts(string $ip): void
    {
        $key = "suspicious_attempts_{$ip}";
        $attempts = Cache::get($key, 0);
        $attempts++;

        Cache::put($key, $attempts, 3600); // 1 hora

        // Si hay demasiados intentos sospechosos, bloquear la IP
        if ($attempts >= 5) {
            $this->blockedIpService->blockIp($ip, 'Demasiados intentos sospechosos');

            // Enviar notificación de bloqueo
            $this->notificationService->createNotification(
                'IP Bloqueada Automáticamente',
                "La IP {$ip} ha sido bloqueada automáticamente por demasiados intentos sospechosos",
                'error'
            );
        }
    }

    /**
     * Calcula métricas de seguridad
     */
    protected function calculateSecurityMetrics(Request $request, $response, float $startTime): array
    {
        return [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'execution_time' => microtime(true) - $startTime,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Actualiza estadísticas de seguridad
     */
    protected function updateSecurityStatistics(Request $request, array $metrics): void
    {
        $statsKey = 'security_stats_' . date('Y-m-d');
        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'blocked_requests' => 0,
            'suspicious_requests' => 0,
            'error_requests' => 0,
            'ips' => [],
            'user_agents' => [],
            'endpoints' => []
        ]);

        // Actualizar estadísticas generales
        $stats['total_requests']++;

        if ($metrics['status_code'] === 403) {
            $stats['blocked_requests']++;
        }

        if ($metrics['status_code'] >= 400) {
            $stats['error_requests']++;
        }

        // Estadísticas por IP
        $ip = $metrics['ip'];
        if (!isset($stats['ips'][$ip])) {
            $stats['ips'][$ip] = 0;
        }
        $stats['ips'][$ip]++;

        // Estadísticas por User-Agent
        $userAgent = $metrics['user_agent'];
        if ($userAgent) {
            $shortUserAgent = $this->getShortUserAgent($userAgent);
            if (!isset($stats['user_agents'][$shortUserAgent])) {
                $stats['user_agents'][$shortUserAgent] = 0;
            }
            $stats['user_agents'][$shortUserAgent]++;
        }

        // Estadísticas por endpoint
        $endpoint = $request->path();
        if (!isset($stats['endpoints'][$endpoint])) {
            $stats['endpoints'][$endpoint] = 0;
        }
        $stats['endpoints'][$endpoint]++;

        // Guardar estadísticas
        Cache::put($statsKey, $stats, 86400); // 24 horas
    }

    /**
     * Verifica patrones de ataque
     */
    protected function checkForAttackPatterns(Request $request, $response): void
    {
        $attackPatterns = [
            'sql_injection' => $this->detectSqlInjection($request),
            'xss_attack' => $this->detectXssAttack($request),
            'path_traversal' => $this->detectPathTraversal($request),
            'command_injection' => $this->detectCommandInjection($request)
        ];

        $detectedAttacks = array_filter($attackPatterns);

        if (!empty($detectedAttacks)) {
            $this->handleDetectedAttacks($request, $detectedAttacks);
        }
    }

    /**
     * Detecta inyección SQL
     */
    protected function detectSqlInjection(Request $request): bool
    {
        $sqlPatterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/update\s+set/i',
            '/or\s+1\s*=\s*1/i',
            '/and\s+1\s*=\s*1/i'
        ];

        $requestData = $request->all();
        $requestString = json_encode($requestData);

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $requestString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta ataques XSS
     */
    protected function detectXssAttack(Request $request): bool
    {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i'
        ];

        $requestData = $request->all();
        $requestString = json_encode($requestData);

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $requestString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta path traversal
     */
    protected function detectPathTraversal(Request $request): bool
    {
        $pathPatterns = [
            '/\.\.\//',
            '/\.\.\\\\/'
        ];

        $requestData = $request->all();
        $requestString = json_encode($requestData);

        foreach ($pathPatterns as $pattern) {
            if (preg_match($pattern, $requestString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta inyección de comandos
     */
    protected function detectCommandInjection(Request $request): bool
    {
        $commandPatterns = [
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i'
        ];

        $requestData = $request->all();
        $requestString = json_encode($requestData);

        foreach ($commandPatterns as $pattern) {
            if (preg_match($pattern, $requestString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Maneja ataques detectados
     */
    protected function handleDetectedAttacks(Request $request, array $attacks): void
    {
        $ip = $request->ip();

        // Log de ataques detectados
        Log::channel('daily')->critical('Attack patterns detected', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'attacks' => $attacks,
            'timestamp' => now()->toISOString()
        ]);

        // Log de actividad
        $this->activityLogService->logSystemActivity('attack_detected', [
            'ip' => $ip,
            'url' => $request->fullUrl(),
            'attacks' => $attacks
        ]);

        // Bloquear IP inmediatamente
        $this->blockedIpService->blockIp($ip, 'Patrones de ataque detectados: ' . implode(', ', array_keys($attacks)));

        // Enviar notificación crítica
        $this->notificationService->createNotification(
            'Ataque Detectado',
            "Se han detectado patrones de ataque desde la IP {$ip}: " . implode(', ', array_keys($attacks)),
            'error'
        );
    }

    /**
     * Obtiene una versión corta del user agent
     */
    protected function getShortUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        } elseif (str_contains($userAgent, 'Postman')) {
            return 'Postman';
        } elseif (str_contains($userAgent, 'curl')) {
            return 'cURL';
        } else {
            return 'Other';
        }
    }

    /**
     * Obtiene estadísticas de seguridad
     */
    public function getSecurityStatistics(): array
    {
        $statsKey = 'security_stats_' . date('Y-m-d');
        $stats = Cache::get($statsKey, []);

        return [
            'total_requests' => $stats['total_requests'] ?? 0,
            'blocked_requests' => $stats['blocked_requests'] ?? 0,
            'suspicious_requests' => $stats['suspicious_requests'] ?? 0,
            'error_requests' => $stats['error_requests'] ?? 0,
            'block_rate' => $stats['total_requests'] > 0 ?
                round(($stats['blocked_requests'] ?? 0) / $stats['total_requests'] * 100, 2) : 0,
            'error_rate' => $stats['total_requests'] > 0 ?
                round(($stats['error_requests'] ?? 0) / $stats['total_requests'] * 100, 2) : 0,
            'top_ips' => $this->getTopIps($stats['ips'] ?? []),
            'top_user_agents' => $stats['user_agents'] ?? [],
            'top_endpoints' => $stats['endpoints'] ?? []
        ];
    }

    /**
     * Obtiene las IPs más activas
     */
    protected function getTopIps(array $ips): array
    {
        arsort($ips);
        return array_slice($ips, 0, 10, true);
    }

    /**
     * Limpia estadísticas antiguas
     */
    public function cleanupOldStatistics(): void
    {
        $daysToKeep = 7;
        for ($i = 1; $i <= $daysToKeep; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $oldStatsKey = 'security_stats_' . $date;
            Cache::forget($oldStatsKey);
        }

        Log::info('Old security statistics cleaned up', [
            'days_cleaned' => $daysToKeep,
            'timestamp' => now()->toISOString()
        ]);
    }
}

