# 📚 Documentación de API - ModuStackElyMarLuxury

## 🎯 Descripción General

ModuStackElyMarLuxury es un sistema completo de gestión empresarial desarrollado en Laravel que incluye middleware, jobs, comandos artisan, servicios externos, optimización y testing.

## 🚀 Características Principales

### 1. **Middleware Integrados**
- **SecurityMiddleware**: Protección de seguridad
- **PerformanceMiddleware**: Monitoreo de rendimiento
- **LoggingMiddleware**: Registro de eventos

### 2. **Jobs Asíncronos**
- **SystemIntegrationJob**: Integración del sistema
- **LoggingJob**: Procesamiento de logs
- **BackupJob**: Respaldo de datos
- **NotificationJob**: Notificaciones
- **CleanupJob**: Limpieza del sistema

### 3. **Comandos Artisan**
- **system:status**: Estado del sistema
- **system:maintenance**: Modo mantenimiento
- **system:monitor**: Monitoreo
- **backup:manage**: Gestión de respaldos
- **notification:manage**: Gestión de notificaciones
- **cleanup:manage**: Gestión de limpieza
- **jobs:manage**: Gestión de jobs
- **workers:start**: Inicio de workers

### 4. **Servicios Externos**
- **ExternalApiService**: APIs externas
- **ExternalEmailService**: Servicios de email
- **ExternalSmsService**: Servicios de SMS
- **ExternalPushService**: Servicios de push
- **ExternalStorageService**: Servicios de almacenamiento
- **ExternalMonitoringService**: Servicios de monitoreo

### 5. **Optimización**
- **DatabaseOptimizationService**: Optimización de base de datos
- **CacheOptimizationService**: Optimización de cache
- **QueryOptimizationService**: Optimización de consultas
- **MemoryOptimizationService**: Optimización de memoria
- **FileOptimizationService**: Optimización de archivos
- **JobOptimizationService**: Optimización de jobs
- **ExternalServiceOptimizationService**: Optimización de servicios externos

## 🔧 Instalación

### Requisitos del Sistema
- PHP >= 8.1
- Composer
- MySQL >= 8.0
- Redis (opcional)
- Node.js >= 16 (para assets)

### Instalación Paso a Paso

1. **Clonar el repositorio**
```bash
git clone https://github.com/tu-usuario/ModuStackElyMarLuxury.git
cd ModuStackElyMarLuxury
```

2. **Instalar dependencias**
```bash
composer install
npm install
```

3. **Configurar entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos**
```bash
php artisan migrate
php artisan db:seed
```

5. **Compilar assets**
```bash
npm run build
```

## ⚙️ Configuración

### Variables de Entorno

#### Base de Datos
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modustack_elymar_luxury
DB_USERNAME=root
DB_PASSWORD=
```

#### Cache
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Queue
```env
QUEUE_CONNECTION=redis
```

#### Servicios Externos
```env
# APIs Externas
EXTERNAL_API_BASE_URL=https://api.example.com
EXTERNAL_API_KEY=your_api_key
EXTERNAL_API_TIMEOUT=30
EXTERNAL_API_RETRY_ATTEMPTS=3

# Email
MAIL_EXTERNAL_PROVIDER=smtp
MAIL_EXTERNAL_API_KEY=your_email_api_key
MAIL_TIMEOUT=30

# SMS
SMS_PROVIDER=twilio
SMS_API_KEY=your_sms_api_key
SMS_API_SECRET=your_sms_api_secret
SMS_FROM_NUMBER=+1234567890
SMS_TIMEOUT=30

# Push Notifications
PUSH_PROVIDER=fcm
PUSH_API_KEY=your_push_api_key
PUSH_API_SECRET=your_push_api_secret
PUSH_TIMEOUT=30

# Almacenamiento
STORAGE_EXTERNAL_PROVIDER=aws_s3
STORAGE_API_KEY=your_storage_api_key
STORAGE_API_SECRET=your_storage_api_secret
STORAGE_BUCKET=your_bucket
STORAGE_REGION=us-east-1
STORAGE_TIMEOUT=30

