<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SmtpConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'timeout',
        'local_domain',
        'from_address',
        'from_name',
        'is_active',
        'is_default',
        'settings',
        'description',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Tipos de mailer soportados
     */
    const MAILER_SMTP = 'smtp';
    const MAILER_SENDMAIL = 'sendmail';
    const MAILER_MAILGUN = 'mailgun';
    const MAILER_SES = 'ses';
    const MAILER_POSTMARK = 'postmark';
    const MAILER_RESEND = 'resend';

    /**
     * Tipos de encriptación
     */
    const ENCRYPTION_NONE = null;
    const ENCRYPTION_TLS = 'tls';
    const ENCRYPTION_SSL = 'ssl';

    /**
     * Scope para configuraciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para configuración por defecto
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para configuraciones por mailer
     */
    public function scopeByMailer($query, $mailer)
    {
        return $query->where('mailer', $mailer);
    }

    /**
     * Mutator para encriptar contraseña
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Accessor para desencriptar contraseña
     */
    public function getPasswordAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Obtener configuración activa por defecto
     */
    public static function getActiveDefault(): ?self
    {
        return Cache::remember('smtp_config_default', 3600, function () {
            return self::active()->default()->first();
        });
    }

    /**
     * Obtener configuración por nombre
     */
    public static function getByName(string $name): ?self
    {
        return self::active()->where('name', $name)->first();
    }

    /**
     * Establecer como configuración por defecto
     */
    public function setAsDefault(): bool
    {
        // Quitar el flag de default de todas las configuraciones
        self::where('is_default', true)->update(['is_default' => false]);

        // Establecer esta como default
        $this->is_default = true;
        $this->is_active = true;

        $result = $this->save();

        // Limpiar cache
        Cache::forget('smtp_config_default');

        return $result;
    }

    /**
     * Activar configuración
     */
    public function activate(): bool
    {
        $this->is_active = true;
        $result = $this->save();

        // Limpiar cache
        Cache::forget('smtp_config_default');

        return $result;
    }

    /**
     * Desactivar configuración
     */
    public function deactivate(): bool
    {
        $this->is_active = false;

        // Si es la configuración por defecto, quitar el flag
        if ($this->is_default) {
            $this->is_default = false;
        }

        $result = $this->save();

        // Limpiar cache
        Cache::forget('smtp_config_default');

        return $result;
    }

    /**
     * Probar configuración SMTP
     */
    public function testConnection(): array
    {
        $result = [
            'success' => false,
            'error' => null,
            'details' => []
        ];

        try {
            // Crear configuración temporal de Laravel
            $config = [
                'driver' => $this->mailer,
                'host' => $this->host,
                'port' => $this->port,
                'encryption' => $this->encryption,
                'username' => $this->username,
                'password' => $this->password,
                'timeout' => $this->timeout,
                'local_domain' => $this->local_domain,
                'from' => [
                    'address' => $this->from_address,
                    'name' => $this->from_name
                ]
            ];

            // Probar conexión SMTP
            $transport = \Symfony\Component\Mailer\Transport\Dsn::fromString(
                "smtp://{$this->username}:{$this->password}@{$this->host}:{$this->port}"
            );

            $result['success'] = true;
            $result['details'] = [
                'host' => $this->host,
                'port' => $this->port,
                'encryption' => $this->encryption,
                'username' => $this->username,
                'from_address' => $this->from_address,
                'from_name' => $this->from_name
            ];

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Obtener array de configuración para Laravel
     */
    public function toLaravelConfig(): array
    {
        return [
            'transport' => $this->mailer,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => $this->timeout,
            'local_domain' => $this->local_domain,
        ];
    }

    /**
     * Crear configuración desde array
     */
    public static function createFromArray(array $config, string $name, ?int $createdBy = null): self
    {
        return self::create([
            'name' => $name,
            'mailer' => $config['mailer'] ?? 'smtp',
            'host' => $config['host'],
            'port' => $config['port'],
            'encryption' => $config['encryption'] ?? null,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'timeout' => $config['timeout'] ?? null,
            'local_domain' => $config['local_domain'] ?? null,
            'from_address' => $config['from_address'],
            'from_name' => $config['from_name'],
            'settings' => $config['settings'] ?? null,
            'description' => $config['description'] ?? null,
            'created_by' => $createdBy,
            'is_active' => true
        ]);
    }

    /**
     * Obtener configuraciones predefinidas
     */
    public static function getPredefinedConfigs(): array
    {
        return [
            'gmail' => [
                'name' => 'Gmail',
                'mailer' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Configuración para Gmail (requiere contraseña de aplicación)'
            ],
            'outlook' => [
                'name' => 'Outlook/Hotmail',
                'mailer' => 'smtp',
                'host' => 'smtp-mail.outlook.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Configuración para Outlook/Hotmail'
            ],
            'yahoo' => [
                'name' => 'Yahoo Mail',
                'mailer' => 'smtp',
                'host' => 'smtp.mail.yahoo.com',
                'port' => 587,
                'encryption' => 'tls',
                'description' => 'Configuración para Yahoo Mail'
            ],
            'mailtrap' => [
                'name' => 'Mailtrap',
                'mailer' => 'smtp',
                'host' => 'sandbox.smtp.mailtrap.io',
                'port' => 2525,
                'encryption' => null,
                'description' => 'Configuración para Mailtrap (testing)'
            ],
            'sendmail' => [
                'name' => 'Sendmail',
                'mailer' => 'sendmail',
                'host' => null,
                'port' => null,
                'encryption' => null,
                'description' => 'Configuración para Sendmail (servidor local)'
            ]
        ];
    }

    /**
     * Crear configuración predefinida
     */
    public static function createPredefined(string $type, array $credentials = [], ?int $createdBy = null): self
    {
        $predefined = self::getPredefinedConfigs()[$type] ?? null;

        if (!$predefined) {
            throw new \InvalidArgumentException("Tipo de configuración predefinida no válido: {$type}");
        }

        $config = array_merge($predefined, $credentials);

        return self::createFromArray($config, $config['name'], $createdBy);
    }

    /**
     * Obtener estadísticas de configuración
     */
    public static function getStatistics(): array
    {
        return [
            'total_configs' => self::count(),
            'active_configs' => self::active()->count(),
            'inactive_configs' => self::where('is_active', false)->count(),
            'default_config' => self::getActiveDefault()?->name,
            'mailer_types' => self::select('mailer')
                ->distinct()
                ->pluck('mailer')
                ->toArray(),
            'last_created' => self::latest()->first()?->created_at,
            'last_updated' => self::latest('updated_at')->first()?->updated_at
        ];
    }

    /**
     * Validar configuración
     */
    public function validate(): array
    {
        $errors = [];
        $warnings = [];

        // Validaciones requeridas
        if (empty($this->host) && $this->mailer === 'smtp') {
            $errors[] = 'Host es requerido para SMTP';
        }

        if (empty($this->port) && $this->mailer === 'smtp') {
            $errors[] = 'Puerto es requerido para SMTP';
        }

        if (empty($this->from_address)) {
            $errors[] = 'Dirección de remitente es requerida';
        }

        if (empty($this->from_name)) {
            $warnings[] = 'Nombre de remitente no especificado';
        }

        // Validaciones específicas por mailer
        if ($this->mailer === 'smtp') {
            if (empty($this->username)) {
                $warnings[] = 'Usuario SMTP no especificado (puede ser necesario para autenticación)';
            }

            if (empty($this->password)) {
                $warnings[] = 'Contraseña SMTP no especificada (puede ser necesario para autenticación)';
            }

            if (!in_array($this->encryption, [null, 'tls', 'ssl'])) {
                $errors[] = 'Tipo de encriptación inválido';
            }

            if ($this->port < 1 || $this->port > 65535) {
                $errors[] = 'Puerto inválido';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Obtener badge de estado
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_default) {
            return '<span class="badge badge-success">Por Defecto</span>';
        } elseif ($this->is_active) {
            return '<span class="badge badge-primary">Activa</span>';
        } else {
            return '<span class="badge badge-secondary">Inactiva</span>';
        }
    }

    /**
     * Obtener badge de mailer
     */
    public function getMailerBadgeAttribute(): string
    {
        $colors = [
            'smtp' => 'primary',
            'sendmail' => 'info',
            'mailgun' => 'warning',
            'ses' => 'success',
            'postmark' => 'danger',
            'resend' => 'secondary'
        ];

        $color = $colors[$this->mailer] ?? 'secondary';
        $name = strtoupper($this->mailer);

        return "<span class=\"badge badge-{$color}\">{$name}</span>";
    }

    /**
     * Obtener información de conexión (sin contraseña)
     */
    public function getConnectionInfoAttribute(): array
    {
        return [
            'name' => $this->name,
            'mailer' => $this->mailer,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
