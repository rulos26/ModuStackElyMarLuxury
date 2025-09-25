<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Query\Builder;

class QueryOptimizationService
{
    protected $cachePrefix = 'query_optimization_';
    protected $slowQueryThreshold = 2.0; // segundos

    public function __construct()
    {
        $this->slowQueryThreshold = config('database.slow_query_threshold', 2.0);
    }

    /**
     * Optimizar consultas lentas
     */
    public function optimizeSlowQueries(): array
    {
        try {
            $results = [];

            // Obtener consultas lentas
            $slowQueries = $this->getSlowQueries();

            foreach ($slowQueries as $query) {
                $optimized = $this->optimizeQuery($query);
                if ($optimized) {
                    $results[] = $optimized;
                }
            }

            $this->logOptimization('slow_queries', $results);
            return [
                'success' => true,
                'optimized_queries' => count($results),
                'results' => $results
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
     * Optimizar consultas N+1
     */
    public function optimizeNPlusOneQueries(): array
    {
        try {
            $results = [];

            // Detectar consultas N+1
            $nPlusOneQueries = $this->detectNPlusOneQueries();

            foreach ($nPlusOneQueries as $query) {
                $optimized = $this->optimizeNPlusOneQuery($query);
                if ($optimized) {
                    $results[] = $optimized;
                }
            }

            $this->logOptimization('n_plus_one_queries', $results);
            return [
                'success' => true,
                'optimized_queries' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('n_plus_one_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar consultas con joins
     */
    public function optimizeJoinQueries(): array
    {
        try {
            $results = [];

            // Obtener consultas con joins
            $joinQueries = $this->getJoinQueries();

            foreach ($joinQueries as $query) {
                $optimized = $this->optimizeJoinQuery($query);
                if ($optimized) {
                    $results[] = $optimized;
                }
            }

            $this->logOptimization('join_queries', $results);
            return [
                'success' => true,
                'optimized_queries' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('join_query_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar consultas con subconsultas
     */
    public function optimizeSubqueryQueries(): array
    {
        try {
            $results = [];

            // Obtener consultas con subconsultas
            $subqueryQueries = $this->getSubqueryQueries();

            foreach ($subqueryQueries as $query) {
                $optimized = $this->optimizeSubqueryQuery($query);
                if ($optimized) {
                    $results[] = $optimized;
                }
            }

            $this->logOptimization('subquery_queries', $results);
            return [
                'success' => true,
                'optimized_queries' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('subquery_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizar rendimiento de consultas
     */
    public function analyzeQueryPerformance(): array
    {
        try {
            $analysis = [
                'slow_queries' => $this->getSlowQueriesCount(),
                'n_plus_one_queries' => $this->getNPlusOneQueriesCount(),
                'join_queries' => $this->getJoinQueriesCount(),
                'subquery_queries' => $this->getSubqueryQueriesCount(),
                'total_queries' => $this->getTotalQueriesCount(),
                'average_query_time' => $this->getAverageQueryTime(),
                'recommendations' => $this->getQueryRecommendations()
            ];

            Cache::put($this->cachePrefix . 'performance_analysis', $analysis, 3600);
            return [
                'success' => true,
                'analysis' => $analysis
            ];

        } catch (\Exception $e) {
            $this->logError('query_performance_analysis', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener consultas lentas
     */
    protected function getSlowQueries(): array
    {
        return DB::select("
            SELECT
                sql_text,
                start_time,
                query_time,
                lock_time,
                rows_sent,
                rows_examined
            FROM mysql.slow_log
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY query_time DESC
        ");
    }

    /**
     * Optimizar consulta
     */
    protected function optimizeQuery(array $query): ?array
    {
        $originalQuery = $query['sql_text'];
        $optimizedQuery = $this->getOptimizedQuery($originalQuery);

        if ($optimizedQuery !== $originalQuery) {
            return [
                'original_query' => $originalQuery,
                'optimized_query' => $optimizedQuery,
                'improvement' => $this->calculateImprovement($query),
                'recommendations' => $this->getQueryRecommendationsString($originalQuery)
            ];
        }

        return null;
    }

    /**
     * Obtener consulta optimizada
     */
    protected function getOptimizedQuery(string $query): string
    {
        $optimized = $query;

        // Optimizar SELECT *
        $optimized = preg_replace('/SELECT \*/', 'SELECT specific_columns', $optimized);

        // Optimizar LIMIT sin ORDER BY
        if (preg_match('/LIMIT \d+/', $optimized) && !preg_match('/ORDER BY/', $optimized)) {
            $optimized = preg_replace('/LIMIT (\d+)/', 'ORDER BY id LIMIT $1', $optimized);
        }

        // Optimizar WHERE con funciones
        $optimized = preg_replace('/WHERE DATE\(([^)]+)\)/', 'WHERE $1 >=', $optimized);

        return $optimized;
    }

    /**
     * Calcular mejora
     */
    protected function calculateImprovement(array $query): float
    {
        $queryTime = $query['query_time'] ?? 0;
        $rowsExamined = $query['rows_examined'] ?? 0;
        $rowsSent = $query['rows_sent'] ?? 0;

        // Calcular factor de mejora basado en tiempo y filas
        $timeFactor = $queryTime > $this->slowQueryThreshold ? 0.5 : 0.1;
        $rowFactor = $rowsExamined > $rowsSent * 10 ? 0.3 : 0.1;

        return round(($timeFactor + $rowFactor) * 100, 2);
    }

    /**
     * Detectar consultas N+1
     */
    protected function detectNPlusOneQueries(): array
    {
        // Implementar detección de consultas N+1
        return [];
    }

    /**
     * Optimizar consulta N+1
     */
    protected function optimizeNPlusOneQuery(array $query): ?array
    {
        // Implementar optimización de consultas N+1
        return null;
    }

    /**
     * Obtener consultas con joins
     */
    protected function getJoinQueries(): array
    {
        return DB::select("
            SELECT
                sql_text,
                start_time,
                query_time,
                rows_sent,
                rows_examined
            FROM mysql.slow_log
            WHERE sql_text LIKE '%JOIN%'
            AND start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY query_time DESC
        ");
    }

    /**
     * Optimizar consulta con joins
     */
    protected function optimizeJoinQuery(array $query): ?array
    {
        $originalQuery = $query['sql_text'];
        $optimizedQuery = $this->optimizeJoinQueryString($originalQuery);

        if ($optimizedQuery !== $originalQuery) {
            return [
                'original_query' => $originalQuery,
                'optimized_query' => $optimizedQuery,
                'improvement' => $this->calculateJoinImprovement($query)
            ];
        }

        return null;
    }

    /**
     * Optimizar consulta con joins (string version)
     */
    protected function optimizeJoinQueryString(string $query): string
    {
        $optimized = $query;

        // Optimizar orden de joins
        $optimized = $this->optimizeJoinOrder($optimized);

        // Optimizar tipos de joins
        $optimized = $this->optimizeJoinTypes($optimized);

        return $optimized;
    }

    /**
     * Optimizar orden de joins
     */
    protected function optimizeJoinOrder(string $query): string
    {
        // Implementar optimización de orden de joins
        return $query;
    }

    /**
     * Optimizar tipos de joins
     */
    protected function optimizeJoinTypes(string $query): string
    {
        // Convertir LEFT JOIN a INNER JOIN cuando sea posible
        $optimized = preg_replace('/LEFT JOIN/', 'INNER JOIN', $query);

        return $optimized;
    }

    /**
     * Calcular mejora de joins
     */
    protected function calculateJoinImprovement(array $query): float
    {
        $queryTime = $query['query_time'] ?? 0;
        $rowsExamined = $query['rows_examined'] ?? 0;

        // Calcular mejora basada en tiempo y filas examinadas
        $timeImprovement = $queryTime > $this->slowQueryThreshold ? 0.4 : 0.1;
        $rowImprovement = $rowsExamined > 1000 ? 0.3 : 0.1;

        return round(($timeImprovement + $rowImprovement) * 100, 2);
    }

    /**
     * Obtener consultas con subconsultas
     */
    protected function getSubqueryQueries(): array
    {
        return DB::select("
            SELECT
                sql_text,
                start_time,
                query_time,
                rows_sent,
                rows_examined
            FROM mysql.slow_log
            WHERE sql_text LIKE '%SELECT%SELECT%'
            AND start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY query_time DESC
        ");
    }

    /**
     * Optimizar consulta con subconsultas
     */
    protected function optimizeSubqueryQuery(array $query): ?array
    {
        $originalQuery = $query['sql_text'];
        $optimizedQuery = $this->convertSubqueryToJoin($originalQuery);

        if ($optimizedQuery !== $originalQuery) {
            return [
                'original_query' => $originalQuery,
                'optimized_query' => $optimizedQuery,
                'improvement' => $this->calculateSubqueryImprovement($query)
            ];
        }

        return null;
    }

    /**
     * Convertir subconsulta a JOIN
     */
    protected function convertSubqueryToJoin(string $query): string
    {
        // Implementar conversión de subconsultas a JOINs
        return $query;
    }

    /**
     * Calcular mejora de subconsultas
     */
    protected function calculateSubqueryImprovement(array $query): float
    {
        $queryTime = $query['query_time'] ?? 0;

        // Las subconsultas suelen ser más lentas que los JOINs
        return $queryTime > $this->slowQueryThreshold ? 0.6 : 0.3;
    }

    /**
     * Obtener conteo de consultas lentas
     */
    protected function getSlowQueriesCount(): int
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM mysql.slow_log
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        return $result[0]->count ?? 0;
    }

    /**
     * Obtener conteo de consultas N+1
     */
    protected function getNPlusOneQueriesCount(): int
    {
        // Implementar conteo de consultas N+1
        return 0;
    }

    /**
     * Obtener conteo de consultas con joins
     */
    protected function getJoinQueriesCount(): int
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM mysql.slow_log
            WHERE sql_text LIKE '%JOIN%'
            AND start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        return $result[0]->count ?? 0;
    }

    /**
     * Obtener conteo de consultas con subconsultas
     */
    protected function getSubqueryQueriesCount(): int
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM mysql.slow_log
            WHERE sql_text LIKE '%SELECT%SELECT%'
            AND start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        return $result[0]->count ?? 0;
    }

    /**
     * Obtener conteo total de consultas
     */
    protected function getTotalQueriesCount(): int
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM mysql.slow_log
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        return $result[0]->count ?? 0;
    }

    /**
     * Obtener tiempo promedio de consultas
     */
    protected function getAverageQueryTime(): float
    {
        $result = DB::select("
            SELECT AVG(query_time) as avg_time
            FROM mysql.slow_log
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        return round($result[0]->avg_time ?? 0, 4);
    }

    /**
     * Obtener recomendaciones de consultas
     */
    protected function getQueryRecommendations(): array
    {
        $recommendations = [];

        $slowQueries = $this->getSlowQueriesCount();
        if ($slowQueries > 10) {
            $recommendations[] = 'Many slow queries detected. Consider adding indexes or optimizing queries';
        }

        $nPlusOneQueries = $this->getNPlusOneQueriesCount();
        if ($nPlusOneQueries > 5) {
            $recommendations[] = 'N+1 queries detected. Consider using eager loading';
        }

        $joinQueries = $this->getJoinQueriesCount();
        if ($joinQueries > 20) {
            $recommendations[] = 'Many join queries. Consider optimizing join order and types';
        }

        $subqueryQueries = $this->getSubqueryQueriesCount();
        if ($subqueryQueries > 10) {
            $recommendations[] = 'Many subqueries detected. Consider converting to JOINs';
        }

        return $recommendations;
    }

    /**
     * Obtener recomendaciones para consulta específica (string version)
     */
    protected function getQueryRecommendationsString(string $query): array
    {
        $recommendations = [];

        if (strpos($query, 'SELECT *') !== false) {
            $recommendations[] = 'Avoid SELECT *. Specify only needed columns';
        }

        if (strpos($query, 'WHERE') !== false && strpos($query, 'ORDER BY') === false) {
            $recommendations[] = 'Add ORDER BY clause for consistent results';
        }

        if (strpos($query, 'LIKE') !== false && strpos($query, '%') === 0) {
            $recommendations[] = 'Avoid leading wildcards in LIKE queries';
        }

        if (strpos($query, 'OR') !== false) {
            $recommendations[] = 'Consider using UNION instead of OR for better performance';
        }

        return $recommendations;
    }

    /**
     * Log de optimización
     */
    protected function logOptimization(string $type, array $results): void
    {
        Log::info("Query optimization completed: {$type}", [
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
        Log::error("Query optimization failed: {$type}", [
            'type' => $type,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }
}