# Monitoreo
MONITORING_PROVIDER=datadog
MONITORING_API_KEY=your_monitoring_api_key
MONITORING_API_SECRET=your_monitoring_api_secret
MONITORING_TIMEOUT=30
```

## 🛠️ Uso de la API

### Middleware

#### SecurityMiddleware
```php
// Aplicar middleware de seguridad
Route::middleware(['security'])->group(function () {
    // Rutas protegidas
});
```

#### PerformanceMiddleware
```php
// Aplicar middleware de rendimiento
Route::middleware(['performance'])->group(function () {
    // Rutas monitoreadas
});
```

#### LoggingMiddleware
```php
// Aplicar middleware de logging
Route::middleware(['logging'])->group(function () {
    // Rutas con logging
});
```

### Jobs

#### Despachar Jobs
```php
use App\Jobs\SystemIntegrationJob;
use App\Jobs\LoggingJob;
use App\Jobs\BackupJob;
use App\Jobs\NotificationJob;
use App\Jobs\CleanupJob;

// Despachar job de integración del sistema
SystemIntegrationJob::dispatch('system_health_check', ['test' => true], 1);

// Despachar job de logging
LoggingJob::dispatch('system_log', ['event' => 'test'], 'info', 'daily');

// Despachar job de respaldo
BackupJob::dispatch('database', ['name' => 'Test Backup'], 1, 30);

// Despachar job de notificación
NotificationJob::dispatch('system_alert', ['title' => 'Test'], 1, ['database']);

// Despachar job de limpieza
CleanupJob::dispatch('full_cleanup', ['test' => true], 30);
```

### Comandos Artisan

#### Estado del Sistema
```bash
# Verificar estado del sistema
php artisan system:status

# Verificar estado con detalles
php artisan system:status --detailed

# Exportar estado a JSON
php artisan system:status --json

# Guardar estado
php artisan system:status --save
```

#### Modo Mantenimiento
```bash
# Activar modo mantenimiento
php artisan system:maintenance start

# Desactivar modo mantenimiento
php artisan system:maintenance stop

# Verificar estado de mantenimiento
php artisan system:maintenance status
```

#### Monitoreo
```bash
# Iniciar monitoreo
php artisan system:monitor start

# Detener monitoreo
php artisan system:monitor stop

# Verificar estado de monitoreo
php artisan system:monitor status

# Verificar salud del sistema
php artisan system:monitor health

# Ver alertas
php artisan system:monitor alerts
```

#### Gestión de Respaldos
```bash
# Listar respaldos
php artisan backup:manage list

# Crear respaldo
php artisan backup:manage create

# Crear respaldo con retención
php artisan backup:manage create --retention=30

# Verificar respaldos
php artisan backup:manage verify

# Programar respaldos
php artisan backup:manage schedule
```

#### Gestión de Notificaciones
```bash
# Listar notificaciones
php artisan notification:manage list

# Enviar notificación
php artisan notification:manage send

# Enviar notificación a canales específicos
php artisan notification:manage send --channels=email,sms

# Probar notificaciones
php artisan notification:manage test

# Programar notificaciones
php artisan notification:manage schedule
```

#### Gestión de Limpieza
```bash
# Verificar estado de limpieza
php artisan cleanup:manage status

# Ejecutar limpieza
php artisan cleanup:manage run --type=logs

# Programar limpieza
php artisan cleanup:manage schedule
```

#### Gestión de Jobs
```bash
# Verificar estado de jobs
php artisan jobs:manage status

# Despachar jobs
php artisan jobs:manage dispatch

# Limpiar jobs
php artisan jobs:manage clear

# Reintentar jobs fallidos
php artisan jobs:manage retry
```

#### Workers
```bash
# Iniciar workers
php artisan workers:start --workers=1 --timeout=30

# Detener workers
php artisan workers:stop

# Reiniciar workers
php artisan workers:restart

