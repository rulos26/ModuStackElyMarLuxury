<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class ExternalStorageService
{
    protected $provider;
    protected $apiKey;
    protected $apiSecret;
    protected $bucket;
    protected $region;
    protected $timeout;

    public function __construct()
    {
        $this->provider = config('storage.external_provider', 'aws_s3');
        $this->apiKey = config('storage.api_key');
        $this->apiSecret = config('storage.api_secret');
        $this->bucket = config('storage.bucket');
        $this->region = config('storage.region', 'us-east-1');
        $this->timeout = config('storage.timeout', 30);
    }

    /**
     * Subir archivo a almacenamiento en la nube
     */
    public function uploadFile(string $filePath, string $remotePath, array $options = []): array
    {
        try {
            $uploadData = [
                'file_path' => $filePath,
                'remote_path' => $remotePath,
                'bucket' => $options['bucket'] ?? $this->bucket,
                'region' => $options['region'] ?? $this->region,
                'visibility' => $options['visibility'] ?? 'private',
                'metadata' => $options['metadata'] ?? []
            ];

            $result = $this->uploadViaProvider($uploadData);

            if ($result['success']) {
                $this->logUpload($uploadData, $result);
                $this->updateStorageStats(true);
            } else {
                $this->logUploadError($uploadData, $result['error']);
                $this->updateStorageStats(false);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logUploadError(['file_path' => $filePath, 'remote_path' => $remotePath], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Descargar archivo de almacenamiento en la nube
     */
    public function downloadFile(string $remotePath, string $localPath, array $options = []): array
    {
        try {
            $downloadData = [
                'remote_path' => $remotePath,
                'local_path' => $localPath,
                'bucket' => $options['bucket'] ?? $this->bucket,
                'region' => $options['region'] ?? $this->region
            ];

            $result = $this->downloadViaProvider($downloadData);

            if ($result['success']) {
                $this->logDownload($downloadData, $result);
            } else {
                $this->logDownloadError($downloadData, $result['error']);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logDownloadError(['remote_path' => $remotePath, 'local_path' => $localPath], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar archivo de almacenamiento en la nube
     */
    public function deleteFile(string $remotePath, array $options = []): array
    {
        try {
            $deleteData = [
                'remote_path' => $remotePath,
                'bucket' => $options['bucket'] ?? $this->bucket,
                'region' => $options['region'] ?? $this->region
            ];

            $result = $this->deleteViaProvider($deleteData);

            if ($result['success']) {
                $this->logDelete($deleteData, $result);
            } else {
                $this->logDeleteError($deleteData, $result['error']);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logDeleteError(['remote_path' => $remotePath], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener URL pública de archivo
     */
    public function getPublicUrl(string $remotePath, array $options = []): array
    {
        try {
            $urlData = [
                'remote_path' => $remotePath,
                'bucket' => $options['bucket'] ?? $this->bucket,
                'region' => $options['region'] ?? $this->region,
                'expires' => $options['expires'] ?? 3600
            ];

            $result = $this->getUrlViaProvider($urlData);

            if ($result['success']) {
                $this->logUrlGenerated($urlData, $result);
            } else {
                $this->logUrlError($urlData, $result['error']);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logUrlError(['remote_path' => $remotePath], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Listar archivos en bucket
     */
    public function listFiles(string $prefix = '', array $options = []): array
    {
        try {
            $listData = [
                'prefix' => $prefix,
                'bucket' => $options['bucket'] ?? $this->bucket,
                'region' => $options['region'] ?? $this->region,
                'max_keys' => $options['max_keys'] ?? 1000
            ];

            $result = $this->listViaProvider($listData);

            if ($result['success']) {
                $this->logList($listData, $result);
            } else {
                $this->logListError($listData, $result['error']);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logListError(['prefix' => $prefix], $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar estado del servicio de almacenamiento
     */
    public function checkHealth(): array
    {
        try {
            $testFile = 'health-check.txt';
            $testContent = 'Health check file created at ' . now()->toISOString();

            // Crear archivo temporal
            $tempPath = storage_path('app/temp/' . $testFile);
            file_put_contents($tempPath, $testContent);

            // Subir archivo
            $uploadResult = $this->uploadFile($tempPath, $testFile);

            if ($uploadResult['success']) {
                // Eliminar archivo
                $this->deleteFile($testFile);

                // Limpiar archivo temporal
                unlink($tempPath);

                return [
                    'status' => 'healthy',
                    'provider' => $this->provider,
                    'bucket' => $this->bucket
                ];
            }

            return [
                'status' => 'unhealthy',
                'provider' => $this->provider,
                'error' => $uploadResult['error']
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'provider' => $this->provider,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas de almacenamiento
     */
    public function getStats(): array
    {
        $stats = Cache::get('storage_stats', [
            'total_uploads' => 0,
            'successful_uploads' => 0,
            'failed_uploads' => 0,
            'total_downloads' => 0,
            'total_deletes' => 0,
            'last_upload' => null,
            'providers_used' => []
        ]);

        return $stats;
    }

    /**
     * Configurar proveedor de almacenamiento
     */
    public function configure(string $provider, string $apiKey, string $apiSecret, string $bucket, string $region = 'us-east-1'): void
    {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->bucket = $bucket;
        $this->region = $region;
    }

    /**
     * Subir archivo via proveedor
     */
    protected function uploadViaProvider(array $uploadData): array
    {
        switch ($this->provider) {
            case 'aws_s3':
                return $this->uploadViaS3($uploadData);
            case 'google_cloud':
                return $this->uploadViaGoogleCloud($uploadData);
            case 'azure_blob':
                return $this->uploadViaAzureBlob($uploadData);
            case 'digital_ocean':
                return $this->uploadViaDigitalOcean($uploadData);
            default:
                return $this->uploadViaS3($uploadData);
        }
    }

    /**
     * Subir archivo via AWS S3
     */
    protected function uploadViaS3(array $uploadData): array
    {
        try {
            $fileContent = file_get_contents($uploadData['file_path']);
            $fileName = basename($uploadData['remote_path']);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => mime_content_type($uploadData['file_path']),
                    'x-amz-acl' => $uploadData['visibility'] === 'public' ? 'public-read' : 'private'
                ])
                ->put("https://{$uploadData['bucket']}.s3.{$uploadData['region']}.amazonaws.com/{$uploadData['remote_path']}", $fileContent);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'provider' => 'aws_s3',
                    'url' => "https://{$uploadData['bucket']}.s3.{$uploadData['region']}.amazonaws.com/{$uploadData['remote_path']}",
                    'file_size' => filesize($uploadData['file_path'])
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Subir archivo via Google Cloud
     */
    protected function uploadViaGoogleCloud(array $uploadData): array
    {
        try {
            // Implementar Google Cloud Storage
            return ['success' => true, 'provider' => 'google_cloud'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Subir archivo via Azure Blob
     */
    protected function uploadViaAzureBlob(array $uploadData): array
    {
        try {
            // Implementar Azure Blob Storage
            return ['success' => true, 'provider' => 'azure_blob'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Subir archivo via Digital Ocean
     */
    protected function uploadViaDigitalOcean(array $uploadData): array
    {
        try {
            // Implementar Digital Ocean Spaces
            return ['success' => true, 'provider' => 'digital_ocean'];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Descargar archivo via proveedor
     */
    protected function downloadViaProvider(array $downloadData): array
    {
        switch ($this->provider) {
            case 'aws_s3':
                return $this->downloadViaS3($downloadData);
            default:
                return ['success' => true, 'provider' => $this->provider];
        }
    }

    /**
     * Descargar archivo via AWS S3
     */
    protected function downloadViaS3(array $downloadData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("https://{$downloadData['bucket']}.s3.{$downloadData['region']}.amazonaws.com/{$downloadData['remote_path']}");

            if ($response->successful()) {
                file_put_contents($downloadData['local_path'], $response->body());
                return [
                    'success' => true,
                    'provider' => 'aws_s3',
                    'file_size' => strlen($response->body())
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eliminar archivo via proveedor
     */
    protected function deleteViaProvider(array $deleteData): array
    {
        switch ($this->provider) {
            case 'aws_s3':
                return $this->deleteViaS3($deleteData);
            default:
                return ['success' => true, 'provider' => $this->provider];
        }
    }

    /**
     * Eliminar archivo via AWS S3
     */
    protected function deleteViaS3(array $deleteData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->delete("https://{$deleteData['bucket']}.s3.{$deleteData['region']}.amazonaws.com/{$deleteData['remote_path']}");

            return [
                'success' => $response->successful(),
                'provider' => 'aws_s3'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtener URL via proveedor
     */
    protected function getUrlViaProvider(array $urlData): array
    {
        switch ($this->provider) {
            case 'aws_s3':
                return $this->getUrlViaS3($urlData);
            default:
                return [
                    'success' => true,
                    'provider' => $this->provider,
                    'url' => "https://{$urlData['bucket']}.s3.{$urlData['region']}.amazonaws.com/{$urlData['remote_path']}"
                ];
        }
    }

    /**
     * Obtener URL via AWS S3
     */
    protected function getUrlViaS3(array $urlData): array
    {
        try {
            $url = "https://{$urlData['bucket']}.s3.{$urlData['region']}.amazonaws.com/{$urlData['remote_path']}";

            return [
                'success' => true,
                'provider' => 'aws_s3',
                'url' => $url,
                'expires' => $urlData['expires']
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Listar archivos via proveedor
     */
    protected function listViaProvider(array $listData): array
    {
        switch ($this->provider) {
            case 'aws_s3':
                return $this->listViaS3($listData);
            default:
                return ['success' => true, 'provider' => $this->provider, 'files' => []];
        }
    }

    /**
     * Listar archivos via AWS S3
     */
    protected function listViaS3(array $listData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get("https://{$listData['bucket']}.s3.{$listData['region']}.amazonaws.com/", [
                    'prefix' => $listData['prefix'],
                    'max-keys' => $listData['max_keys']
                ]);

            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                $files = [];

                if (isset($xml->Contents)) {
                    foreach ($xml->Contents as $content) {
                        $files[] = [
                            'key' => (string) $content->Key,
                            'size' => (int) $content->Size,
                            'last_modified' => (string) $content->LastModified
                        ];
                    }
                }

                return [
                    'success' => true,
                    'provider' => 'aws_s3',
                    'files' => $files
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log de subida
     */
    protected function logUpload(array $uploadData, array $result): void
    {
        Log::info('File uploaded successfully', [
            'remote_path' => $uploadData['remote_path'],
            'provider' => $result['provider'] ?? $this->provider,
            'file_size' => $result['file_size'] ?? null
        ]);
    }

    /**
     * Log de error de subida
     */
    protected function logUploadError(array $uploadData, string $error): void
    {
        Log::error('File upload failed', [
            'remote_path' => $uploadData['remote_path'],
            'error' => $error
        ]);
    }

    /**
     * Log de descarga
     */
    protected function logDownload(array $downloadData, array $result): void
    {
        Log::info('File downloaded successfully', [
            'remote_path' => $downloadData['remote_path'],
            'local_path' => $downloadData['local_path'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de descarga
     */
    protected function logDownloadError(array $downloadData, string $error): void
    {
        Log::error('File download failed', [
            'remote_path' => $downloadData['remote_path'],
            'error' => $error
        ]);
    }

    /**
     * Log de eliminación
     */
    protected function logDelete(array $deleteData, array $result): void
    {
        Log::info('File deleted successfully', [
            'remote_path' => $deleteData['remote_path'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de eliminación
     */
    protected function logDeleteError(array $deleteData, string $error): void
    {
        Log::error('File deletion failed', [
            'remote_path' => $deleteData['remote_path'],
            'error' => $error
        ]);
    }

    /**
     * Log de URL generada
     */
    protected function logUrlGenerated(array $urlData, array $result): void
    {
        Log::info('Public URL generated', [
            'remote_path' => $urlData['remote_path'],
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de URL
     */
    protected function logUrlError(array $urlData, string $error): void
    {
        Log::error('URL generation failed', [
            'remote_path' => $urlData['remote_path'],
            'error' => $error
        ]);
    }

    /**
     * Log de listado
     */
    protected function logList(array $listData, array $result): void
    {
        Log::info('Files listed successfully', [
            'prefix' => $listData['prefix'],
            'count' => count($result['files'] ?? []),
            'provider' => $result['provider'] ?? $this->provider
        ]);
    }

    /**
     * Log de error de listado
     */
    protected function logListError(array $listData, string $error): void
    {
        Log::error('File listing failed', [
            'prefix' => $listData['prefix'],
            'error' => $error
        ]);
    }

    /**
     * Actualizar estadísticas de almacenamiento
     */
    protected function updateStorageStats(bool $success): void
    {
        $stats = Cache::get('storage_stats', [
            'total_uploads' => 0,
            'successful_uploads' => 0,
            'failed_uploads' => 0,
            'total_downloads' => 0,
            'total_deletes' => 0,
            'last_upload' => null,
            'providers_used' => []
        ]);

        $stats['total_uploads']++;
        $stats['last_upload'] = now()->toISOString();

        if ($success) {
            $stats['successful_uploads']++;
        } else {
            $stats['failed_uploads']++;
        }

        if (!in_array($this->provider, $stats['providers_used'])) {
            $stats['providers_used'][] = $this->provider;
        }

        Cache::put('storage_stats', $stats, 86400);
    }
}



