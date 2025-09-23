<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'storage_type',
        'file_path',
        'file_name',
        'file_size',
        'file_hash',
        'options',
        'description',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
        'execution_time',
        'is_encrypted',
        'is_compressed',
        'retention_days',
        'expires_at',
        'created_by'
    ];

    protected $casts = [
        'options' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_encrypted' => 'boolean',
        'is_compressed' => 'boolean'
    ];

    /**
     * Relación con el usuario creador
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para filtrar por tipo de almacenamiento
     */
    public function scopeStorageType($query, string $storageType)
    {
        return $query->where('storage_type', $storageType);
    }

    /**
     * Scope para filtrar backups completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para filtrar backups fallidos
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para filtrar backups en progreso
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope para filtrar backups expirados
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope para filtrar backups no expirados
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Obtener badge de estado
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => 'badge-secondary',
            'in_progress' => 'badge-warning',
            'completed' => 'badge-success',
            'failed' => 'badge-danger'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    /**
     * Obtener badge de tipo
     */
    public function getTypeBadgeAttribute(): string
    {
        $badges = [
            'full' => 'badge-primary',
            'database' => 'badge-info',
            'files' => 'badge-success',
            'incremental' => 'badge-warning'
        ];

        return $badges[$this->type] ?? 'badge-secondary';
    }

    /**
     * Formatear tamaño del archivo
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Formatear tiempo de ejecución
     */
    public function getFormattedExecutionTimeAttribute(): string
    {
        if (!$this->execution_time) {
            return 'N/A';
        }

        if ($this->execution_time < 60) {
            return $this->execution_time . 's';
        }

        $minutes = floor($this->execution_time / 60);
        $seconds = $this->execution_time % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Verificar si el backup está expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar si el archivo existe
     */
    public function fileExists(): bool
    {
        if (!$this->file_path) {
            return false;
        }

        return Storage::disk($this->storage_type)->exists($this->file_path);
    }

    /**
     * Obtener URL del archivo
     */
    public function getFileUrl(): ?string
    {
        if (!$this->file_path || !$this->fileExists()) {
            return null;
        }

        return Storage::disk($this->storage_type)->url($this->file_path);
    }

    /**
     * Marcar como en progreso
     */
    public function markAsInProgress(): bool
    {
        return $this->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);
    }

    /**
     * Marcar como completado
     */
    public function markAsCompleted(string $filePath, int $fileSize, ?string $fileHash = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'file_hash' => $fileHash,
            'completed_at' => now(),
            'execution_time' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'expires_at' => now()->addDays($this->retention_days)
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'execution_time' => $this->started_at ? now()->diffInSeconds($this->started_at) : null
        ]);
    }

    /**
     * Crear backup programado
     */
    public static function createScheduled(
        string $name,
        string $type,
        array $options = [],
        ?string $description = null,
        ?int $createdBy = null,
        int $retentionDays = 30
    ): self {
        return self::create([
            'name' => $name,
            'type' => $type,
            'status' => 'pending',
            'storage_type' => $options['storage_type'] ?? 'local',
            'file_name' => self::generateFileName($name, $type),
            'options' => $options,
            'description' => $description,
            'is_encrypted' => $options['encrypt'] ?? false,
            'is_compressed' => $options['compress'] ?? true,
            'retention_days' => $retentionDays,
            'created_by' => $createdBy
        ]);
    }

    /**
     * Crear backup manual
     */
    public static function createManual(
        string $name,
        string $type,
        array $options = [],
        ?string $description = null,
        ?int $createdBy = null
    ): self {
        return self::create([
            'name' => $name,
            'type' => $type,
            'status' => 'pending',
            'storage_type' => $options['storage_type'] ?? 'local',
            'file_name' => self::generateFileName($name, $type),
            'options' => $options,
            'description' => $description,
            'is_encrypted' => $options['encrypt'] ?? false,
            'is_compressed' => $options['compress'] ?? true,
            'retention_days' => $options['retention_days'] ?? 30,
            'created_by' => $createdBy
        ]);
    }

    /**
     * Generar nombre de archivo único
     */
    protected static function generateFileName(string $name, string $type): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $extension = self::getFileExtension($type);

        return "{$name}_{$type}_{$timestamp}.{$extension}";
    }

    /**
     * Obtener extensión de archivo según tipo
     */
    protected static function getFileExtension(string $type): string
    {
        $extensions = [
            'full' => 'tar.gz',
            'database' => 'sql',
            'files' => 'tar.gz',
            'incremental' => 'tar.gz'
        ];

        return $extensions[$type] ?? 'zip';
    }

    /**
     * Obtener estadísticas de backups
     */
    public static function getStats(?string $type = null, ?int $days = 30): array
    {
        $query = $type ? self::ofType($type) : self::query();

        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $total = $query->count();
        $completed = $query->clone()->completed()->count();
        $failed = $query->clone()->failed()->count();
        $inProgress = $query->clone()->inProgress()->count();
        $expired = $query->clone()->expired()->count();

        $byType = $query->clone()->groupBy('type')
            ->selectRaw('type, count(*) as count')
            ->pluck('count', 'type')
            ->toArray();

        $totalSize = $query->clone()->completed()->sum('file_size');

        return [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'in_progress' => $inProgress,
            'expired' => $expired,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'by_type' => $byType,
            'total_size' => $totalSize,
            'formatted_total_size' => self::formatBytes($totalSize),
            'period_days' => $days
        ];
    }

    /**
     * Formatear bytes a formato legible
     */
    protected static function formatBytes(int $bytes): string
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Limpiar backups expirados
     */
    public static function cleanExpiredBackups(): int
    {
        $expiredBackups = self::expired()->get();
        $deletedCount = 0;

        foreach ($expiredBackups as $backup) {
            if ($backup->fileExists()) {
                Storage::disk($backup->storage_type)->delete($backup->file_path);
            }
            $backup->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Obtener backups recientes
     */
    public static function getRecent(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('creator')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