# Verificar estado de workers
php artisan workers:status
```

### Servicios Externos

#### API Externa
```php
use App\Services\ExternalApiService;

$apiService = new ExternalApiService();

// GET request
$response = $apiService->get('endpoint');

// POST request
$response = $apiService->post('endpoint', ['data' => 'value']);

// PUT request
$response = $apiService->put('endpoint', ['data' => 'value']);

// DELETE request
$response = $apiService->delete('endpoint');

// Enviar webhook
$response = $apiService->sendWebhook('https://webhook.example.com', ['data' => 'value']);

// Verificar salud
$health = $apiService->checkHealth();

// Obtener estadísticas
$stats = $apiService->getStats();
```

#### Email
```php
use App\Services\ExternalEmailService;

$emailService = new ExternalEmailService();

// Enviar email simple
$response = $emailService->sendEmail('test@example.com', 'Subject', 'Message');

// Enviar email con plantilla
$response = $emailService->sendTemplateEmail('test@example.com', 'template', ['name' => 'John']);

// Enviar email masivo
$response = $emailService->sendBulkEmail([
    ['email' => 'user1@example.com', 'name' => 'User 1'],
    ['email' => 'user2@example.com', 'name' => 'User 2']
], 'Subject', 'Message');

// Enviar email con adjuntos
$response = $emailService->sendEmailWithAttachments('test@example.com', 'Subject', 'Message', ['file.pdf']);

// Verificar salud
$health = $emailService->checkHealth();

// Obtener estadísticas
$stats = $emailService->getStats();
```

#### SMS
```php
use App\Services\ExternalSmsService;

$smsService = new ExternalSmsService();

// Enviar SMS simple
$response = $smsService->sendSms('+1234567890', 'Message');

// Enviar SMS con plantilla
$response = $smsService->sendTemplateSms('+1234567890', 'template', ['name' => 'John']);

// Enviar SMS masivo
$response = $smsService->sendBulkSms([
    ['phone' => '+1234567890', 'message' => 'Message 1'],
    ['phone' => '+0987654321', 'message' => 'Message 2']
]);

// Verificar salud
$health = $smsService->checkHealth();

// Obtener estadísticas
$stats = $smsService->getStats();
```

#### Push Notifications
```php
use App\Services\ExternalPushService;

$pushService = new ExternalPushService();

// Enviar push simple
$response = $pushService->sendPush('device-token', 'Title', 'Message');

// Enviar push masivo
$response = $pushService->sendBulkPush([
    ['token' => 'token1', 'title' => 'Title 1', 'message' => 'Message 1'],
    ['token' => 'token2', 'title' => 'Title 2', 'message' => 'Message 2']
]);

// Enviar push por topic
$response = $pushService->sendPushToTopic('topic', 'Title', 'Message');

// Suscribir a topic
$response = $pushService->subscribeToTopic('device-token', 'topic');

// Desuscribir de topic
$response = $pushService->unsubscribeFromTopic('device-token', 'topic');

// Verificar salud
$health = $pushService->checkHealth();

// Obtener estadísticas
$stats = $pushService->getStats();
```

#### Almacenamiento
```php
use App\Services\ExternalStorageService;

$storageService = new ExternalStorageService();

// Subir archivo
$response = $storageService->uploadFile('/path/to/file.txt', 'remote/file.txt');

// Descargar archivo
$response = $storageService->downloadFile('remote/file.txt', '/path/to/download.txt');

// Eliminar archivo
$response = $storageService->deleteFile('remote/file.txt');

// Obtener URL pública
$url = $storageService->getPublicUrl('remote/file.txt');

// Listar archivos
$files = $storageService->listFiles('folder/');

// Verificar salud
$health = $storageService->checkHealth();

// Obtener estadísticas
$stats = $storageService->getStats();
```

#### Monitoreo
```php
use App\Services\ExternalMonitoringService;

$monitoringService = new ExternalMonitoringService();

// Enviar métrica
$response = $monitoringService->sendMetric('test.metric', 42.5);

