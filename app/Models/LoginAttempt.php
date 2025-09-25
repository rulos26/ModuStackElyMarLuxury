<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'email',
        'user_agent',
        'attempted_at',
        'success',
        'reason'
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'success' => 'boolean',
    ];

    /**
     * Scope para obtener intentos de una IP específica
     */
    public function scopeForIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope para obtener intentos fallidos
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope para obtener intentos en un período de tiempo
     */
    public function scopeWithinTimeframe($query, $minutes = 15)
    {
        return $query->where('attempted_at', '>=', Carbon::now()->subMinutes($minutes));
    }

    /**
     * Scope para obtener intentos de un email específico
     */
    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Obtener intentos fallidos de una IP en un período
     */
    public static function getFailedAttemptsForIp($ip, $minutes = 15)
    {
        return self::forIp($ip)
            ->failed()
            ->withinTimeframe($minutes)
            ->count();
    }

    /**
     * Obtener intentos fallidos de un email en un período
     */
    public static function getFailedAttemptsForEmail($email, $minutes = 15)
    {
        return self::forEmail($email)
            ->failed()
            ->withinTimeframe($minutes)
            ->count();
    }

    /**
     * Registrar un intento de login
     */
    public static function recordAttempt($ip, $email, $userAgent, $success = false, $reason = null)
    {
        return self::create([
            'ip_address' => $ip,
            'email' => $email,
            'user_agent' => $userAgent,
            'attempted_at' => Carbon::now(),
            'success' => $success,
            'reason' => $reason
        ]);
    }

    /**
     * Verificar si una IP está bloqueada
     */
    public static function isIpBlocked($ip, $maxAttempts = 5, $minutes = 15)
    {
        $attempts = self::getFailedAttemptsForIp($ip, $minutes);
        return $attempts >= $maxAttempts;
    }

    /**
     * Verificar si un email está bloqueado
     */
    public static function isEmailBlocked($email, $maxAttempts = 5, $minutes = 15)
    {
        $attempts = self::getFailedAttemptsForEmail($email, $minutes);
        return $attempts >= $maxAttempts;
    }

    /**
     * Limpiar intentos antiguos (más de X días)
     */
    public static function cleanupOldAttempts($days = 30)
    {
        return self::where('attempted_at', '<', Carbon::now()->subDays($days))->delete();
    }

    /**
     * Limpiar intentos de una IP específica
     */
    public static function clearAttemptsForIp($ip)
    {
        return self::forIp($ip)->delete();
    }

    /**
     * Limpiar intentos de un email específico
     */
    public static function clearAttemptsForEmail($email)
    {
        return self::forEmail($email)->delete();
    }

    /**
     * Obtener estadísticas de intentos
     */
    public static function getAttemptStats($hours = 24)
    {
        $since = Carbon::now()->subHours($hours);

        return [
            'total_attempts' => self::where('attempted_at', '>=', $since)->count(),
            'failed_attempts' => self::where('attempted_at', '>=', $since)->failed()->count(),
            'successful_attempts' => self::where('attempted_at', '>=', $since)->where('success', true)->count(),
            'unique_ips' => self::where('attempted_at', '>=', $since)->distinct('ip_address')->count(),
            'blocked_ips' => self::where('attempted_at', '>=', $since)
                ->select('ip_address')
                ->failed()
                ->groupBy('ip_address')
                ->havingRaw('COUNT(*) >= ?', [config('auth.login_max_attempts', 5)])
                ->count()
        ];
    }

    /**
     * Obtener IPs más problemáticas
     */
    public static function getTopProblematicIps($limit = 10, $hours = 24)
    {
        $since = Carbon::now()->subHours($hours);

        return self::where('attempted_at', '>=', $since)
            ->failed()
            ->selectRaw('ip_address, COUNT(*) as attempt_count')
            ->groupBy('ip_address')
            ->orderBy('attempt_count', 'desc')
            ->limit($limit)
            ->get();
    }
}



