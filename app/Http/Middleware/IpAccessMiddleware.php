<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AllowedIp;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IpAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Verificar si el control de acceso por IP está habilitado
        if (!$this->isIpAccessControlEnabled()) {
            return $next($request);
        }

        // Verificar si la IP está permitida
        if (!$this->isIpAllowed($ip)) {
            $this->logBlockedAccess($ip, $request);
            return $this->blockAccess($request);
        }

        // Registrar acceso exitoso (opcional, para auditoría)
        $this->logAllowedAccess($ip, $request);

        return $next($request);
    }

    /**
     * Verificar si el control de acceso por IP está habilitado
     */
    private function isIpAccessControlEnabled(): bool
    {
        return (bool) AppSetting::getValue('ip_whitelist_enabled', false);
    }

    /**
     * Verificar si una IP está permitida
     */
    private function isIpAllowed(string $ip): bool
    {
        // Verificar IPs específicas
        if ($this->isIpInSpecificList($ip)) {
            return true;
        }

        // Verificar rangos CIDR
        if ($this->isIpInCidrRanges($ip)) {
            return true;
        }

        // Verificar si hay IPs bloqueadas específicamente
        if ($this->isIpBlocked($ip)) {
            return false;
        }

        // Si el control de IP está habilitado y no hay IPs permitidas, bloquear acceso
        $hasAllowedIps = AllowedIp::active()->whereIn('type', ['specific', 'cidr'])->exists();
        if ($hasAllowedIps) {
            return false; // Hay configuración pero esta IP no está permitida
        }

        // Si no hay configuración específica, permitir acceso
        return true;
    }

    /**
     * Verificar si la IP está en la lista de IPs específicas
     */
    private function isIpInSpecificList(string $ip): bool
    {
        return AllowedIp::active()
            ->where('type', 'specific')
            ->where('ip_address', $ip)
            ->exists();
    }

    /**
     * Verificar si la IP está en rangos CIDR
     */
    private function isIpInCidrRanges(string $ip): bool
    {
        $cidrRanges = AllowedIp::active()
            ->where('type', 'cidr')
            ->pluck('ip_address');

        foreach ($cidrRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si la IP está bloqueada
     */
    private function isIpBlocked(string $ip): bool
    {
        return AllowedIp::active()
            ->where('ip_address', $ip)
            ->where('type', 'blocked')
            ->exists();
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

        // Para IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->ipv4InRange($ip, $subnet, $bits);
        }

        // Para IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6InRange($ip, $subnet, $bits);
        }

        return false;
    }

    /**
     * Verificar si una IPv4 está en un rango CIDR
     */
    private function ipv4InRange(string $ip, string $subnet, int $bits): bool
    {
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet_long &= $mask;

        return ($ip_long & $mask) == $subnet_long;
    }

    /**
     * Verificar si una IPv6 está en un rango CIDR
     */
    private function ipv6InRange(string $ip, string $subnet, int $bits): bool
    {
        $ip_bin = inet_pton($ip);
        $subnet_bin = inet_pton($subnet);

        if ($ip_bin === false || $subnet_bin === false) {
            return false;
        }

        // Crear máscara para los bits especificados
        $mask = str_repeat(chr(0xFF), floor($bits / 8));
        if ($bits % 8 !== 0) {
            $mask .= chr(0xFF << (8 - ($bits % 8)));
        }
        $mask = str_pad($mask, 16, chr(0), STR_PAD_RIGHT);

        // Aplicar máscara
        $ip_masked = $ip_bin & $mask;
        $subnet_masked = $subnet_bin & $mask;

        return $ip_masked === $subnet_masked;
    }

    /**
     * Bloquear acceso y devolver respuesta
     */
    private function blockAccess(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Acceso denegado',
                'message' => 'Tu dirección IP no está autorizada para acceder a este sistema.',
                'code' => 'IP_ACCESS_DENIED'
            ], 403);
        }

        return response()->view('errors.403', [
            'message' => 'Acceso denegado: Tu dirección IP no está autorizada para acceder a este sistema.'
        ], 403);
    }

    /**
     * Registrar acceso bloqueado
     */
    private function logBlockedAccess(string $ip, Request $request): void
    {
        Log::warning('IP access blocked', [
            'ip' => $ip,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Registrar acceso permitido (para auditoría)
     */
    private function logAllowedAccess(string $ip, Request $request): void
    {
        // Solo registrar si está habilitado el logging de accesos
        if (AppSetting::getValue('ip_access_logging', false)) {
            Log::info('IP access allowed', [
                'ip' => $ip,
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Obtener estadísticas de acceso por IP
     */
    public static function getAccessStats(int $hours = 24): array
    {
        // Esta funcionalidad se puede implementar con logs de Laravel
        // Por ahora retornamos estadísticas básicas
        return [
            'total_allowed_ips' => AllowedIp::where('type', 'specific')->where('status', 'active')->count(),
            'total_cidr_ranges' => AllowedIp::where('type', 'cidr')->where('status', 'active')->count(),
            'total_blocked_ips' => AllowedIp::where('type', 'blocked')->where('status', 'active')->count(),
            'ip_control_enabled' => (bool) AppSetting::getValue('ip_whitelist_enabled', false),
            'access_logging_enabled' => (bool) AppSetting::getValue('ip_access_logging', false),
        ];
    }

    /**
     * Verificar si una IP específica está permitida (método público)
     */
    public static function checkIpAccess(string $ip): bool
    {
        // Verificar si el control de acceso por IP está habilitado
        if (!(bool) AppSetting::getValue('ip_whitelist_enabled', false)) {
            return true;
        }

        // Verificar IPs específicas
        if (AllowedIp::active()->specific()->where('ip_address', $ip)->exists()) {
            return true;
        }

        // Verificar rangos CIDR
        $cidrRanges = AllowedIp::active()->cidr()->pluck('ip_address');
        foreach ($cidrRanges as $range) {
            if (AllowedIp::ipInRange($ip, $range)) {
                return true;
            }
        }

        // Verificar si está bloqueada
        if (AllowedIp::active()->blocked()->where('ip_address', $ip)->exists()) {
            return false;
        }

        // Si hay configuración pero esta IP no está permitida, bloquear
        $hasAllowedIps = AllowedIp::active()->whereIn('type', ['specific', 'cidr'])->exists();
        if ($hasAllowedIps) {
            return false;
        }

        // Si no hay configuración específica, permitir acceso
        return true;
    }

    /**
     * Agregar IP a la lista de permitidas
     */
    public static function addAllowedIp(string $ip, string $type = 'specific', string $description = ''): bool
    {
        try {
            AllowedIp::create([
                'ip_address' => $ip,
                'type' => $type,
                'description' => $description,
                'status' => 'active',
                'created_by' => auth()->id() ?? null
            ]);

            Log::info('IP added to allowed list', [
                'ip' => $ip,
                'type' => $type,
                'description' => $description,
                'added_by' => auth()->id()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add IP to allowed list', [
                'ip' => $ip,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Remover IP de la lista de permitidas
     */
    public static function removeAllowedIp(string $ip): bool
    {
        try {
            $deleted = AllowedIp::where('ip_address', $ip)->delete();

            if ($deleted) {
                Log::info('IP removed from allowed list', [
                    'ip' => $ip,
                    'removed_by' => auth()->id()
                ]);
            }

            return $deleted > 0;
        } catch (\Exception $e) {
            Log::error('Failed to remove IP from allowed list', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