// Enviar evento
$response = $monitoringService->sendEvent('test.event', ['data' => 'value']);

// Enviar log
$response = $monitoringService->sendLog('test.log', 'info', ['message' => 'test']);

// Enviar alerta
$response = $monitoringService->sendAlert('test.alert', 'warning', 'Test alert');

// Verificar salud
$health = $monitoringService->checkHealth();

// Obtener estadísticas
$stats = $monitoringService->getStats();
```

## 🔧 Optimización

### Optimización de Base de Datos
```php
use App\Services\DatabaseOptimizationService;

$dbOptimization = new DatabaseOptimizationService();

// Optimizar índices
$result = $dbOptimization->optimizeIndexes();

// Optimizar consultas lentas
$result = $dbOptimization->optimizeSlowQueries();

// Optimizar tablas
$result = $dbOptimization->optimizeTables();

// Limpiar datos obsoletos
$result = $dbOptimization->cleanupObsoleteData();

// Analizar rendimiento
$result = $dbOptimization->analyzePerformance();

// Optimizar configuración
$result = $dbOptimization->optimizeConfiguration();
```

### Optimización de Cache
```php
use App\Services\CacheOptimizationService;

$cacheOptimization = new CacheOptimizationService();

// Optimizar cache general
$result = $cacheOptimization->optimizeCache();

// Optimizar cache de Redis
$result = $cacheOptimization->optimizeRedisCache();

// Optimizar cache de base de datos
$result = $cacheOptimization->optimizeDatabaseCache();

// Optimizar cache de sesiones
$result = $cacheOptimization->optimizeSessionCache();

// Analizar rendimiento de cache
$result = $cacheOptimization->analyzeCachePerformance();
```

### Optimización de Consultas
```php
use App\Services\QueryOptimizationService;

$queryOptimization = new QueryOptimizationService();

// Optimizar consultas lentas
$result = $queryOptimization->optimizeSlowQueries();

// Optimizar consultas N+1
$result = $queryOptimization->optimizeNPlusOneQueries();

// Optimizar consultas con joins
$result = $queryOptimization->optimizeJoinQueries();

// Optimizar consultas con subconsultas
$result = $queryOptimization->optimizeSubqueryQueries();

// Analizar rendimiento de consultas
$result = $queryOptimization->analyzeQueryPerformance();
```

### Optimización de Memoria
```php
use App\Services\MemoryOptimizationService;

$memoryOptimization = new MemoryOptimizationService();

// Optimizar memoria general
$result = $memoryOptimization->optimizeMemory();

// Optimizar memoria de PHP
$result = $memoryOptimization->optimizePhpMemory();

// Optimizar memoria de Redis
$result = $memoryOptimization->optimizeRedisMemory();

// Analizar uso de memoria
$result = $memoryOptimization->analyzeMemoryUsage();
```

### Optimización de Archivos
```php
use App\Services\FileOptimizationService;

$fileOptimization = new FileOptimizationService();

// Optimizar archivos general
$result = $fileOptimization->optimizeFiles();

// Optimizar archivos de log
$result = $fileOptimization->optimizeLogFiles();

// Optimizar archivos de cache
$result = $fileOptimization->optimizeCacheFiles();

// Optimizar archivos de sesión
$result = $fileOptimization->optimizeSessionFiles();

// Analizar uso de archivos
$result = $fileOptimization->analyzeFileUsage();
```

### Optimización de Jobs
```php
use App\Services\JobOptimizationService;

$jobOptimization = new JobOptimizationService();

// Optimizar jobs general
$result = $jobOptimization->optimizeJobs();

// Optimizar colas
$result = $jobOptimization->optimizeQueues();

// Optimizar workers
$result = $jobOptimization->optimizeWorkers();

// Optimizar retry
$result = $jobOptimization->optimizeRetry();

// Analizar rendimiento de jobs
$result = $jobOptimization->analyzeJobPerformance();
```

### Optimización de Servicios Externos
```php
use App\Services\ExternalServiceOptimizationService;

