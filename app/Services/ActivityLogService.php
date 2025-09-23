<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogService
{
    /**
     * Registrar actividad de autenticación
     */
    public function logAuth(string $event, ?string $description = null, ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();

        return ActivityLog::createLog(
            'auth',
            $description ?: "Evento de autenticación: {$event}",
            'info',
            $event,
            null,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Registrar actividad del sistema
     */
    public function logSystem(string $event, string $description, string $level = 'info', ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();

        return ActivityLog::createLog(
            'system',
            $description,
            $level,
            $event,
            null,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Registrar actividad de API
     */
    public function logApi(string $event, string $description, string $level = 'info', ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();

        return ActivityLog::createLog(
            'api',
            $description,
            $level,
            $event,
            null,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Registrar actividad de modelo
     */
    public function logModel(string $event, $model, string $description = null, ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();

        $description = $description ?: "Modelo {$event}: " . class_basename($model);

        return ActivityLog::createLog(
            'model',
            $description,
            'info',
            $event,
            $model,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Registrar actividad de usuario
     */
    public function logUser(string $event, $user, string $description = null, ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();

        $description = $description ?: "Usuario {$event}: {$user->name}";

        return ActivityLog::createLog(
            'user',
            $description,
            'info',
            $event,
            $user,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Registrar actividad de seguridad
     */
    public function logSecurity(string $event, string $description, string $level = 'warning', ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();

        return ActivityLog::createLog(
            'security',
            $description,
            $level,
            $event,
            null,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Registrar error crítico
     */
    public function logError(\Throwable $exception, ?string $description = null, ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();

        $description = $description ?: "Error: " . $exception->getMessage();

        $properties = array_merge([
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ], $properties ?? []);

        return ActivityLog::createLog(
            'error',
            $description,
            'critical',
            'error',
            null,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Registrar rendimiento
     */
    public function logPerformance(string $event, string $description, int $executionTime, int $memoryUsage, ?array $properties = null): ActivityLog
    {
        $context = $this->getRequestContext();
        $context['execution_time'] = $executionTime;
        $context['memory_usage'] = $memoryUsage;

        $level = $executionTime > 5000 ? 'warning' : 'info'; // Más de 5 segundos

        return ActivityLog::createLog(
            'performance',
            $description,
            $level,
            $event,
            null,
            Auth::user(),
            $properties,
            $context
        );
    }

    /**
     * Obtener contexto de la petición actual
     */
    protected function getRequestContext(): array
    {
        $request = request();

        if (!$request) {
            return [];
        }

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'session_id' => session()->getId(),
            'request_id' => $this->getRequestId()
        ];
    }

    /**
     * Generar ID único para la petición
     */
    protected function getRequestId(): string
    {
        if (!session()->has('request_id')) {
            session(['request_id' => Str::uuid()->toString()]);
        }

        return session('request_id');
    }

    /**
     * Obtener estadísticas de logs
     */
    public function getStats(?string $logName = null, ?int $days = 7): array
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        return ActivityLog::getStats($logName, $startDate, $endDate);
    }

    /**
     * Obtener logs recientes
     */
    public function getRecent(int $limit = 50, ?string $logName = null): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::getRecent($limit, $logName);
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs(int $daysToKeep = 30): int
    {
        return ActivityLog::cleanOldLogs($daysToKeep);
    }

    /**
     * Obtener logs por nivel
     */
    public function getLogsByLevel(string $level, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::level($level)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener logs por IP
     */
    public function getLogsByIp(string $ip, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::fromIp($ip)
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener logs lentos
     */
    public function getSlowLogs(int $threshold = 1000, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::slowQueries($threshold)
            ->with(['causer', 'subject'])
            ->orderBy('execution_time', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener logs de alto uso de memoria
     */
    public function getHighMemoryLogs(int $threshold = 134217728, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::highMemoryUsage($threshold)
            ->with(['causer', 'subject'])
            ->orderBy('memory_usage', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Exportar logs
     */
    public function exportLogs(?string $logName = null, ?int $days = 7): array
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        $query = ActivityLog::query();

        if ($logName) {
            $query->inLog($logName);
        }

        $query->betweenDates($startDate, $endDate)
              ->orderBy('created_at', 'desc');

        return $query->get()->toArray();
    }

    /**
     * Buscar logs
     */
    public function searchLogs(string $search, ?string $logName = null, ?int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        $query = ActivityLog::query();

        if ($logName) {
            $query->inLog($logName);
        }

        $query->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
              ->orWhere('event', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%")
              ->orWhere('user_agent', 'like', "%{$search}%");
        });

        return $query->with(['causer', 'subject'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }
}
