<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\DynamicDriverService;
use Illuminate\Support\Facades\Log;

class DynamicDriverMiddleware
{
    protected $dynamicDriverService;

    public function __construct(DynamicDriverService $dynamicDriverService)
    {
        $this->dynamicDriverService = $dynamicDriverService;
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
        try {
            // Restaurar configuraciones de drivers desde base de datos
            $this->restoreDriverConfigurations();

            // Aplicar configuraciones específicas por ruta si es necesario
            $this->applyRouteSpecificConfigurations($request);

        } catch (\Exception $e) {
            Log::error('Error en DynamicDriverMiddleware', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }

        return $next($request);
    }

    /**
     * Restaura las configuraciones de drivers desde la base de datos
     */
    protected function restoreDriverConfigurations(): void
    {
        $services = ['cache', 'session', 'queue', 'mail', 'database'];

        foreach ($services as $service) {
            try {
                $this->dynamicDriverService->restoreDriverConfig($service);
            } catch (\Exception $e) {
                Log::warning("No se pudo restaurar configuración para {$service}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Aplica configuraciones específicas por ruta
     */
    protected function applyRouteSpecificConfigurations(Request $request): void
    {
        $route = $request->route();

        if (!$route) {
            return;
        }

        $routeName = $route->getName();
        $routeAction = $route->getAction();

        // Configuraciones específicas por ruta
        $routeConfigs = $this->getRouteSpecificConfigurations($routeName, $routeAction);

        foreach ($routeConfigs as $service => $config) {
            try {
                if (isset($config['driver'])) {
                    $this->dynamicDriverService->changeDriver(
                        $service,
                        $config['driver'],
                        $config['config'] ?? []
                    );
                }
            } catch (\Exception $e) {
                Log::warning("No se pudo aplicar configuración específica para {$service}", [
                    'route' => $routeName,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Obtiene configuraciones específicas por ruta
     */
    protected function getRouteSpecificConfigurations(string $routeName, array $routeAction): array
    {
        $configurations = [];

        // Configuraciones basadas en el nombre de la ruta
        switch ($routeName) {
            case 'admin.drivers.index':
            case 'admin.drivers.store':
            case 'admin.drivers.update':
                // Para rutas de administración de drivers, usar configuración de sesión
                $configurations['session'] = [
                    'driver' => 'database',
                    'config' => [
                        'table' => 'sessions',
                        'connection' => null
                    ]
                ];
                break;

            case 'api.*':
                // Para rutas API, usar configuración específica
                $configurations['cache'] = [
                    'driver' => 'redis',
                    'config' => [
                        'connection' => 'default',
                        'prefix' => 'api_cache'
                    ]
                ];
                break;

            case 'admin.*':
                // Para rutas de administración, usar configuración específica
                $configurations['cache'] = [
                    'driver' => 'file',
                    'config' => [
                        'path' => storage_path('framework/cache/admin')
                    ]
                ];
                break;
        }

        // Configuraciones basadas en middleware
        if (in_array('throttle', $routeAction['middleware'] ?? [])) {
            $configurations['cache'] = [
                'driver' => 'redis',
                'config' => [
                    'connection' => 'throttle',
                    'prefix' => 'throttle'
                ]
            ];
        }

        return $configurations;
    }

    /**
     * Aplica configuración de driver para una ruta específica
     */
    public function applyDriverForRoute(string $routeName, string $service, string $driver, array $config = []): bool
    {
        try {
            return $this->dynamicDriverService->changeDriver($service, $driver, $config);
        } catch (\Exception $e) {
            Log::error("Error al aplicar driver para ruta específica", [
                'route' => $routeName,
                'service' => $service,
                'driver' => $driver,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Obtiene el estado actual de los drivers
     */
    public function getDriversStatus(): array
    {
        return $this->dynamicDriverService->getAllDriversStatus();
    }

    /**
     * Valida si un driver es compatible con la ruta actual
     */
    public function validateDriverForRoute(string $routeName, string $service, string $driver): bool
    {
        $supportedDrivers = $this->dynamicDriverService->getSupportedDrivers($service);

        if (!in_array($driver, $supportedDrivers)) {
            return false;
        }

        // Validaciones específicas por ruta
        switch ($routeName) {
            case 'admin.drivers.*':
                // Para administración de drivers, evitar drivers que puedan causar conflictos
                if ($service === 'session' && $driver === 'array') {
                    return false;
                }
                break;

            case 'api.*':
                // Para API, preferir drivers de alto rendimiento
                if ($service === 'cache' && in_array($driver, ['file', 'array'])) {
                    return false;
                }
                break;
        }

        return true;
    }
}

