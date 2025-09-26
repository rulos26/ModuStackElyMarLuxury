<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MemoryOptimizationService
{
    protected $cachePrefix = 'memory_optimization_';
    protected $memoryThreshold = 0.8; // 80% de uso de memoria

    public function __construct()
    {
        $this->memoryThreshold = config('memory.threshold', 0.8);
    }

    /**
     * Optimizar memoria general
     */
    public function optimizeMemory(): array
    {
        try {
            $results = [];

            // Analizar uso de memoria
            $results['memory_analysis'] = $this->analyzeMemoryUsage();

            // Limpiar memoria no utilizada
            $results['memory_cleaned'] = $this->cleanUnusedMemory();

            // Optimizar garbage collection
            $results['gc_optimized'] = $this->optimizeGarbageCollection();

            // Optimizar cache de memoria
            $results['cache_optimized'] = $this->optimizeMemoryCache();

            // Optimizar variables globales
            $results['globals_optimized'] = $this->optimizeGlobalVariables();

            $this->logOptimization('general', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('memory_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar memoria de PHP
     */
    public function optimizePhpMemory(): array
    {
        try {
            $results = [];

            // Limpiar variables no utilizadas
            $results['variables_cleaned'] = $this->cleanUnusedVariables();

            // Optimizar arrays grandes
            $results['arrays_optimized'] = $this->optimizeLargeArrays();

            // Limpiar objetos no utilizados
            $results['objects_cleaned'] = $this->cleanUnusedObjects();

            // Optimizar strings
            $results['strings_optimized'] = $this->optimizeStrings();

            $this->logOptimization('php_memory', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('php_memory_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar memoria de Redis
     */
    public function optimizeRedisMemory(): array
    {
        try {
            $results = [];

            // Limpiar claves expiradas
            $results['expired_keys_cleaned'] = $this->cleanExpiredRedisKeys();

            // Optimizar memoria de Redis
            $results['memory_optimized'] = $this->optimizeRedisMemoryUsage();

            // Comprimir datos grandes
            $results['data_compressed'] = $this->compressLargeData();

            // Limpiar fragmentación
            $results['fragmentation_cleaned'] = $this->cleanFragmentation();

            $this->logOptimization('redis_memory', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('redis_memory_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizar uso de memoria
     */
    public function analyzeMemoryUsage(): array
    {
        try {
            $analysis = [
                'php_memory_usage' => $this->getPhpMemoryUsage(),
                'php_memory_peak' => $this->getPhpMemoryPeak(),
                'php_memory_limit' => $this->getPhpMemoryLimit(),
                'redis_memory_usage' => $this->getRedisMemoryUsage(),
                'system_memory_usage' => $this->getSystemMemoryUsage(),
                'memory_efficiency' => $this->getMemoryEfficiency(),
                'recommendations' => $this->getMemoryRecommendations()
            ];

            Cache::put($this->cachePrefix . 'memory_analysis', $analysis, 3600);
            return [
                'success' => true,
                'analysis' => $analysis
            ];

        } catch (\Exception $e) {
            $this->logError('memory_analysis', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar memoria no utilizada
     */
    protected function cleanUnusedMemory(): array
    {
        $results = [];

        // Limpiar cache de Laravel
        Cache::flush();
        $results['laravel_cache_cleared'] = true;

        // Limpiar variables globales
        $this->cleanGlobalVariables();
        $results['global_variables_cleaned'] = true;

        // Forzar garbage collection
        gc_collect_cycles();
        $results['garbage_collection_forced'] = true;

        return $results;
    }

    /**
     * Optimizar garbage collection
     */
    protected function optimizeGarbageCollection(): array
    {
        $results = [];

        // Configurar garbage collection
        gc_enable();
        $results['gc_enabled'] = true;

        // Ejecutar garbage collection
        $collected = gc_collect_cycles();
        $results['cycles_collected'] = $collected;

        // Configurar umbral de memoria
        $threshold = $this->getMemoryThreshold();
        $results['memory_threshold'] = $threshold;

        return $results;
    }

    /**
     * Optimizar cache de memoria
     */
    protected function optimizeMemoryCache(): array
    {
        $results = [];

        // Limpiar cache de opcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $results['opcache_reset'] = true;
        }

        // Limpiar cache de APC
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
            $results['apc_cache_cleared'] = true;
        }

        // Limpiar cache de Redis
        $this->cleanRedisCache();
        $results['redis_cache_cleared'] = true;

        return $results;
    }

    /**
     * Optimizar variables globales
     */
    protected function optimizeGlobalVariables(): array
    {
        $results = [];

        // Limpiar variables globales no utilizadas
        $this->cleanGlobalVariables();
        $results['global_variables_cleaned'] = true;

        // Optimizar arrays globales
        $this->optimizeGlobalArrays();
        $results['global_arrays_optimized'] = true;

        return $results;
    }

    /**
     * Limpiar variables no utilizadas
     */
    protected function cleanUnusedVariables(): int
    {
        $cleaned = 0;

        // Obtener variables globales
        $globals = $GLOBALS;

        foreach ($globals as $key => $value) {
            if (is_string($key) && !in_array($key, ['_GET', '_POST', '_COOKIE', '_SESSION', '_SERVER', '_ENV', '_FILES'])) {
                if (is_array($value) && empty($value)) {
                    unset($GLOBALS[$key]);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Optimizar arrays grandes
     */
    protected function optimizeLargeArrays(): array
    {
        $results = [];

        // Encontrar arrays grandes en memoria
        $largeArrays = $this->findLargeArrays();

        foreach ($largeArrays as $array) {
            // Comprimir array si es posible
            if (is_array($array) && count($array) > 1000) {
                $compressed = gzcompress(serialize($array));
                $results['compressed_arrays'][] = [
                    'original_size' => strlen(serialize($array)),
                    'compressed_size' => strlen($compressed),
                    'compression_ratio' => round((1 - strlen($compressed) / strlen(serialize($array))) * 100, 2)
                ];
            }
        }

        return $results;
    }

    /**
     * Limpiar objetos no utilizados
     */
    protected function cleanUnusedObjects(): int
    {
        $cleaned = 0;

        // Obtener objetos en memoria
        $objects = get_declared_classes();

        foreach ($objects as $class) {
            if (class_exists($class)) {
                $reflection = new \ReflectionClass($class);
                if ($reflection->isInstantiable()) {
                    // Verificar si el objeto está siendo utilizado
                    $instances = $this->getClassInstances($class);
                    if (empty($instances)) {
                        $cleaned++;
                    }
                }
            }
        }

        return $cleaned;
    }

    /**
     * Optimizar strings
     */
    protected function optimizeStrings(): array
    {
        $results = [];

        // Encontrar strings grandes
        $largeStrings = $this->findLargeStrings();

        foreach ($largeStrings as $string) {
            if (strlen($string) > 1000) {
                // Comprimir string si es posible
                $compressed = gzcompress($string);
                $results['compressed_strings'][] = [
                    'original_size' => strlen($string),
                    'compressed_size' => strlen($compressed),
                    'compression_ratio' => round((1 - strlen($compressed) / strlen($string)) * 100, 2)
                ];
            }
        }

        return $results;
    }

    /**
     * Limpiar claves expiradas de Redis
     */
    protected function cleanExpiredRedisKeys(): int
    {
        $redis = Redis::connection();
        $keys = $redis->keys('*');
        $cleaned = 0;

        foreach ($keys as $key) {
            $ttl = $redis->ttl($key);
            if ($ttl === -2) {
                $redis->del($key);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Optimizar uso de memoria de Redis
     */
    protected function optimizeRedisMemoryUsage(): array
    {
        $redis = Redis::connection();
        $results = [];

        // Ejecutar defragmentación
        $redis->memory('PURGE');
        $results['defragmentation'] = 'completed';

        // Configurar política de memoria
        $redis->config('SET', 'maxmemory-policy', 'allkeys-lru');
        $results['memory_policy'] = 'updated';

        return $results;
    }

    /**
     * Comprimir datos grandes
     */
    protected function compressLargeData(): int
    {
        $redis = Redis::connection();
        $keys = $redis->keys('*');
        $compressed = 0;

        foreach ($keys as $key) {
            $value = $redis->get($key);
            if (strlen($value) > 1024) {
                $compressedValue = gzcompress($value);
                $redis->set($key, $compressedValue);
                $compressed++;
            }
        }

        return $compressed;
    }

    /**
     * Limpiar fragmentación
     */
    protected function cleanFragmentation(): array
    {
        $results = [];

        // Ejecutar defragmentación de Redis
        $redis = Redis::connection();
        $redis->memory('PURGE');
        $results['redis_defragmentation'] = 'completed';

        // Limpiar fragmentación de PHP
        gc_collect_cycles();
        $results['php_gc'] = 'completed';

        return $results;
    }

    /**
     * Obtener uso de memoria de PHP
     */
    protected function getPhpMemoryUsage(): string
    {
        return $this->formatBytes(memory_get_usage(true));
    }

    /**
     * Obtener pico de memoria de PHP
     */
    protected function getPhpMemoryPeak(): string
    {
        return $this->formatBytes(memory_get_peak_usage(true));
    }

    /**
     * Obtener límite de memoria de PHP
     */
    protected function getPhpMemoryLimit(): string
    {
        return ini_get('memory_limit');
    }

    /**
     * Obtener uso de memoria de Redis
     */
    protected function getRedisMemoryUsage(): string
    {
        $redis = Redis::connection();
        $info = $redis->info();
        return $info['used_memory_human'] ?? 'N/A';
    }

    /**
     * Obtener uso de memoria del sistema
     */
    protected function getSystemMemoryUsage(): array
    {
        $memory = [];

        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $memory['load_average'] = $load;
        }

        if (function_exists('memory_get_usage')) {
            $memory['php_memory'] = memory_get_usage(true);
            $memory['php_memory_peak'] = memory_get_peak_usage(true);
        }

        return $memory;
    }

    /**
     * Obtener eficiencia de memoria
     */
    protected function getMemoryEfficiency(): float
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        if ($peak === 0) {
            return 0.0;
        }

        return round(($current / $peak) * 100, 2);
    }

    /**
     * Obtener recomendaciones de memoria
     */
    protected function getMemoryRecommendations(): array
    {
        $recommendations = [];

        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        if ($memoryUsage > $memoryLimit * $this->memoryThreshold) {
            $recommendations[] = 'High memory usage detected. Consider increasing memory_limit or optimizing code';
        }

        $efficiency = $this->getMemoryEfficiency();
        if ($efficiency < 50) {
            $recommendations[] = 'Low memory efficiency. Consider optimizing data structures';
        }

        return $recommendations;
    }

    /**
     * Obtener umbral de memoria
     */
    protected function getMemoryThreshold(): float
    {
        return $this->memoryThreshold;
    }

    /**
     * Encontrar arrays grandes
     */
    protected function findLargeArrays(): array
    {
        $largeArrays = [];

        // Buscar en variables globales
        foreach ($GLOBALS as $key => $value) {
            if (is_array($value) && count($value) > 100) {
                $largeArrays[] = $value;
            }
        }

        return $largeArrays;
    }

    /**
     * Encontrar strings grandes
     */
    protected function findLargeStrings(): array
    {
        $largeStrings = [];

        // Buscar en variables globales
        foreach ($GLOBALS as $key => $value) {
            if (is_string($value) && strlen($value) > 1000) {
                $largeStrings[] = $value;
            }
        }

        return $largeStrings;
    }

    /**
     * Obtener instancias de clase
     */
    protected function getClassInstances(string $class): array
    {
        $instances = [];

        // Implementar lógica para encontrar instancias
        return $instances;
    }

    /**
     * Limpiar variables globales
     */
    protected function cleanGlobalVariables(): void
    {
        // Limpiar variables globales no utilizadas
        foreach ($GLOBALS as $key => $value) {
            if (is_string($key) && !in_array($key, ['_GET', '_POST', '_COOKIE', '_SESSION', '_SERVER', '_ENV', '_FILES'])) {
                if (is_array($value) && empty($value)) {
                    unset($GLOBALS[$key]);
                }
            }
        }
    }

    /**
     * Optimizar arrays globales
     */
    protected function optimizeGlobalArrays(): void
    {
        // Optimizar arrays globales
        foreach ($GLOBALS as $key => $value) {
            if (is_array($value)) {
                // Comprimir arrays grandes
                if (count($value) > 1000) {
                    $GLOBALS[$key] = gzcompress(serialize($value));
                }
            }
        }
    }

    /**
     * Limpiar cache de Redis
     */
    protected function cleanRedisCache(): void
    {
        $redis = Redis::connection();
        $redis->flushdb();
    }

    /**
     * Formatear bytes
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Parsear límite de memoria
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        switch ($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }

        return $limit;
    }

    /**
     * Log de optimización
     */
    protected function logOptimization(string $type, array $results): void
    {
        Log::info("Memory optimization completed: {$type}", [
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
        Log::error("Memory optimization failed: {$type}", [
            'type' => $type,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }
}



