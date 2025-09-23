<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'icon',
        'url',
        'action_text',
        'data',
        'is_read',
        'is_push_sent',
        'read_at',
        'expires_at',
        'user_id',
        'created_by'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'is_push_sent' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Relación con el usuario destinatario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el usuario creador
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope para notificaciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope para notificaciones leídas
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope para notificaciones por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para notificaciones no expiradas
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope para notificaciones de un usuario específico
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id');
        });
    }

    /**
     * Scope para notificaciones push no enviadas
     */
    public function scopePendingPush($query)
    {
        return $query->where('is_push_sent', false);
    }

    /**
     * Marcar como leída
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Marcar como enviada por push
     */
    public function markAsPushSent(): bool
    {
        return $this->update([
            'is_push_sent' => true
        ]);
    }

    /**
     * Verificar si está expirada
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verificar si puede ser enviada por push
     */
    public function canBePushed(): bool
    {
        return !$this->is_push_sent && !$this->isExpired();
    }

    /**
     * Obtener badge de tipo
     */
    public function getTypeBadgeAttribute(): string
    {
        $badges = [
            'info' => 'badge-info',
            'success' => 'badge-success',
            'warning' => 'badge-warning',
            'error' => 'badge-danger'
        ];

        return $badges[$this->type] ?? 'badge-secondary';
    }

    /**
     * Obtener icono por defecto según tipo
     */
    public function getDefaultIconAttribute(): string
    {
        $icons = [
            'info' => 'fas fa-info-circle',
            'success' => 'fas fa-check-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'error' => 'fas fa-times-circle'
        ];

        return $this->icon ?: ($icons[$this->type] ?? 'fas fa-bell');
    }

    /**
     * Crear notificación del sistema
     */
    public static function createSystemNotification(
        string $title,
        string $message,
        string $type = 'info',
        ?string $icon = null,
        ?string $url = null,
        ?string $actionText = null,
        ?array $data = null,
        ?int $userId = null,
        ?int $expiresInHours = null
    ): self {
        return self::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'icon' => $icon,
            'url' => $url,
            'action_text' => $actionText,
            'data' => $data,
            'user_id' => $userId,
            'created_by' => null, // Sistema
            'expires_at' => $expiresInHours ? now()->addHours($expiresInHours) : null
        ]);
    }

    /**
     * Crear notificación para un usuario
     */
    public static function createForUser(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $icon = null,
        ?string $url = null,
        ?string $actionText = null,
        ?array $data = null,
        ?int $createdBy = null,
        ?int $expiresInHours = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'icon' => $icon,
            'url' => $url,
            'action_text' => $actionText,
            'data' => $data,
            'created_by' => $createdBy,
            'expires_at' => $expiresInHours ? now()->addHours($expiresInHours) : null
        ]);
    }

    /**
     * Crear notificación global
     */
    public static function createGlobal(
        string $title,
        string $message,
        string $type = 'info',
        ?string $icon = null,
        ?string $url = null,
        ?string $actionText = null,
        ?array $data = null,
        ?int $createdBy = null,
        ?int $expiresInHours = null
    ): self {
        return self::create([
            'user_id' => null, // Global
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'icon' => $icon,
            'url' => $url,
            'action_text' => $actionText,
            'data' => $data,
            'created_by' => $createdBy,
            'expires_at' => $expiresInHours ? now()->addHours($expiresInHours) : null
        ]);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public static function getStats(?int $userId = null): array
    {
        $query = $userId ? self::forUser($userId) : self::query();

        return [
            'total' => $query->count(),
            'unread' => $query->clone()->unread()->count(),
            'read' => $query->clone()->read()->count(),
            'pending_push' => $query->clone()->pendingPush()->count(),
            'expired' => $query->clone()->where('expires_at', '<', now())->count(),
            'by_type' => $query->clone()->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->pluck('count', 'type')
                ->toArray()
        ];
    }
}
