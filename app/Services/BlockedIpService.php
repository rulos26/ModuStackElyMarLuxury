<?php

namespace App\Services;

use App\Models\LoginAttempt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BlockedIpService
{
    /**
     * Verificar si una IP está bloqueada
     */
    public function isIpBlocked(string $ip): bool
    {
        $maxAttempts = config('auth.login_max_attempts', 5);
        $lockoutTime = config('auth.login_lockout_time', 15);

        // Primero verificar en cache para respuesta rápida
        $cacheKey = "blocked_ip_{$ip}";
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Verificar en base de datos
        $attempts = LoginAttempt::getFailedAttemptsForIp($ip, $lockoutTime);

        if ($attempts >= $maxAttempts) {
            // Marcar como bloqueada en cache por el tiempo restante
            $this->markIpAsBlocked($ip, $lockoutTime);
            return true;
        }

        return false;
    }

    /**
     * Verificar si un email está bloqueado
     */
    public function isEmailBlocked(string $email): bool
    {
        $maxAttempts = config('auth.login_max_attempts', 5);
        $lockoutTime = config('auth.login_lockout_time', 15);

        // Verificar en base de datos
        $attempts = LoginAttempt::getFailedAttemptsForEmail($email, $lockoutTime);

        return $attempts >= $maxAttempts;
    }

    /**
     * Registrar un intento de login
     */
    public function recordLoginAttempt(string $ip, string $email, string $userAgent, bool $success = false, ?string $reason = null): void
    {
        // Registrar en base de datos
        LoginAttempt::recordAttempt($ip, $email, $userAgent, $success, $reason);

        // Si es exitoso, limpiar intentos de la IP
        if ($success) {
            $this->clearIpAttempts($ip);
            $this->clearIpBlock($ip);
        } else {
            // Verificar si debe ser bloqueada después de este intento fallido
            $this->checkAndBlockIp($ip);
        }
    }

    /**
     * Marcar una IP como bloqueada en cache
     */
    public function markIpAsBlocked(string $ip, int $minutes): void
    {
        $cacheKey = "blocked_ip_{$ip}";
        Cache::put($cacheKey, true, now()->addMinutes($minutes));

        Log::warning('IP blocked due to failed login attempts', [
            'ip' => $ip,
            'blocked_until' => now()->addMinutes($minutes)->toISOString()
        ]);
    }

    /**
     * Limpiar bloqueo de una IP
     */
    public function clearIpBlock(string $ip): void
    {
        $cacheKey = "blocked_ip_{$ip}";
        Cache::forget($cacheKey);

        Log::info('IP block cleared', [
            'ip' => $ip,
            'cleared_at' => now()->toISOString()
        ]);
    }

    /**
     * Limpiar intentos de una IP
     */
    public function clearIpAttempts(string $ip): void
    {
        LoginAttempt::clearAttemptsForIp($ip);

        Log::info('Login attempts cleared for IP', [
            'ip' => $ip,
            'cleared_at' => now()->toISOString()
        ]);
    }

    /**
     * Limpiar intentos de un email
     */
    public function clearEmailAttempts(string $email): void
    {
        LoginAttempt::clearAttemptsForEmail($email);

        Log::info('Login attempts cleared for email', [
            'email' => $email,
            'cleared_at' => now()->toISOString()
        ]);
    }

    /**
     * Verificar y bloquear IP si es necesario
     */
    public function checkAndBlockIp(string $ip): void
    {
        $maxAttempts = config('auth.login_max_attempts', 5);
        $lockoutTime = config('auth.login_lockout_time', 15);

        $attempts = LoginAttempt::getFailedAttemptsForIp($ip, $lockoutTime);

        if ($attempts >= $maxAttempts) {
            $this->markIpAsBlocked($ip, $lockoutTime);
        }
    }

    /**
     * Obtener tiempo restante de bloqueo para una IP
     */
    public function getBlockTimeRemaining(string $ip): int
    {
        $cacheKey = "blocked_ip_{$ip}";
        $blockedUntil = Cache::get($cacheKey);

        if (!$blockedUntil) {
            return 0;
        }

        // Si está en cache, calcular tiempo restante
        $maxAttempts = config('auth.login_max_attempts', 5);
        $lockoutTime = config('auth.login_lockout_time', 15);

        $lastAttempt = LoginAttempt::forIp($ip)
            ->failed()
            ->orderBy('attempted_at', 'desc')
            ->first();

        if (!$lastAttempt) {
            return 0;
        }

        $blockedUntil = $lastAttempt->attempted_at->addMinutes($lockoutTime);
        $remaining = $blockedUntil->diffInMinutes(now());

        return max(0, $remaining);
    }

    /**
     * Obtener estadísticas de bloqueos
     */
    public function getBlockingStats(int $hours = 24): array
    {
        $stats = LoginAttempt::getAttemptStats($hours);
        $problematicIps = LoginAttempt::getTopProblematicIps(10, $hours);

        return [
            'timeframe' => $hours . ' hours',
            'total_attempts' => $stats['total_attempts'],
            'failed_attempts' => $stats['failed_attempts'],
            'successful_attempts' => $stats['successful_attempts'],
            'unique_ips' => $stats['unique_ips'],
            'blocked_ips' => $stats['blocked_ips'],
            'top_problematic_ips' => $problematicIps->map(function ($ip) {
                return [
                    'ip' => $ip->ip_address,
                    'attempts' => $ip->attempt_count
                ];
            })
        ];
    }

    /**
     * Limpiar intentos antiguos (mantenimiento)
     */
    public function cleanupOldAttempts(int $days = 30): int
    {
        $deleted = LoginAttempt::cleanupOldAttempts($days);

        Log::info('Old login attempts cleaned up', [
            'deleted_count' => $deleted,
            'older_than_days' => $days
        ]);

        return $deleted;
    }

    /**
     * Desbloquear IP manualmente (para administradores)
     */
    public function unblockIp(string $ip): bool
    {
        $this->clearIpBlock($ip);
        $this->clearIpAttempts($ip);

        Log::info('IP manually unblocked', [
            'ip' => $ip,
            'unblocked_at' => now()->toISOString()
        ]);

        return true;
    }

    /**
     * Verificar si una IP está en lista de IPs permitidas
     */
    public function isIpWhitelisted(string $ip): bool
    {
        $whitelist = config('auth.ip_whitelist', []);

        if (empty($whitelist)) {
            return false;
        }

        foreach ($whitelist as $whitelistedIp) {
            if ($this->ipMatches($ip, $whitelistedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si una IP coincide con un patrón (soporte para rangos)
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Si es una IP exacta
        if ($ip === $pattern) {
            return true;
        }

        // Si es un rango CIDR
        if (strpos($pattern, '/') !== false) {
            return $this->ipInRange($ip, $pattern);
        }

        return false;
    }

    /**
     * Verificar si una IP está en un rango CIDR
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        list($subnet, $bits) = explode('/', $range);

        if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet_long &= $mask;

        return ($ip_long & $mask) == $subnet_long;
    }
}



