<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AllowedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'type',
        'description',
        'status',
        'created_by',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Tipos de IP permitidos
     */
    const TYPE_SPECIFIC = 'specific';
    const TYPE_CIDR = 'cidr';
    const TYPE_BLOCKED = 'blocked';

    /**
     * Estados de IP
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_EXPIRED = 'expired';

    /**
     * Scope para IPs activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    /**
     * Scope para IPs específicas
     */
    public function scopeSpecific($query)
    {
        return $query->where('type', self::TYPE_SPECIFIC);
    }

    /**
     * Scope para rangos CIDR
     */
    public function scopeCidr($query)
    {
        return $query->where('type', self::TYPE_CIDR);
    }

    /**
     * Scope para IPs bloqueadas
     */
    public function scopeBlocked($query)
    {
        return $query->where('type', self::TYPE_BLOCKED);
    }

    /**
     * Scope para IPs expiradas
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    /**
     * Verificar si la IP está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Verificar si la IP ha expirado
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Obtener todas las IPs permitidas (específicas y CIDR)
     */
    public static function getAllowedIps(): array
    {
        return self::active()
            ->whereIn('type', [self::TYPE_SPECIFIC, self::TYPE_CIDR])
            ->pluck('ip_address', 'id')
            ->toArray();
    }

    /**
     * Obtener todas las IPs bloqueadas
     */
    public static function getBlockedIps(): array
    {
        return self::active()
            ->blocked()
            ->pluck('ip_address', 'id')
            ->toArray();
    }

    /**
     * Verificar si una IP está permitida
     */
    public static function isIpAllowed(string $ip): bool
    {
        // Verificar IPs específicas
        if (self::active()->specific()->where('ip_address', $ip)->exists()) {
            return true;
        }

        // Verificar rangos CIDR
        $cidrRanges = self::active()->cidr()->pluck('ip_address');
        foreach ($cidrRanges as $range) {
            if (self::ipInRange($ip, $range)) {
                return true;
            }
        }

        // Verificar si está bloqueada
        if (self::active()->blocked()->where('ip_address', $ip)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si una IP está en un rango CIDR
     */
    public static function ipInRange(string $ip, string $range): bool
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
            return self::ipv4InRange($ip, $subnet, (int)$bits);
        }

        // Para IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::ipv6InRange($ip, $subnet, (int)$bits);
        }

        return false;
    }

    /**
     * Verificar si una IPv4 está en un rango CIDR
     */
    private static function ipv4InRange(string $ip, string $subnet, int $bits): bool
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
    private static function ipv6InRange(string $ip, string $subnet, int $bits): bool
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
     * Validar formato de IP
     */
    public static function validateIpFormat(string $ip, string $type = 'specific'): bool
    {
        if ($type === self::TYPE_CIDR) {
            // Validar formato CIDR
            if (strpos($ip, '/') === false) {
                return false;
            }

            list($subnet, $bits) = explode('/', $ip);

            if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
                return false;
            }

            $bits = (int)$bits;
            $maxBits = filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 32 : 128;

            return $bits >= 0 && $bits <= $maxBits;
        }

        // Validar IP específica
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Obtener estadísticas de IPs
     */
    public static function getStats(): array
    {
        return [
            'total_ips' => self::count(),
            'active_ips' => self::active()->count(),
            'expired_ips' => self::expired()->count(),
            'specific_ips' => self::active()->specific()->count(),
            'cidr_ranges' => self::active()->cidr()->count(),
            'blocked_ips' => self::active()->blocked()->count(),
        ];
    }

    /**
     * Limpiar IPs expiradas
     */
    public static function cleanupExpiredIps(): int
    {
        $expiredCount = self::expired()->count();
        self::expired()->update(['status' => self::STATUS_EXPIRED]);

        return $expiredCount;
    }

    /**
     * Relación con el usuario que creó la entrada
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtener el nombre del tipo
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SPECIFIC => 'IP Específica',
            self::TYPE_CIDR => 'Rango CIDR',
            self::TYPE_BLOCKED => 'IP Bloqueada',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener el estado formateado
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Activa',
            self::STATUS_INACTIVE => 'Inactiva',
            self::STATUS_EXPIRED => 'Expirada',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener badge de estado
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => '<span class="badge badge-success">Activa</span>',
            self::STATUS_INACTIVE => '<span class="badge badge-secondary">Inactiva</span>',
            self::STATUS_EXPIRED => '<span class="badge badge-warning">Expirada</span>',
            default => '<span class="badge badge-dark">Desconocido</span>'
        };
    }

    /**
     * Obtener badge de tipo
     */
    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SPECIFIC => '<span class="badge badge-info">IP Específica</span>',
            self::TYPE_CIDR => '<span class="badge badge-primary">Rango CIDR</span>',
            self::TYPE_BLOCKED => '<span class="badge badge-danger">Bloqueada</span>',
            default => '<span class="badge badge-dark">Desconocido</span>'
        };
    }
}



