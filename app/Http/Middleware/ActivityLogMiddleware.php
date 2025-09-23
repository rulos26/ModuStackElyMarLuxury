<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogMiddleware
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Procesar la petición
        $response = $next($request);

        // Calcular métricas
        $executionTime = round((microtime(true) - $startTime) * 1000); // En milisegundos
        $memoryUsage = memory_get_usage() - $startMemory;

        // Registrar la actividad
        $this->logRequest($request, $response, $executionTime, $memoryUsage);

        return $response;
    }

    /**
     * Registrar la petición
     */
    protected function logRequest(Request $request, Response $response, int $executionTime, int $memoryUsage): void
    {
        try {
            $level = $this->getLogLevel($response->getStatusCode());
            $event = $this->getEvent($request, $response);
            $description = $this->getDescription($request, $response);

            $properties = [
                'request_data' => $this->getSanitizedRequestData($request),
                'response_size' => strlen($response->getContent()),
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage
            ];

            $context = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'session_id' => session()->getId(),
                'request_id' => $this->getRequestId($request)
            ];

            $this->activityLogService->logApi(
                $event,
                $description,
                $level,
                $properties
            );

        } catch (\Exception $e) {
            // No registrar errores de logging para evitar recursión
            \Log::error('Error en ActivityLogMiddleware: ' . $e->getMessage());
        }
    }

    /**
     * Determinar el nivel de log basado en el código de estado
     */
    protected function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } elseif ($statusCode >= 300) {
            return 'info';
        }

        return 'info';
    }

    /**
     * Determinar el evento basado en la petición y respuesta
     */
    protected function getEvent(Request $request, Response $response): string
    {
        $method = $request->method();
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 500) {
            return 'server_error';
        } elseif ($statusCode >= 400) {
            return 'client_error';
        }

        switch ($method) {
            case 'GET':
                return 'view';
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return 'request';
        }
    }

    /**
     * Generar descripción de la actividad
     */
    protected function getDescription(Request $request, Response $response): string
    {
        $method = $request->method();
        $url = $request->path();
        $statusCode = $response->getStatusCode();

        $user = auth()->user();
        $userInfo = $user ? " ({$user->name})" : ' (anónimo)';

        return "{$method} {$url} - {$statusCode}{$userInfo}";
    }

    /**
     * Obtener datos de la petición sanitizados
     */
    protected function getSanitizedRequestData(Request $request): array
    {
        $data = $request->except(['password', 'password_confirmation', '_token', 'api_token']);

        // Limitar el tamaño de los datos
        if (strlen(json_encode($data)) > 10000) {
            return ['data_too_large' => true];
        }

        return $data;
    }

    /**
     * Generar ID único para la petición
     */
    protected function getRequestId(Request $request): string
    {
        if (!session()->has('request_id')) {
            session(['request_id' => \Illuminate\Support\Str::uuid()->toString()]);
        }

        return session('request_id');
    }
}
