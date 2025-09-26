<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\AppSetting;

class DynamicDriverService
{
    protected $supportedDrivers = [
        'cache' => ['file', 'redis', 'database', 'array'],
        'session' => ['file', 'database', 'redis', 'array'],
        'queue' => ['sync', 'database', 'redis', 'sqs'],
        'mail' => ['smtp', 'mailgun', 'ses', 'mail', 'sendmail', 'log'],
        'database' => ['mysql', 'pgsql', 'sqlite', 'sqlsrv']
    ];

    /**
     * Obtiene los drivers soportados para un servicio específico
     */
    public function getSupportedDrivers(string $service): array
    {
        return $this->supportedDrivers[$service] ?? [];
    }

    /**
     * Cambia el driver de un servicio dinámicamente
     */
    public function changeDriver(string $service, string $driver, array $config = []): bool
    {
        try {
            // Validar que el driver sea soportado
            if (!in_array($driver, $this->getSupportedDrivers($service))) {
                throw new \InvalidArgumentException("Driver '{$driver}' no soportado para el servicio '{$service}'");
            }

            // Obtener configuración actual
            $currentConfig = Config::get($service);

            // Aplicar nueva configuración
            $newConfig = array_merge($currentConfig, [
                'default' => $driver,
                'connections' => array_merge(
                    $currentConfig['connections'] ?? [],
                    $config
                )
            ]);

            // Actualizar configuración
            Config::set($service, $newConfig);

            // Guardar en base de datos
            $this->saveDriverConfig($service, $driver, $config);

            // Limpiar cache
            Cache::forget("driver_config_{$service}");

            Log::info("Driver cambiado exitosamente", [
                'service' => $service,
                'driver' => $driver,
                'config' => $config
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Error al cambiar driver", [
                'service' => $service,
                'driver' => $driver,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Obtiene la configuración actual del driver
     */
    public function getCurrentDriver(string $service): ?string
    {
        return Config::get("{$service}.default");
    }

    /**
     * Obtiene la configuración completa del driver
     */
    public function getDriverConfig(string $service): array
    {
        return Cache::remember("driver_config_{$service}", 3600, function () use ($service) {
            $setting = AppSetting::where('key', "driver_config_{$service}")->first();
            return $setting ? json_decode($setting->value, true) : [];
        });
    }

    /**
     * Guarda la configuración del driver en base de datos
     */
    protected function saveDriverConfig(string $service, string $driver, array $config): void
    {
        $configData = [
            'driver' => $driver,
            'config' => $config,
            'updated_at' => now()->toISOString()
        ];

        AppSetting::updateOrCreate(
            ['key' => "driver_config_{$service}"],
            ['value' => json_encode($configData)]
        );
    }

    /**
     * Restaura la configuración desde base de datos
     */
    public function restoreDriverConfig(string $service): bool
    {
        try {
            $config = $this->getDriverConfig($service);

            if (empty($config)) {
                return false;
            }

            $driver = $config['driver'] ?? null;
            $driverConfig = $config['config'] ?? [];

            if ($driver) {
                return $this->changeDriver($service, $driver, $driverConfig);
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Error al restaurar configuración del driver", [
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Obtiene el estado de todos los drivers
     */
    public function getAllDriversStatus(): array
    {
        $status = [];

        foreach (array_keys($this->supportedDrivers) as $service) {
            $status[$service] = [
                'current' => $this->getCurrentDriver($service),
                'supported' => $this->getSupportedDrivers($service),
                'config' => $this->getDriverConfig($service)
            ];
        }

        return $status;
    }

    /**
     * Valida la configuración de un driver
     */
    public function validateDriverConfig(string $service, string $driver, array $config): array
    {
        $errors = [];

        switch ($service) {
            case 'mail':
                if ($driver === 'smtp') {
                    if (empty($config['host'])) {
                        $errors[] = 'Host SMTP es requerido';
                    }
                    if (empty($config['port'])) {
                        $errors[] = 'Puerto SMTP es requerido';
                    }
                }
                break;

            case 'database':
                if (empty($config['host'])) {
                    $errors[] = 'Host de base de datos es requerido';
                }
                if (empty($config['database'])) {
                    $errors[] = 'Nombre de base de datos es requerido';
                }
                break;

            case 'cache':
            case 'session':
                if ($driver === 'redis' && empty($config['host'])) {
                    $errors[] = 'Host Redis es requerido';
                }
                break;
        }

        return $errors;
    }

    /**
     * Reinicia servicios después del cambio de driver
     */
    public function restartServices(array $services = []): bool
    {
        try {
            if (empty($services)) {
                $services = array_keys($this->supportedDrivers);
            }

            foreach ($services as $service) {
                switch ($service) {
                    case 'cache':
                        Cache::flush();
                        break;
                    case 'session':
                        // Limpiar sesiones actuales
                        break;
                    case 'queue':
                        // Reiniciar workers de cola
                        break;
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Error al reiniciar servicios", [
                'services' => $services,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}



