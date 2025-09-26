<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseOptimizationService
{
    protected $connection;
    protected $cachePrefix = 'db_optimization_';

    public function __construct()
    {
        $this->connection = DB::connection();
    }

    /**
     * Optimizar índices de base de datos
     */
    public function optimizeIndexes(): array
    {
        try {
            $results = [];
            $tables = $this->getTables();

            foreach ($tables as $table) {
                $indexes = $this->getTableIndexes($table);
                $optimizedIndexes = $this->analyzeIndexes($table, $indexes);

                if (!empty($optimizedIndexes)) {
                    $this->createOptimizedIndexes($table, $optimizedIndexes);
                    $results[$table] = $optimizedIndexes;
                }
            }

            $this->logOptimization('indexes', $results);
            return [
                'success' => true,
                'optimized_tables' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('index_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar consultas lentas
     */
    public function optimizeSlowQueries(): array
    {
        try {
            $slowQueries = $this->getSlowQueries();
            $optimizedQueries = [];

            foreach ($slowQueries as $query) {
                $optimized = $this->optimizeQuery($query);
                if ($optimized) {
                    $optimizedQueries[] = $optimized;
                }
            }

            $this->logOptimization('slow_queries', $optimizedQueries);
            return [
                'success' => true,
                'optimized_queries' => count($optimizedQueries),
                'results' => $optimizedQueries
            ];

        } catch (\Exception $e) {
            $this->logError('slow_query_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar tablas
     */
    public function optimizeTables(): array
    {
        try {
            $tables = $this->getTables();
            $results = [];

            foreach ($tables as $table) {
                $this->connection->statement("OPTIMIZE TABLE `{$table}`");
                $results[] = $table;
            }

            $this->logOptimization('tables', $results);
            return [
                'success' => true,
                'optimized_tables' => count($results),
                'tables' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('table_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar datos obsoletos
     */
    public function cleanupObsoleteData(): array
    {
        try {
            $results = [];

            // Limpiar logs antiguos
            $deletedLogs = $this->cleanupOldLogs();
            $results['deleted_logs'] = $deletedLogs;

            // Limpiar sesiones expiradas
            $deletedSessions = $this->cleanupExpiredSessions();
            $results['deleted_sessions'] = $deletedSessions;

            // Limpiar cache expirado
            $deletedCache = $this->cleanupExpiredCache();
            $results['deleted_cache'] = $deletedCache;

            // Limpiar jobs fallidos antiguos
            $deletedJobs = $this->cleanupFailedJobs();
            $results['deleted_jobs'] = $deletedJobs;

            $this->logOptimization('cleanup', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('cleanup_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizar rendimiento de base de datos
     */
    public function analyzePerformance(): array
    {
        try {
            $analysis = [
                'connection_count' => $this->getConnectionCount(),
                'slow_queries' => $this->getSlowQueriesCount(),
                'table_sizes' => $this->getTableSizes(),
                'index_usage' => $this->getIndexUsage(),
                'query_cache' => $this->getQueryCacheStatus(),
                'buffer_pool' => $this->getBufferPoolStatus(),
                'recommendations' => $this->getRecommendations()
            ];

            Cache::put($this->cachePrefix . 'performance_analysis', $analysis, 3600);
            return [
                'success' => true,
                'analysis' => $analysis
            ];

        } catch (\Exception $e) {
            $this->logError('performance_analysis', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar configuración de base de datos
     */
    public function optimizeConfiguration(): array
    {
        try {
            $config = $this->getCurrentConfiguration();
            $optimizedConfig = $this->getOptimizedConfiguration();
            $recommendations = $this->getConfigurationRecommendations($config, $optimizedConfig);

            return [
                'success' => true,
                'current_config' => $config,
                'optimized_config' => $optimizedConfig,
                'recommendations' => $recommendations
            ];

        } catch (\Exception $e) {
            $this->logError('configuration_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener tablas de la base de datos
     */
    protected function getTables(): array
    {
        $tables = $this->connection->select("SHOW TABLES");
        return array_map(function($table) {
            return array_values((array) $table)[0];
        }, $tables);
    }

    /**
     * Obtener índices de una tabla
     */
    protected function getTableIndexes(string $table): array
    {
        return $this->connection->select("SHOW INDEX FROM `{$table}`");
    }

    /**
     * Analizar índices para optimización
     */
    protected function analyzeIndexes(string $table, array $indexes): array
    {
        $optimizedIndexes = [];

        // Agrupar índices por columna
        $columnIndexes = [];
        foreach ($indexes as $index) {
            $columnIndexes[$index->Column_name][] = $index;
        }

        // Analizar cada columna
        foreach ($columnIndexes as $column => $indexes) {
            if (count($indexes) > 1) {
                // Múltiples índices en la misma columna - optimizar
                $optimizedIndexes[] = [
                    'table' => $table,
                    'column' => $column,
                    'action' => 'consolidate',
                    'indexes' => $indexes
                ];
            }
        }

        return $optimizedIndexes;
    }

    /**
     * Crear índices optimizados
     */
    protected function createOptimizedIndexes(string $table, array $optimizedIndexes): void
    {
        foreach ($optimizedIndexes as $index) {
            if ($index['action'] === 'consolidate') {
                // Consolidar índices duplicados
                $this->consolidateIndexes($table, $index);
            }
        }
    }

    /**
     * Consolidar índices duplicados
     */
    protected function consolidateIndexes(string $table, array $index): void
    {
        // Implementar lógica de consolidación
        $this->connection->statement("ALTER TABLE `{$table}` DROP INDEX `{$index['indexes'][0]->Key_name}`");
    }

    /**
     * Obtener consultas lentas
     */
    protected function getSlowQueries(): array
    {
        return $this->connection->select("
            SELECT * FROM mysql.slow_log
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY start_time DESC
        ");
    }

    /**
     * Optimizar consulta
     */
    protected function optimizeQuery(array $query): ?array
    {
        // Implementar lógica de optimización de consultas
        return [
            'original_query' => $query['sql_text'],
            'optimized_query' => $this->getOptimizedQuery($query['sql_text']),
            'improvement' => $this->calculateImprovement($query)
        ];
    }

    /**
     * Obtener consulta optimizada
     */
    protected function getOptimizedQuery(string $query): string
    {
        // Implementar lógica de optimización
        return $query;
    }

    /**
     * Calcular mejora
     */
    protected function calculateImprovement(array $query): float
    {
        return 0.0; // Implementar cálculo de mejora
    }

    /**
     * Limpiar logs antiguos
     */
    protected function cleanupOldLogs(): int
    {
        return $this->connection->table('logs')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
    }

    /**
     * Limpiar sesiones expiradas
     */
    protected function cleanupExpiredSessions(): int
    {
        return $this->connection->table('sessions')
            ->where('last_activity', '<', now()->subMinutes(config('session.lifetime', 120)))
            ->delete();
    }

    /**
     * Limpiar cache expirado
     */
    protected function cleanupExpiredCache(): int
    {
        return $this->connection->table('cache')
            ->where('expiration', '<', now()->timestamp)
            ->delete();
    }

    /**
     * Limpiar jobs fallidos
     */
    protected function cleanupFailedJobs(): int
    {
        return $this->connection->table('failed_jobs')
            ->where('failed_at', '<', now()->subDays(7))
            ->delete();
    }

    /**
     * Obtener conteo de conexiones
     */
    protected function getConnectionCount(): int
    {
        $result = $this->connection->select("SHOW STATUS LIKE 'Threads_connected'");
        return $result[0]->Value ?? 0;
    }

    /**
     * Obtener conteo de consultas lentas
     */
    protected function getSlowQueriesCount(): int
    {
        $result = $this->connection->select("SHOW STATUS LIKE 'Slow_queries'");
        return $result[0]->Value ?? 0;
    }

    /**
     * Obtener tamaños de tablas
     */
    protected function getTableSizes(): array
    {
        $tables = $this->getTables();
        $sizes = [];

        foreach ($tables as $table) {
            $result = $this->connection->select("
                SELECT
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name = '{$table}'
            ");

            if (!empty($result)) {
                $sizes[$table] = $result[0]->size_mb;
            }
        }

        return $sizes;
    }

    /**
     * Obtener uso de índices
     */
    protected function getIndexUsage(): array
    {
        return $this->connection->select("
            SELECT
                table_name,
                index_name,
                cardinality
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
            ORDER BY cardinality DESC
        ");
    }

    /**
     * Obtener estado del query cache
     */
    protected function getQueryCacheStatus(): array
    {
        $result = $this->connection->select("SHOW STATUS LIKE 'Qcache%'");
        $status = [];

        foreach ($result as $row) {
            $status[$row->Variable_name] = $row->Value;
        }

        return $status;
    }

    /**
     * Obtener estado del buffer pool
     */
    protected function getBufferPoolStatus(): array
    {
        $result = $this->connection->select("SHOW STATUS LIKE 'Innodb_buffer_pool%'");
        $status = [];

        foreach ($result as $row) {
            $status[$row->Variable_name] = $row->Value;
        }

        return $status;
    }

    /**
     * Obtener recomendaciones
     */
    protected function getRecommendations(): array
    {
        $recommendations = [];

        // Analizar conexiones
        $connections = $this->getConnectionCount();
        if ($connections > 100) {
            $recommendations[] = 'Consider increasing max_connections';
        }

        // Analizar consultas lentas
        $slowQueries = $this->getSlowQueriesCount();
        if ($slowQueries > 10) {
            $recommendations[] = 'Review slow queries and add indexes';
        }

        return $recommendations;
    }

    /**
     * Obtener configuración actual
     */
    protected function getCurrentConfiguration(): array
    {
        $variables = $this->connection->select("SHOW VARIABLES");
        $config = [];

        foreach ($variables as $variable) {
            $config[$variable->Variable_name] = $variable->Value;
        }

        return $config;
    }

    /**
     * Obtener configuración optimizada
     */
    protected function getOptimizedConfiguration(): array
    {
        return [
            'innodb_buffer_pool_size' => '1G',
            'query_cache_size' => '64M',
            'max_connections' => '200',
            'slow_query_log' => 'ON',
            'long_query_time' => '2'
        ];
    }

    /**
     * Obtener recomendaciones de configuración
     */
    protected function getConfigurationRecommendations(array $current, array $optimized): array
    {
        $recommendations = [];

        foreach ($optimized as $key => $value) {
            if (isset($current[$key]) && $current[$key] !== $value) {
                $recommendations[] = [
                    'variable' => $key,
                    'current' => $current[$key],
                    'recommended' => $value,
                    'action' => 'update'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Log de optimización
     */
    protected function logOptimization(string $type, array $results): void
    {
        Log::info("Database optimization completed: {$type}", [
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
        Log::error("Database optimization failed: {$type}", [
            'type' => $type,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }
}