$externalOptimization = new ExternalServiceOptimizationService();

// Optimizar servicios externos general
$result = $externalOptimization->optimizeExternalServices();

// Optimizar APIs
$result = $externalOptimization->optimizeApis();

// Optimizar servicios de email
$result = $externalOptimization->optimizeEmailServices();

// Optimizar servicios de SMS
$result = $externalOptimization->optimizeSmsServices();

// Optimizar servicios de push
$result = $externalOptimization->optimizePushServices();

// Optimizar servicios de almacenamiento
$result = $externalOptimization->optimizeStorageServices();

// Optimizar servicios de monitoreo
$result = $externalOptimization->optimizeMonitoringServices();

// Analizar rendimiento de servicios externos
$result = $externalOptimization->analyzeExternalServicePerformance();
```

## 🧪 Testing

### Ejecutar Tests
```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test tests/Feature/SystemIntegrationTest.php
php artisan test tests/Feature/JobsIntegrationTest.php
php artisan test tests/Feature/CommandsIntegrationTest.php
php artisan test tests/Feature/ExternalServicesTest.php
php artisan test tests/Feature/OptimizationTest.php

# Ejecutar tests con cobertura
php artisan test --coverage
```

### Tests Disponibles
- **SystemIntegrationTest**: Tests de integración del sistema
- **JobsIntegrationTest**: Tests de jobs
- **CommandsIntegrationTest**: Tests de comandos artisan
- **ExternalServicesTest**: Tests de servicios externos
- **OptimizationTest**: Tests de optimización
- **MiddlewareIntegrationTest**: Tests de middleware
- **CompleteSystemTest**: Tests del sistema completo

## 📊 Monitoreo

### Métricas Disponibles
- **Rendimiento**: Tiempo de respuesta, uso de memoria
- **Base de Datos**: Consultas lentas, uso de índices
- **Cache**: Tasa de aciertos, uso de memoria
- **Jobs**: Tiempo de procesamiento, tasa de éxito
- **Servicios Externos**: Tiempo de respuesta, tasa de éxito

### Alertas
- **Sistema**: Alertas de salud del sistema
- **Rendimiento**: Alertas de rendimiento
- **Errores**: Alertas de errores
- **Recursos**: Alertas de uso de recursos

## 🔒 Seguridad

### Características de Seguridad
- **Middleware de Seguridad**: Protección contra ataques
- **Validación de Entrada**: Sanitización de datos
- **Autenticación**: Sistema de autenticación robusto
- **Autorización**: Control de acceso granular
- **Encriptación**: Encriptación de datos sensibles
- **Logs de Seguridad**: Registro de eventos de seguridad

## 🚀 Deployment

### Requisitos de Producción
- **Servidor Web**: Apache/Nginx
- **PHP**: >= 8.1 con extensiones requeridas
- **Base de Datos**: MySQL >= 8.0
- **Cache**: Redis
- **Queue**: Redis/Database
- **Almacenamiento**: Local/S3/Google Cloud

### Pasos de Deployment
1. **Configurar servidor**
2. **Instalar dependencias**
3. **Configurar base de datos**
4. **Configurar cache y queue**
5. **Configurar servicios externos**
6. **Ejecutar migraciones**
7. **Configurar workers**
8. **Configurar monitoreo**

## 📞 Soporte

### Contacto
- **Email**: soporte@modustack.com
- **Documentación**: https://docs.modustack.com
- **Issues**: https://github.com/tu-usuario/ModuStackElyMarLuxury/issues

### Recursos Adicionales
- **Wiki**: https://github.com/tu-usuario/ModuStackElyMarLuxury/wiki
- **Ejemplos**: https://github.com/tu-usuario/ModuStackElyMarLuxury/examples
- **Tutoriales**: https://github.com/tu-usuario/ModuStackElyMarLuxury/tutorials

---

**ModuStackElyMarLuxury** - Sistema completo de gestión empresarial

