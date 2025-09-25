<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el modo mantenimiento está activo
        if ($this->isMaintenanceModeActive()) {
            // Verificar si el usuario está autorizado para acceder durante mantenimiento
            if (!$this->isUserAuthorized($request)) {
                // Verificar si la IP está en la lista de IPs permitidas
                if (!$this->isIpAllowed($request)) {
                    return $this->getMaintenanceResponse($request);
                }
            }
        }

        return $next($request);
    }

    /**
     * Verificar si el modo mantenimiento está activo
     */
    protected function isMaintenanceModeActive(): bool
    {
        return Cache::get('maintenance_mode', false);
    }

    /**
     * Verificar si el usuario está autorizado para acceder durante mantenimiento
     */
    protected function isUserAuthorized(Request $request): bool
    {
        // Si no hay usuario autenticado, no está autorizado
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Verificar si el usuario tiene rol de administrador
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        // Verificar si el usuario está en la lista de usuarios permitidos
        $allowedUsers = Cache::get('maintenance_allowed_users', []);
        return in_array($user->id, $allowedUsers);
    }

    /**
     * Verificar si la IP está en la lista de IPs permitidas
     */
    protected function isIpAllowed(Request $request): bool
    {
        $userIp = $request->ip();
        $allowedIps = Cache::get('maintenance_allowed_ips', []);

        // Si no hay IPs configuradas, no permitir ninguna
        if (empty($allowedIps)) {
            return false;
        }

        // Verificar cada IP permitida (soporta CIDR)
        foreach ($allowedIps as $allowedIp) {
            if ($this->ipMatches($userIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si una IP coincide con un patrón (soporta CIDR)
     */
    protected function ipMatches(string $ip, string $pattern): bool
    {
        // Si es una IP exacta
        if ($ip === $pattern) {
            return true;
        }

        // Si contiene CIDR notation
        if (strpos($pattern, '/') !== false) {
            return $this->ipInRange($ip, $pattern);
        }

        return false;
    }

    /**
     * Verificar si una IP está en un rango CIDR
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);

        if ($bits === null) {
            $bits = 32;
        }

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Obtener respuesta de mantenimiento
     */
    protected function getMaintenanceResponse(Request $request): Response
    {
        // Si es una petición AJAX, devolver JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => 'Modo mantenimiento activo',
                'message' => 'El sitio está temporalmente en mantenimiento. Por favor, inténtalo más tarde.',
                'retry_after' => $this->getRetryAfter()
            ], 503);
        }

        // Si es una petición web, devolver vista HTML
        return response()->view('maintenance', [
            'title' => 'Sitio en Mantenimiento',
            'message' => 'Estamos realizando mejoras en nuestro sitio web. Volveremos pronto.',
            'retry_after' => $this->getRetryAfter(),
            'contact_info' => $this->getContactInfo()
        ], 503);
    }

    /**
     * Obtener tiempo de reintento
     */
    protected function getRetryAfter(): ?int
    {
        return Cache::get('maintenance_retry_after', 3600); // 1 hora por defecto
    }

    /**
     * Obtener información de contacto
     */
    protected function getContactInfo(): array
    {
        return Cache::get('maintenance_contact_info', [
            'email' => config('mail.from.address'),
            'phone' => null,
            'support_url' => null
        ]);
    }
}
