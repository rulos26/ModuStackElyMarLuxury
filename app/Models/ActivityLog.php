<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'level',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'status_code',
        'execution_time',
        'memory_usage',
        'session_id',
        'request_id'
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Relación con el modelo causante
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relación con el modelo sujeto
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope para filtrar por nombre de log
     */
    public function scopeInLog($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope para filtrar por nivel
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope para filtrar por evento
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeCausedBy($query, $causer)
    {
        if ($causer instanceof Model) {
            return $query->where('causer_type', get_class($causer))
                        ->where('causer_id', $causer->id);
        }

        return $query->where('causer_type', $causer);
    }

    /**
     * Scope para filtrar por sujeto
     */
    public function scopeForSubject($query, $subject)
    {
        if ($subject instanceof Model) {
            return $query->where('subject_type', get_class($subject))
                        ->where('subject_id', $subject->id);
        }

        return $query->where('subject_type', $subject);
    }

    /**
     * Scope para filtrar por IP
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para filtrar por método HTTP
     */
    public function scopeHttpMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope para filtrar por código de estado
     */
    public function scopeStatusCode($query, int $code)
    {
        return $query->where('status_code', $code);
    }

    /**
     * Scope para filtrar por tiempo de ejecución
     */
    public function scopeSlowQueries($query, int $threshold = 1000)
    {
        return $query->where('execution_time', '>', $threshold);
    }

    /**
     * Scope para filtrar por uso de memoria
     */
    public function scopeHighMemoryUsage($query, int $threshold = 134217728) // 128MB
    {
        return $query->where('memory_usage', '>', $threshold);
    }

    /**
     * Obtener badge de nivel
     */
    public function getLevelBadgeAttribute(): string
    {
        $badges = [
            'debug' => 'badge-secondary',
            'info' => 'badge-info',
            'warning' => 'badge-warning',
            'error' => 'badge-danger',
            'critical' => 'badge-dark'
        ];

        return $badges[$this->level] ?? 'badge-secondary';
    }

    /**
     * Obtener badge de método HTTP
     */
    public function getMethodBadgeAttribute(): string
    {
        $badges = [
            'GET' => 'badge-success',
            'POST' => 'badge-primary',
            'PUT' => 'badge-warning',
            'PATCH' => 'badge-info',
            'DELETE' => 'badge-danger',
            'HEAD' => 'badge-secondary',
            'OPTIONS' => 'badge-secondary'
        ];

        return $badges[$this->method] ?? 'badge-secondary';
    }

    /**
     * Obtener badge de código de estado
     */
    public function getStatusCodeBadgeAttribute(): string
    {
        if ($this->status_code >= 200 && $this->status_code < 300) {
            return 'badge-success';
        } elseif ($this->status_code >= 300 && $this->status_code < 400) {
            return 'badge-info';
        } elseif ($this->status_code >= 400 && $this->status_code < 500) {
            return 'badge-warning';
        } elseif ($this->status_code >= 500) {
            return 'badge-danger';
        }

        return 'badge-secondary';
    }

    /**
     * Formatear tiempo de ejecución
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if (!$this->execution_time) {
            return 'N/A';
        }

        if ($this->execution_time < 1000) {
            return $this->execution_time . 'ms';
        }

        return number_format($this->execution_time / 1000, 2) . 's';
    }

    /**
     * Formatear uso de memoria
     */
    public function getFormattedMemoryUsageAttribute(): string
    {
        if (!$this->memory_usage) {
            return 'N/A';
        }

        $bytes = $this->memory_usage;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Crear log de actividad
     */
    public static function createLog(
        string $logName,
        string $description,
        string $level = 'info',
        ?string $event = null,
        $subject = null,
        $causer = null,
        ?array $properties = null,
        ?array $context = null
    ): self {
        $data = [
            'log_name' => $logName,
            'description' => $description,
            'level' => $level,
            'event' => $event,
            'properties' => $properties
        ];

        // Agregar información del sujeto
        if ($subject instanceof Model) {
            $data['subject_type'] = get_class($subject);
            $data['subject_id'] = $subject->id;
        }

        // Agregar información del causante
        if ($causer instanceof Model) {
            $data['causer_type'] = get_class($causer);
            $data['causer_id'] = $causer->id;
        }

        // Agregar contexto de la petición
        if ($context) {
            $data = array_merge($data, $context);
        }

        return self::create($data);
    }

    /**
     * Obtener estadísticas de logs
     */
    public static function getStats(?string $logName = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $logName ? self::inLog($logName) : self::query();

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $total = $query->count();
        $byLevel = $query->clone()->groupBy('level')
            ->selectRaw('level, count(*) as count')
            ->pluck('count', 'level')
            ->toArray();

        $byEvent = $query->clone()->whereNotNull('event')
            ->groupBy('event')
            ->selectRaw('event, count(*) as count')
            ->pluck('count', 'event')
            ->toArray();

        $byMethod = $query->clone()->whereNotNull('method')
            ->groupBy('method')
            ->selectRaw('method, count(*) as count')
            ->pluck('count', 'method')
            ->toArray();

        $byStatusCode = $query->clone()->whereNotNull('status_code')
            ->groupBy('status_code')
            ->selectRaw('status_code, count(*) as count')
            ->pluck('count', 'status_code')
            ->toArray();

        $avgExecutionTime = $query->clone()->whereNotNull('execution_time')
            ->avg('execution_time');

        $avgMemoryUsage = $query->clone()->whereNotNull('memory_usage')
            ->avg('memory_usage');

        $topIps = $query->clone()->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->selectRaw('ip_address, count(*) as count')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'ip_address')
            ->toArray();

        return [
            'total' => $total,
            'by_level' => $byLevel,
            'by_event' => $byEvent,
            'by_method' => $byMethod,
            'by_status_code' => $byStatusCode,
            'avg_execution_time' => $avgExecutionTime ? round($avgExecutionTime, 2) : null,
            'avg_memory_usage' => $avgMemoryUsage ? round($avgMemoryUsage, 2) : null,
            'top_ips' => $topIps,
            'period' => [
                'start' => $startDate?->format('Y-m-d H:i:s'),
                'end' => $endDate?->format('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Limpiar logs antiguos
     */
    public static function cleanOldLogs(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return self::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Obtener logs recientes
     */
    public static function getRecent(int $limit = 50, ?string $logName = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($logName) {
            $query->inLog($logName);
        }

        return $query->get();
    }
}
