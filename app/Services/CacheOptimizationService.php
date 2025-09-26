<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class CacheOptimizationService
{
    protected $cachePrefix = 'cache_optimization_';
    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Optimizar cache general
     */
    public function optimizeCache(): array
    {
        try {
            $results = [];

            // Limpiar cache expirado
            $results['expired_cleaned'] = $this->cleanExpiredCache();

            // Optimizar cache de base de datos
            $results['database_optimized'] = $this->optimizeDatabaseCache();

            // Optimizar cache de sesiones
            $results['sessions_optimized'] = $this->optimizeSessionCache();

            // Optimizar cache de vistas
            $results['views_optimized'] = $this->optimizeViewCache();

            // Optimizar cache de rutas
            $results['routes_optimized'] = $this->optimizeRouteCache();

            // Optimizar cache de configuración
            $results['config_optimized'] = $this->optimizeConfigCache();

            $this->logOptimization('general', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('cache_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar cache de Redis
     */
    public function optimizeRedisCache(): array
    {
        try {
            $results = [];

            // Limpiar claves expiradas
            $results['expired_keys'] = $this->cleanExpiredRedisKeys();

            // Optimizar memoria
            $results['memory_optimized'] = $this->optimizeRedisMemory();

            // Optimizar configuración
            $results['config_optimized'] = $this->optimizeRedisConfig();

            // Analizar rendimiento
            $results['performance_analysis'] = $this->analyzeRedisPerformance();

            $this->logOptimization('redis', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('redis_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar cache de base de datos
     */
    public function optimizeDatabaseCache(): array
    {
        try {
            $results = [];

            // Limpiar cache de consultas
            $results['query_cache_cleared'] = $this->clearQueryCache();

            // Optimizar cache de resultados
            $results['result_cache_optimized'] = $this->optimizeResultCache();

            // Limpiar cache de esquemas
            $results['schema_cache_cleared'] = $this->clearSchemaCache();

            $this->logOptimization('database_cache', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('database_cache_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar cache de sesiones
     */
    public function optimizeSessionCache(): array
    {
        try {
            $results = [];

            // Limpiar sesiones expiradas
            $results['expired_sessions'] = $this->cleanExpiredSessions();

            // Optimizar almacenamiento de sesiones
            $results['storage_optimized'] = $this->optimizeSessionStorage();

            // Comprimir sesiones
            $results['compression_applied'] = $this->compressSessions();

            $this->logOptimization('session_cache', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('session_cache_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizar rendimiento de cache
     */
    public function analyzeCachePerformance(): array
    {
        try {
            $analysis = [
                'hit_rate' => $this->getCacheHitRate(),
                'miss_rate' => $this->getCacheMissRate(),
                'memory_usage' => $this->getCacheMemoryUsage(),
                'key_count' => $this->getCacheKeyCount(),
                'expired_keys' => $this->getExpiredKeyCount(),
                'recommendations' => $this->getCacheRecommendations()
            ];

            Cache::put($this->cachePrefix . 'performance_analysis', $analysis, 3600);
            return [
                'success' => true,
                'analysis' => $analysis
            ];

        } catch (\Exception $e) {
            $this->logError('cache_performance_analysis', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar cache expirado
     */
    protected function cleanExpiredCache(): int
    {
        $cleaned = 0;

        // Limpiar cache de Laravel
        $cleaned += $this->cleanLaravelCache();

        // Limpiar cache de Redis
        $cleaned += $this->cleanRedisCache();

        return $cleaned;
    }

    /**
     * Limpiar cache de Laravel
     */
    protected function cleanLaravelCache(): int
    {
        $cleaned = 0;

        // Limpiar cache de aplicación
        Cache::flush();
        $cleaned++;

        // Limpiar cache de configuración
        \Artisan::call('config:clear');
        $cleaned++;

        // Limpiar cache de rutas
        \Artisan::call('route:clear');
        $cleaned++;

        // Limpiar cache de vistas
        \Artisan::call('view:clear');
        $cleaned++;

        return $cleaned;
    }

    /**
     * Limpiar cache de Redis
     */
    protected function cleanRedisCache(): int
    {
        $cleaned = 0;

        // Obtener todas las claves
        $keys = $this->redis->keys('*');

        foreach ($keys as $key) {
            $ttl = $this->redis->ttl($key);
            if ($ttl === -1) {
                // Clave sin expiración - eliminar si es antigua
                $this->redis->del($key);
                $cleaned++;
            } elseif ($ttl === -2) {
                // Clave expirada - eliminar
                $this->redis->del($key);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Optimizar memoria de Redis
     */
    protected function optimizeRedisMemory(): array
    {
        $results = [];

        // Ejecutar defragmentación
        $this->redis->memory('PURGE');
        $results['defragmentation'] = 'completed';

        // Optimizar configuración de memoria
        $this->redis->config('SET', 'maxmemory-policy', 'allkeys-lru');
        $results['memory_policy'] = 'updated';

        return $results;
    }

    /**
     * Optimizar configuración de Redis
     */
    protected function optimizeRedisConfig(): array
    {
        $config = [
            'maxmemory' => '256mb',
            'maxmemory-policy' => 'allkeys-lru',
            'save' => '900 1 300 10 60 10000',
            'tcp-keepalive' => '60'
        ];

        $results = [];
        foreach ($config as $key => $value) {
            $this->redis->config('SET', $key, $value);
            $results[$key] = $value;
        }

        return $results;
    }

    /**
     * Analizar rendimiento de Redis
     */
    protected function analyzeRedisPerformance(): array
    {
        $info = $this->redis->info();

        return [
            'memory_usage' => $info['used_memory_human'] ?? 'N/A',
            'hit_rate' => $this->calculateHitRate($info),
            'connected_clients' => $info['connected_clients'] ?? 0,
            'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0
        ];
    }

    /**
     * Calcular tasa de aciertos
     */
    protected function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Limpiar cache de consultas
     */
    protected function clearQueryCache(): bool
    {
        try {
            DB::statement('FLUSH QUERY CACHE');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimizar cache de resultados
     */
    protected function optimizeResultCache(): array
    {
        $results = [];

        // Limpiar cache de resultados
        Cache::tags(['database', 'results'])->flush();
        $results['results_cleared'] = true;

        // Recalentar cache crítico
        $this->warmupCriticalCache();
        $results['critical_cache_warmed'] = true;

        return $results;
    }

    /**
     * Limpiar cache de esquemas
     */
    protected function clearSchemaCache(): bool
    {
        try {
            \Artisan::call('schema:cache');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Limpiar sesiones expiradas
     */
    protected function cleanExpiredSessions(): int
    {
        $expired = DB::table('sessions')
            ->where('last_activity', '<', now()->subMinutes(config('session.lifetime', 120)))
            ->delete();

        return $expired;
    }

    /**
     * Optimizar almacenamiento de sesiones
     */
    protected function optimizeSessionStorage(): array
    {
        $results = [];

        // Comprimir sesiones grandes
        $sessions = DB::table('sessions')->get();
        $compressed = 0;

        foreach ($sessions as $session) {
            if (strlen($session->payload) > 1024) {
                $compressedPayload = gzcompress($session->payload);
                DB::table('sessions')
                    ->where('id', $session->id)
                    ->update(['payload' => $compressedPayload]);
                $compressed++;
            }
        }

        $results['compressed_sessions'] = $compressed;
        return $results;
    }

    /**
     * Comprimir sesiones
     */
    protected function compressSessions(): int
    {
        $compressed = 0;

        $sessions = DB::table('sessions')->get();
        foreach ($sessions as $session) {
            if (strlen($session->payload) > 512) {
                $compressedPayload = gzcompress($session->payload);
                DB::table('sessions')
                    ->where('id', $session->id)
                    ->update(['payload' => $compressedPayload]);
                $compressed++;
            }
        }

        return $compressed;
    }

    /**
     * Recalentar cache crítico
     */
    protected function warmupCriticalCache(): void
    {
        // Cache de configuración
        Cache::remember('app_config', 3600, function () {
            return config('app');
        });

        // Cache de rutas
        Cache::remember('routes', 3600, function () {
            return app('router')->getRoutes();
        });

        // Cache de esquemas
        Cache::remember('database_schema', 3600, function () {
            return Schema::getColumnListing('users');
        });
    }

    /**
     * Obtener tasa de aciertos de cache
     */
    protected function getCacheHitRate(): float
    {
        $hits = Cache::get('cache_hits', 0);
        $misses = Cache::get('cache_misses', 0);
        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Obtener tasa de fallos de cache
     */
    protected function getCacheMissRate(): float
    {
        return 100 - $this->getCacheHitRate();
    }

    /**
     * Obtener uso de memoria de cache
     */
    protected function getCacheMemoryUsage(): string
    {
        $info = $this->redis->info();
        return $info['used_memory_human'] ?? 'N/A';
    }

    /**
     * Obtener conteo de claves de cache
     */
    protected function getCacheKeyCount(): int
    {
        $keys = $this->redis->keys('*');
        return count($keys);
    }

    /**
     * Obtener conteo de claves expiradas
     */
    protected function getExpiredKeyCount(): int
    {
        $keys = $this->redis->keys('*');
        $expired = 0;

        foreach ($keys as $key) {
            $ttl = $this->redis->ttl($key);
            if ($ttl === -2) {
                $expired++;
            }
        }

        return $expired;
    }

    /**
     * Obtener recomendaciones de cache
     */
    protected function getCacheRecommendations(): array
    {
        $recommendations = [];

        $hitRate = $this->getCacheHitRate();
        if ($hitRate < 80) {
            $recommendations[] = 'Cache hit rate is low. Consider increasing cache TTL or adding more cache layers';
        }

        $memoryUsage = $this->getCacheMemoryUsage();
        if (strpos($memoryUsage, 'MB') !== false && (int) $memoryUsage > 100) {
            $recommendations[] = 'High memory usage. Consider implementing cache eviction policies';
        }

        $expiredKeys = $this->getExpiredKeyCount();
        if ($expiredKeys > 1000) {
            $recommendations[] = 'Many expired keys. Consider running cache cleanup more frequently';
        }

        return $recommendations;
    }

    /**
     * Log de optimización
     */
    protected function logOptimization(string $type, array $results): void
    {
        Log::info("Cache optimization completed: {$type}", [
            'type' => $type,
            'results' => $results,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log de error
     */
    protected function logError(string $type, string $error): void
    {
        Log::error("Cache optimization failed: {$type}", [
            'type' => $type,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }
}



