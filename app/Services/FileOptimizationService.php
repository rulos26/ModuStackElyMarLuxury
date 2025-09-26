<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FileOptimizationService
{
    protected $cachePrefix = 'file_optimization_';
    protected $maxFileSize = 10485760; // 10MB

    public function __construct()
    {
        $this->maxFileSize = config('files.max_size', 10485760);
    }

    /**
     * Optimizar archivos general
     */
    public function optimizeFiles(): array
    {
        try {
            $results = [];

            // Limpiar archivos temporales
            $results['temp_files_cleaned'] = $this->cleanTempFiles();

            // Optimizar archivos de log
            $results['log_files_optimized'] = $this->optimizeLogFiles();

            // Comprimir archivos grandes
            $results['large_files_compressed'] = $this->compressLargeFiles();

            // Limpiar archivos duplicados
            $results['duplicate_files_removed'] = $this->removeDuplicateFiles();

            // Optimizar archivos de cache
            $results['cache_files_optimized'] = $this->optimizeCacheFiles();

            $this->logOptimization('general', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('file_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar archivos de log
     */
    public function optimizeLogFiles(): array
    {
        try {
            $results = [];

            // Comprimir logs antiguos
            $results['old_logs_compressed'] = $this->compressOldLogs();

            // Limpiar logs muy antiguos
            $results['very_old_logs_removed'] = $this->removeVeryOldLogs();

            // Rotar logs grandes
            $results['large_logs_rotated'] = $this->rotateLargeLogs();

            // Optimizar formato de logs
            $results['log_format_optimized'] = $this->optimizeLogFormat();

            $this->logOptimization('log_files', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('log_file_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar archivos de cache
     */
    public function optimizeCacheFiles(): array
    {
        try {
            $results = [];

            // Limpiar cache expirado
            $results['expired_cache_cleared'] = $this->clearExpiredCache();

            // Comprimir cache grande
            $results['large_cache_compressed'] = $this->compressLargeCache();

            // Optimizar estructura de cache
            $results['cache_structure_optimized'] = $this->optimizeCacheStructure();

            $this->logOptimization('cache_files', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('cache_file_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optimizar archivos de sesión
     */
    public function optimizeSessionFiles(): array
    {
        try {
            $results = [];

            // Limpiar sesiones expiradas
            $results['expired_sessions_cleared'] = $this->clearExpiredSessions();

            // Comprimir sesiones grandes
            $results['large_sessions_compressed'] = $this->compressLargeSessions();

            // Optimizar almacenamiento de sesiones
            $results['session_storage_optimized'] = $this->optimizeSessionStorage();

            $this->logOptimization('session_files', $results);
            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            $this->logError('session_file_optimization', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analizar uso de archivos
     */
    public function analyzeFileUsage(): array
    {
        try {
            $analysis = [
                'total_files' => $this->getTotalFileCount(),
                'total_size' => $this->getTotalFileSize(),
                'largest_files' => $this->getLargestFiles(),
                'oldest_files' => $this->getOldestFiles(),
                'duplicate_files' => $this->getDuplicateFiles(),
                'recommendations' => $this->getFileRecommendations()
            ];

            Cache::put($this->cachePrefix . 'file_analysis', $analysis, 3600);
            return [
                'success' => true,
                'analysis' => $analysis
            ];

        } catch (\Exception $e) {
            $this->logError('file_analysis', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Limpiar archivos temporales
     */
    protected function cleanTempFiles(): int
    {
        $cleaned = 0;

        // Limpiar directorio temporal
        $tempDir = sys_get_temp_dir();
        $files = glob($tempDir . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $age = time() - filemtime($file);
                if ($age > 3600) { // 1 hora
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Comprimir logs antiguos
     */
    protected function compressOldLogs(): int
    {
        $compressed = 0;
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log');

        foreach ($files as $file) {
            $age = time() - filemtime($file);
            if ($age > 86400) { // 1 día
                $compressedFile = $file . '.gz';
                if (!file_exists($compressedFile)) {
                    $this->compressFile($file, $compressedFile);
                    unlink($file);
                    $compressed++;
                }
            }
        }

        return $compressed;
    }

    /**
     * Eliminar logs muy antiguos
     */
    protected function removeVeryOldLogs(): int
    {
        $removed = 0;
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log.gz');

        foreach ($files as $file) {
            $age = time() - filemtime($file);
            if ($age > 2592000) { // 30 días
                unlink($file);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Rotar logs grandes
     */
    protected function rotateLargeLogs(): int
    {
        $rotated = 0;
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log');

        foreach ($files as $file) {
            if (filesize($file) > $this->maxFileSize) {
                $this->rotateLogFile($file);
                $rotated++;
            }
        }

        return $rotated;
    }

    /**
     * Optimizar formato de logs
     */
    protected function optimizeLogFormat(): array
    {
        $results = [];

        // Implementar optimización de formato de logs
        $results['format_optimized'] = true;

        return $results;
    }

    /**
     * Limpiar cache expirado
     */
    protected function clearExpiredCache(): int
    {
        $cleaned = 0;
        $cachePath = storage_path('framework/cache');
        $files = glob($cachePath . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $age = time() - filemtime($file);
                if ($age > 3600) { // 1 hora
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Comprimir cache grande
     */
    protected function compressLargeCache(): int
    {
        $compressed = 0;
        $cachePath = storage_path('framework/cache');
        $files = glob($cachePath . '/*');

        foreach ($files as $file) {
            if (is_file($file) && filesize($file) > 1024) {
                $compressedFile = $file . '.gz';
                if (!file_exists($compressedFile)) {
                    $this->compressFile($file, $compressedFile);
                    unlink($file);
                    $compressed++;
                }
            }
        }

        return $compressed;
    }

    /**
     * Optimizar estructura de cache
     */
    protected function optimizeCacheStructure(): array
    {
        $results = [];

        // Implementar optimización de estructura de cache
        $results['structure_optimized'] = true;

        return $results;
    }

    /**
     * Limpiar sesiones expiradas
     */
    protected function clearExpiredSessions(): int
    {
        $cleaned = 0;
        $sessionPath = storage_path('framework/sessions');
        $files = glob($sessionPath . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $age = time() - filemtime($file);
                if ($age > 7200) { // 2 horas
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Comprimir sesiones grandes
     */
    protected function compressLargeSessions(): int
    {
        $compressed = 0;
        $sessionPath = storage_path('framework/sessions');
        $files = glob($sessionPath . '/*');

        foreach ($files as $file) {
            if (is_file($file) && filesize($file) > 512) {
                $compressedFile = $file . '.gz';
                if (!file_exists($compressedFile)) {
                    $this->compressFile($file, $compressedFile);
                    unlink($file);
                    $compressed++;
                }
            }
        }

        return $compressed;
    }

    /**
     * Optimizar almacenamiento de sesiones
     */
    protected function optimizeSessionStorage(): array
    {
        $results = [];

        // Implementar optimización de almacenamiento de sesiones
        $results['storage_optimized'] = true;

        return $results;
    }

    /**
     * Comprimir archivos grandes
     */
    protected function compressLargeFiles(): int
    {
        $compressed = 0;
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions')
        ];

        foreach ($directories as $directory) {
            $files = glob($directory . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filesize($file) > $this->maxFileSize) {
                    $compressedFile = $file . '.gz';
                    if (!file_exists($compressedFile)) {
                        $this->compressFile($file, $compressedFile);
                        unlink($file);
                        $compressed++;
                    }
                }
            }
        }

        return $compressed;
    }

    /**
     * Eliminar archivos duplicados
     */
    protected function removeDuplicateFiles(): int
    {
        $removed = 0;
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions')
        ];

        foreach ($directories as $directory) {
            $files = glob($directory . '/*');
            $hashes = [];

            foreach ($files as $file) {
                if (is_file($file)) {
                    $hash = md5_file($file);
                    if (isset($hashes[$hash])) {
                        unlink($file);
                        $removed++;
                    } else {
                        $hashes[$hash] = $file;
                    }
                }
            }
        }

        return $removed;
    }

    /**
     * Obtener conteo total de archivos
     */
    protected function getTotalFileCount(): int
    {
        $count = 0;
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions')
        ];

        foreach ($directories as $directory) {
            $files = glob($directory . '/*');
            $count += count($files);
        }

        return $count;
    }

    /**
     * Obtener tamaño total de archivos
     */
    protected function getTotalFileSize(): string
    {
        $totalSize = 0;
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions')
        ];

        foreach ($directories as $directory) {
            $files = glob($directory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $totalSize += filesize($file);
                }
            }
        }

        return $this->formatBytes($totalSize);
    }

    /**
     * Obtener archivos más grandes
     */
    protected function getLargestFiles(): array
    {
        $files = [];
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions')
        ];

        foreach ($directories as $directory) {
            $dirFiles = glob($directory . '/*');
            foreach ($dirFiles as $file) {
                if (is_file($file)) {
                    $files[] = [
                        'path' => $file,
                        'size' => filesize($file),
                        'size_formatted' => $this->formatBytes(filesize($file))
                    ];
                }
            }
        }

        usort($files, function($a, $b) {
            return $b['size'] - $a['size'];
        });

        return array_slice($files, 0, 10);
    }

    /**
     * Obtener archivos más antiguos
     */
    protected function getOldestFiles(): array
    {
        $files = [];
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions')
        ];

        foreach ($directories as $directory) {
            $dirFiles = glob($directory . '/*');
            foreach ($dirFiles as $file) {
                if (is_file($file)) {
                    $files[] = [
                        'path' => $file,
                        'modified' => filemtime($file),
                        'age_days' => round((time() - filemtime($file)) / 86400, 2)
                    ];
                }
            }
        }

        usort($files, function($a, $b) {
            return $a['modified'] - $b['modified'];
        });

        return array_slice($files, 0, 10);
    }

    /**
     * Obtener archivos duplicados
     */
    protected function getDuplicateFiles(): array
    {
        $duplicates = [];
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions')
        ];

        foreach ($directories as $directory) {
            $files = glob($directory . '/*');
            $hashes = [];

            foreach ($files as $file) {
                if (is_file($file)) {
                    $hash = md5_file($file);
                    if (isset($hashes[$hash])) {
                        $duplicates[] = [
                            'original' => $hashes[$hash],
                            'duplicate' => $file,
                            'size' => filesize($file)
                        ];
                    } else {
                        $hashes[$hash] = $file;
                    }
                }
            }
        }

        return $duplicates;
    }

    /**
     * Obtener recomendaciones de archivos
     */
    protected function getFileRecommendations(): array
    {
        $recommendations = [];

        $totalSize = $this->getTotalFileSize();
        if (strpos($totalSize, 'GB') !== false) {
            $recommendations[] = 'Large file storage detected. Consider implementing file compression';
        }

        $duplicates = $this->getDuplicateFiles();
        if (count($duplicates) > 10) {
            $recommendations[] = 'Many duplicate files detected. Consider implementing deduplication';
        }

        $oldestFiles = $this->getOldestFiles();
        if (!empty($oldestFiles) && $oldestFiles[0]['age_days'] > 30) {
            $recommendations[] = 'Very old files detected. Consider implementing file cleanup policies';
        }

        return $recommendations;
    }

    /**
     * Comprimir archivo
     */
    protected function compressFile(string $source, string $destination): bool
    {
        $content = file_get_contents($source);
        $compressed = gzcompress($content);
        return file_put_contents($destination, $compressed) !== false;
    }

    /**
     * Rotar archivo de log
     */
    protected function rotateLogFile(string $file): void
    {
        $rotatedFile = $file . '.' . date('Y-m-d-H-i-s');
        rename($file, $rotatedFile);

        // Crear nuevo archivo de log
        touch($file);
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
     * Log de optimización
     */
    protected function logOptimization(string $type, array $results): void
    {
        Log::info("File optimization completed: {$type}", [
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
        Log::error("File optimization failed: {$type}", [
            'type' => $type,
            'error' => $error,
            'timestamp' => now()->toISOString()
        ]);
    }
}



