<?php

namespace App\Services;

use App\Models\SmtpConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmtpConfigService
{
    protected $currentConfig;

    public function __construct()
    {
        $this->currentConfig = null;
    }

    /**
     * Aplicar configuración SMTP dinámica
     */
    public function applyDynamicConfig(?SmtpConfig $config = null): bool
    {
        try {
            if (!$config) {
                $config = SmtpConfig::getActiveDefault();
            }

            if (!$config) {
                Log::warning('No hay configuración SMTP activa disponible');
                return false;
            }

            // Validar configuración antes de aplicar
            $validation = $config->validate();
            if (!$validation['valid']) {
                Log::error('Configuración SMTP inválida: ' . implode(', ', $validation['errors']));
                return false;
            }

            // Aplicar configuración a Laravel
            $laravelConfig = $config->toLaravelConfig();

            // Actualizar configuración de mail
            Config::set('mail.default', $config->mailer);
            Config::set("mail.mailers.{$config->mailer}", $laravelConfig);
            Config::set('mail.from', [
                'address' => $config->from_address,
                'name' => $config->from_name
            ]);

            $this->currentConfig = $config;

            Log::info("Configuración SMTP aplicada: {$config->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error aplicando configuración SMTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener configuración actual
     */
    public function getCurrentConfig(): ?SmtpConfig
    {
        return $this->currentConfig;
    }

    /**
     * Probar configuración SMTP
     */
    public function testConfiguration(SmtpConfig $config): array
    {
        $result = [
            'success' => false,
            'error' => null,
            'details' => [],
            'test_email_sent' => false
        ];

        try {
            // Aplicar configuración temporalmente
            $originalConfig = $this->backupCurrentConfig();
            $this->applyDynamicConfig($config);

            // Probar conexión básica
            $connectionTest = $config->testConnection();
            if (!$connectionTest['success']) {
                $result['error'] = $connectionTest['error'];
                $this->restoreConfig($originalConfig);
                return $result;
            }

            $result['details'] = $connectionTest['details'];

            // Probar envío de email de prueba
            try {
                $testResult = $this->sendTestEmail($config);
                $result['test_email_sent'] = $testResult;
                $result['success'] = true;

            } catch (\Exception $e) {
                $result['error'] = "Error enviando email de prueba: " . $e->getMessage();
            }

            // Restaurar configuración original
            $this->restoreConfig($originalConfig);

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Enviar email de prueba
     */
    protected function sendTestEmail(SmtpConfig $config): bool
    {
        try {
            Mail::raw('Este es un email de prueba para verificar la configuración SMTP.', function ($message) use ($config) {
                $message->to($config->from_address)
                        ->subject('Test SMTP - ' . config('app.name'))
                        ->from($config->from_address, $config->from_name);
            });

            return true;

        } catch (\Exception $e) {
            Log::error("Error enviando email de prueba: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear configuración desde formulario
     */
    public function createFromForm(array $data, ?int $createdBy = null): SmtpConfig
    {
        $config = SmtpConfig::create([
            'name' => $data['name'],
            'mailer' => $data['mailer'] ?? 'smtp',
            'host' => $data['host'] ?? null,
            'port' => $data['port'] ?? null,
            'encryption' => $data['encryption'] ?? null,
            'username' => $data['username'] ?? null,
            'password' => $data['password'] ?? null,
            'timeout' => $data['timeout'] ?? null,
            'local_domain' => $data['local_domain'] ?? null,
            'from_address' => $data['from_address'],
            'from_name' => $data['from_name'],
            'description' => $data['description'] ?? null,
            'created_by' => $createdBy,
            'is_active' => true
        ]);

        Log::info("Configuración SMTP creada: {$config->name}");
        return $config;
    }

    /**
     * Actualizar configuración
     */
    public function updateConfiguration(SmtpConfig $config, array $data): bool
    {
        try {
            $config->update([
                'name' => $data['name'],
                'mailer' => $data['mailer'] ?? $config->mailer,
                'host' => $data['host'] ?? $config->host,
                'port' => $data['port'] ?? $config->port,
                'encryption' => $data['encryption'] ?? $config->encryption,
                'username' => $data['username'] ?? $config->username,
                'password' => $data['password'] ?? null, // Solo actualizar si se proporciona
                'timeout' => $data['timeout'] ?? $config->timeout,
                'local_domain' => $data['local_domain'] ?? $config->local_domain,
                'from_address' => $data['from_address'],
                'from_name' => $data['from_name'],
                'description' => $data['description'] ?? $config->description
            ]);

            // Limpiar cache
            Cache::forget('smtp_config_default');

            Log::info("Configuración SMTP actualizada: {$config->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error actualizando configuración SMTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Establecer configuración por defecto
     */
    public function setAsDefault(SmtpConfig $config): bool
    {
        try {
            $result = $config->setAsDefault();

            if ($result) {
                Log::info("Configuración SMTP establecida como por defecto: {$config->name}");
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Error estableciendo configuración por defecto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activar/desactivar configuración
     */
    public function toggleActive(SmtpConfig $config): bool
    {
        try {
            if ($config->is_active) {
                $result = $config->deactivate();
                $status = 'desactivada';
            } else {
                $result = $config->activate();
                $status = 'activada';
            }

            if ($result) {
                Log::info("Configuración SMTP {$status}: {$config->name}");
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Error cambiando estado de configuración SMTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar configuración
     */
    public function deleteConfiguration(SmtpConfig $config): bool
    {
        try {
            // No permitir eliminar la configuración por defecto
            if ($config->is_default) {
                throw new \Exception('No se puede eliminar la configuración por defecto');
            }

            $name = $config->name;
            $config->delete();

            // Limpiar cache
            Cache::forget('smtp_config_default');

            Log::info("Configuración SMTP eliminada: {$name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error eliminando configuración SMTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener configuraciones disponibles
     */
    public function getAvailableConfigurations(): array
    {
        $configs = SmtpConfig::active()->get();

        return $configs->map(function ($config) {
            return [
                'id' => $config->id,
                'name' => $config->name,
                'mailer' => $config->mailer,
                'host' => $config->host,
                'from_address' => $config->from_address,
                'from_name' => $config->from_name,
                'is_default' => $config->is_default,
                'status_badge' => $config->status_badge,
                'mailer_badge' => $config->mailer_badge
            ];
        })->toArray();
    }

    /**
     * Obtener estadísticas del sistema
     */
    public function getSystemStatistics(): array
    {
        return [
            'smtp_configs' => SmtpConfig::getStatistics(),
            'current_config' => $this->currentConfig ? $this->currentConfig->name : null,
            'predefined_configs' => array_keys(SmtpConfig::getPredefinedConfigs()),
            'cache_status' => Cache::has('smtp_config_default') ? 'Cached' : 'Not cached'
        ];
    }

    /**
     * Crear configuración predefinida
     */
    public function createPredefinedConfiguration(string $type, array $credentials = [], ?int $createdBy = null): SmtpConfig
    {
        $config = SmtpConfig::createPredefined($type, $credentials, $createdBy);

        Log::info("Configuración SMTP predefinida creada: {$config->name} (tipo: {$type})");

        return $config;
    }

    /**
     * Backup de configuración actual
     */
    protected function backupCurrentConfig(): array
    {
        return [
            'default' => Config::get('mail.default'),
            'mailers' => Config::get('mail.mailers'),
            'from' => Config::get('mail.from')
        ];
    }

    /**
     * Restaurar configuración
     */
    protected function restoreConfig(array $backup): void
    {
        Config::set('mail.default', $backup['default']);
        Config::set('mail.mailers', $backup['mailers']);
        Config::set('mail.from', $backup['from']);
    }

    /**
     * Validar configuración completa
     */
    public function validateConfiguration(SmtpConfig $config): array
    {
        $validation = $config->validate();

        if ($validation['valid']) {
            // Probar conexión si es válida
            $testResult = $this->testConfiguration($config);
            $validation['connection_test'] = $testResult;
        }

        return $validation;
    }

    /**
     * Obtener configuraciones por tipo de mailer
     */
    public function getConfigurationsByMailer(string $mailer): array
    {
        return SmtpConfig::active()
            ->byMailer($mailer)
            ->get()
            ->map(function ($config) {
                return $config->connection_info;
            })
            ->toArray();
    }

    /**
     * Migrar configuración desde .env
     */
    public function migrateFromEnv(): ?SmtpConfig
    {
        try {
            $envConfig = [
                'name' => 'Configuración desde .env',
                'mailer' => config('mail.default', 'smtp'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'password' => config('mail.mailers.smtp.password'),
                'timeout' => config('mail.mailers.smtp.timeout'),
                'local_domain' => config('mail.mailers.smtp.local_domain'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'description' => 'Configuración migrada desde archivo .env'
            ];

            // Solo crear si hay configuración válida
            if (!empty($envConfig['host']) && !empty($envConfig['from_address'])) {
                $config = SmtpConfig::createFromArray($envConfig, $envConfig['name']);

                Log::info("Configuración SMTP migrada desde .env: {$config->name}");
                return $config;
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Error migrando configuración desde .env: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Limpiar cache de configuraciones
     */
    public function clearCache(): void
    {
        Cache::forget('smtp_config_default');
        Log::info('Cache de configuraciones SMTP limpiado');
    }
}
