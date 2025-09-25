# 🔧 Documentación de Servicios - ModuStackElyMarLuxury

## 📋 Descripción General

Esta documentación cubre todos los servicios implementados en ModuStackElyMarLuxury, incluyendo middleware, jobs, comandos artisan, servicios externos y optimización.

## 🛡️ Middleware

### SecurityMiddleware

**Ubicación**: `app/Http/Middleware/SecurityMiddleware.php`

**Propósito**: Proporcionar protección de seguridad para las rutas de la aplicación.

**Características**:
- Protección contra ataques XSS
- Validación de entrada
- Sanitización de datos
- Headers de seguridad
- Rate limiting
- Protección CSRF

**Uso**:
```php
// En routes/web.php
Route::middleware(['security'])->group(function () {
    Route::get('/admin', 'AdminController@index');
});
```

**Configuración**:
```php
// En app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\SecurityMiddleware::class,
    ],
];
```

### PerformanceMiddleware

**Ubicación**: `app/Http/Middleware/PerformanceMiddleware.php`

**Propósito**: Monitorear y optimizar el rendimiento de las rutas.

**Características**:
- Medición de tiempo de respuesta
- Monitoreo de uso de memoria
- Análisis de rendimiento
- Métricas de performance
- Alertas de rendimiento

**Uso**:
```php
Route::middleware(['performance'])->group(function () {
    Route::get('/api/data', 'ApiController@getData');
});
```

### LoggingMiddleware

**Ubicación**: `app/Http/Middleware/LoggingMiddleware.php`

**Propósito**: Registrar eventos y actividades de la aplicación.

**Características**:
- Logging de requests
- Logging de responses
- Logging de errores
- Logging de actividades
- Análisis de logs

**Uso**:
```php
Route::middleware(['logging'])->group(function () {
    Route::post('/api/action', 'ApiController@action');
});
```

## ⚡ Jobs

### SystemIntegrationJob

**Ubicación**: `app/Jobs/SystemIntegrationJob.php`

**Propósito**: Manejar tareas de integración del sistema.

**Características**:
- Verificación de salud del sistema
- Sincronización de datos
- Integración con servicios externos
- Monitoreo de componentes
- Mantenimiento automático

**Uso**:
```php
use App\Jobs\SystemIntegrationJob;

// Despachar job
SystemIntegrationJob::dispatch('system_health_check', ['test' => true], 1);

// Con retraso
SystemIntegrationJob::dispatch('system_health_check', ['test' => true], 1)
    ->delay(now()->addMinutes(5));
```

### LoggingJob

**Ubicación**: `app/Jobs/LoggingJob.php`

**Propósito**: Procesar logs de manera asíncrona.

**Características**:
- Procesamiento de logs
- Análisis de eventos
- Almacenamiento de logs
- Limpieza de logs antiguos
- Reportes de logs

**Uso**:
```php
use App\Jobs\LoggingJob;

// Despachar job de logging
LoggingJob::dispatch('system_log', ['event' => 'test'], 'info', 'daily');
```

### BackupJob

**Ubicación**: `app/Jobs/BackupJob.php`

**Propósito**: Crear y gestionar respaldos del sistema.

**Características**:
- Respaldo de base de datos
- Respaldo de archivos
- Respaldo completo del sistema
- Compresión de respaldos
- Almacenamiento en la nube

**Uso**:
```php
use App\Jobs\BackupJob;

// Crear respaldo
BackupJob::dispatch('database', ['name' => 'Test Backup'], 1, 30);
```

### NotificationJob

**Ubicación**: `app/Jobs/NotificationJob.php`

**Propósito**: Enviar notificaciones de manera asíncrona.

**Características**:
- Notificaciones por email
- Notificaciones por SMS
- Notificaciones push
- Notificaciones masivas
- Programación de notificaciones

**Uso**:
```php
use App\Jobs\NotificationJob;

// Enviar notificación
NotificationJob::dispatch('system_alert', ['title' => 'Test'], 1, ['database']);
```

### CleanupJob

**Ubicación**: `app/Jobs/CleanupJob.php`

**Propósito**: Limpiar datos obsoletos y temporales.

**Características**:
- Limpieza de logs antiguos
- Limpieza de cache expirado
- Limpieza de sesiones
- Limpieza de archivos temporales
- Limpieza de base de datos

**Uso**:
```php
use App\Jobs\CleanupJob;

// Ejecutar limpieza
CleanupJob::dispatch('full_cleanup', ['test' => true], 30);
```

## 🎯 Comandos Artisan

### System Commands

#### system:status
**Propósito**: Verificar el estado del sistema.

**Opciones**:
- `--detailed`: Mostrar información detallada
- `--json`: Exportar en formato JSON
- `--save`: Guardar estado en archivo

**Uso**:
```bash
php artisan system:status
php artisan system:status --detailed
php artisan system:status --json
php artisan system:status --save
```

#### system:maintenance
**Propósito**: Gestionar el modo mantenimiento.

**Subcomandos**:
- `start`: Activar modo mantenimiento
- `stop`: Desactivar modo mantenimiento
- `status`: Verificar estado

**Uso**:
```bash
php artisan system:maintenance start
php artisan system:maintenance stop
php artisan system:maintenance status
```

#### system:monitor
**Propósito**: Monitorear el sistema.

**Subcomandos**:
- `start`: Iniciar monitoreo
- `stop`: Detener monitoreo
- `status`: Verificar estado
- `health`: Verificar salud
- `alerts`: Ver alertas

**Uso**:
```bash
php artisan system:monitor start
php artisan system:monitor stop
php artisan system:monitor status
php artisan system:monitor health
php artisan system:monitor alerts
```

### Backup Commands

#### backup:manage
**Propósito**: Gestionar respaldos.

**Subcomandos**:
- `list`: Listar respaldos
- `create`: Crear respaldo
- `verify`: Verificar respaldos
- `schedule`: Programar respaldos

**Opciones**:
- `--retention`: Días de retención
- `--type`: Tipo de respaldo
- `--compress`: Comprimir respaldo

**Uso**:
```bash
php artisan backup:manage list
php artisan backup:manage create
php artisan backup:manage create --retention=30
php artisan backup:manage verify
php artisan backup:manage schedule
```

### Notification Commands

#### notification:manage
**Propósito**: Gestionar notificaciones.

**Subcomandos**:
- `list`: Listar notificaciones
- `send`: Enviar notificación
- `test`: Probar notificaciones
- `schedule`: Programar notificaciones

**Opciones**:
- `--channels`: Canales de notificación
- `--template`: Plantilla a usar
- `--priority`: Prioridad de notificación

**Uso**:
```bash
php artisan notification:manage list
php artisan notification:manage send
php artisan notification:manage send --channels=email,sms
php artisan notification:manage test
php artisan notification:manage schedule
```

### Cleanup Commands

#### cleanup:manage
**Propósito**: Gestionar limpieza del sistema.

**Subcomandos**:
- `status`: Verificar estado
- `run`: Ejecutar limpieza
- `schedule`: Programar limpieza

**Opciones**:
- `--type`: Tipo de limpieza
- `--force`: Forzar limpieza
- `--dry-run`: Simular limpieza

**Uso**:
```bash
php artisan cleanup:manage status
php artisan cleanup:manage run --type=logs
php artisan cleanup:manage schedule
```

### Job Commands

#### jobs:manage
**Propósito**: Gestionar jobs.

**Subcomandos**:
- `status`: Verificar estado
- `dispatch`: Despachar jobs
- `clear`: Limpiar jobs
- `retry`: Reintentar jobs fallidos

**Opciones**:
- `--queue`: Cola específica
- `--priority`: Prioridad
- `--timeout`: Timeout

**Uso**:
```bash
php artisan jobs:manage status
php artisan jobs:manage dispatch
php artisan jobs:manage clear
php artisan jobs:manage retry
```

### Worker Commands

#### workers:start
**Propósito**: Iniciar workers.

**Opciones**:
- `--workers`: Número de workers
- `--timeout`: Timeout de workers
- `--queue`: Cola específica
- `--memory`: Límite de memoria

**Uso**:
```bash
php artisan workers:start --workers=1 --timeout=30
php artisan workers:start --workers=4 --timeout=60 --queue=high
```

#### workers:stop
**Propósito**: Detener workers.

**Uso**:
```bash
php artisan workers:stop
```

#### workers:restart
**Propósito**: Reiniciar workers.

**Uso**:
```bash
php artisan workers:restart
```

#### workers:status
**Propósito**: Verificar estado de workers.

**Uso**:
```bash
php artisan workers:status
```

## 🌐 Servicios Externos

### ExternalApiService

**Ubicación**: `app/Services/ExternalApiService.php`

**Propósito**: Interactuar con APIs externas.

**Métodos**:
- `get($endpoint)`: GET request
- `post($endpoint, $data)`: POST request
- `put($endpoint, $data)`: PUT request
- `delete($endpoint)`: DELETE request
- `sendWebhook($url, $data)`: Enviar webhook
- `checkHealth()`: Verificar salud
- `getStats()`: Obtener estadísticas

**Configuración**:
```env
EXTERNAL_API_BASE_URL=https://api.example.com
EXTERNAL_API_KEY=your_api_key
EXTERNAL_API_TIMEOUT=30
EXTERNAL_API_RETRY_ATTEMPTS=3
```

### ExternalEmailService

**Ubicación**: `app/Services/ExternalEmailService.php`

**Propósito**: Enviar emails a través de proveedores externos.

**Métodos**:
- `sendEmail($to, $subject, $message)`: Email simple
- `sendTemplateEmail($to, $template, $data)`: Email con plantilla
- `sendBulkEmail($recipients, $subject, $message)`: Email masivo
- `sendEmailWithAttachments($to, $subject, $message, $attachments)`: Email con adjuntos
- `checkHealth()`: Verificar salud
- `getStats()`: Obtener estadísticas

**Configuración**:
```env
MAIL_EXTERNAL_PROVIDER=smtp
MAIL_EXTERNAL_API_KEY=your_email_api_key
MAIL_TIMEOUT=30
```

### ExternalSmsService

**Ubicación**: `app/Services/ExternalSmsService.php`

**Propósito**: Enviar SMS a través de proveedores externos.

**Métodos**:
- `sendSms($phone, $message)`: SMS simple
- `sendTemplateSms($phone, $template, $data)`: SMS con plantilla
- `sendBulkSms($recipients)`: SMS masivo
- `checkHealth()`: Verificar salud
- `getStats()`: Obtener estadísticas

**Configuración**:
```env
SMS_PROVIDER=twilio
SMS_API_KEY=your_sms_api_key
SMS_API_SECRET=your_sms_api_secret
SMS_FROM_NUMBER=+1234567890
SMS_TIMEOUT=30
```

### ExternalPushService

**Ubicación**: `app/Services/ExternalPushService.php`

**Propósito**: Enviar notificaciones push.

**Métodos**:
- `sendPush($token, $title, $message)`: Push simple
- `sendBulkPush($recipients)`: Push masivo
- `sendPushToTopic($topic, $title, $message)`: Push por topic
- `subscribeToTopic($token, $topic)`: Suscribir a topic
- `unsubscribeFromTopic($token, $topic)`: Desuscribir de topic
- `checkHealth()`: Verificar salud
- `getStats()`: Obtener estadísticas

**Configuración**:
```env
PUSH_PROVIDER=fcm
PUSH_API_KEY=your_push_api_key
PUSH_API_SECRET=your_push_api_secret
PUSH_TIMEOUT=30
```

### ExternalStorageService

**Ubicación**: `app/Services/ExternalStorageService.php`

**Propósito**: Gestionar almacenamiento en la nube.

**Métodos**:
- `uploadFile($localPath, $remotePath)`: Subir archivo
- `downloadFile($remotePath, $localPath)`: Descargar archivo
- `deleteFile($remotePath)`: Eliminar archivo
- `getPublicUrl($remotePath)`: Obtener URL pública
- `listFiles($folder)`: Listar archivos
- `checkHealth()`: Verificar salud
- `getStats()`: Obtener estadísticas

**Configuración**:
```env
STORAGE_EXTERNAL_PROVIDER=aws_s3
STORAGE_API_KEY=your_storage_api_key
STORAGE_API_SECRET=your_storage_api_secret
STORAGE_BUCKET=your_bucket
STORAGE_REGION=us-east-1
STORAGE_TIMEOUT=30
```

### ExternalMonitoringService

**Ubicación**: `app/Services/ExternalMonitoringService.php`

**Propósito**: Enviar datos de monitoreo.

**Métodos**:
- `sendMetric($name, $value)`: Enviar métrica
- `sendEvent($name, $data)`: Enviar evento
- `sendLog($name, $level, $data)`: Enviar log
- `sendAlert($name, $level, $message)`: Enviar alerta
- `checkHealth()`: Verificar salud
- `getStats()`: Obtener estadísticas

**Configuración**:
```env
MONITORING_PROVIDER=datadog
MONITORING_API_KEY=your_monitoring_api_key
MONITORING_API_SECRET=your_monitoring_api_secret
MONITORING_TIMEOUT=30
```

## 🔧 Servicios de Optimización

### DatabaseOptimizationService

**Ubicación**: `app/Services/DatabaseOptimizationService.php`

**Propósito**: Optimizar la base de datos.

**Métodos**:
- `optimizeIndexes()`: Optimizar índices
- `optimizeSlowQueries()`: Optimizar consultas lentas
- `optimizeTables()`: Optimizar tablas
- `cleanupObsoleteData()`: Limpiar datos obsoletos
- `analyzePerformance()`: Analizar rendimiento
- `optimizeConfiguration()`: Optimizar configuración

### CacheOptimizationService

**Ubicación**: `app/Services/CacheOptimizationService.php`

**Propósito**: Optimizar el cache.

**Métodos**:
- `optimizeCache()`: Optimizar cache general
- `optimizeRedisCache()`: Optimizar cache de Redis
- `optimizeDatabaseCache()`: Optimizar cache de base de datos
- `optimizeSessionCache()`: Optimizar cache de sesiones
- `analyzeCachePerformance()`: Analizar rendimiento de cache

### QueryOptimizationService

**Ubicación**: `app/Services/QueryOptimizationService.php`

**Propósito**: Optimizar consultas de base de datos.

**Métodos**:
- `optimizeSlowQueries()`: Optimizar consultas lentas
- `optimizeNPlusOneQueries()`: Optimizar consultas N+1
- `optimizeJoinQueries()`: Optimizar consultas con joins
- `optimizeSubqueryQueries()`: Optimizar consultas con subconsultas
- `analyzeQueryPerformance()`: Analizar rendimiento de consultas

### MemoryOptimizationService

**Ubicación**: `app/Services/MemoryOptimizationService.php`

**Propósito**: Optimizar el uso de memoria.

**Métodos**:
- `optimizeMemory()`: Optimizar memoria general
- `optimizePhpMemory()`: Optimizar memoria de PHP
- `optimizeRedisMemory()`: Optimizar memoria de Redis
- `analyzeMemoryUsage()`: Analizar uso de memoria

### FileOptimizationService

**Ubicación**: `app/Services/FileOptimizationService.php`

**Propósito**: Optimizar archivos del sistema.

**Métodos**:
- `optimizeFiles()`: Optimizar archivos general
- `optimizeLogFiles()`: Optimizar archivos de log
- `optimizeCacheFiles()`: Optimizar archivos de cache
- `optimizeSessionFiles()`: Optimizar archivos de sesión
- `analyzeFileUsage()`: Analizar uso de archivos

### JobOptimizationService

**Ubicación**: `app/Services/JobOptimizationService.php`

**Propósito**: Optimizar jobs y colas.

**Métodos**:
- `optimizeJobs()`: Optimizar jobs general
- `optimizeQueues()`: Optimizar colas
- `optimizeWorkers()`: Optimizar workers
- `optimizeRetry()`: Optimizar retry
- `analyzeJobPerformance()`: Analizar rendimiento de jobs

### ExternalServiceOptimizationService

**Ubicación**: `app/Services/ExternalServiceOptimizationService.php`

**Propósito**: Optimizar servicios externos.

**Métodos**:
- `optimizeExternalServices()`: Optimizar servicios externos general
- `optimizeApis()`: Optimizar APIs
- `optimizeEmailServices()`: Optimizar servicios de email
- `optimizeSmsServices()`: Optimizar servicios de SMS
- `optimizePushServices()`: Optimizar servicios de push
- `optimizeStorageServices()`: Optimizar servicios de almacenamiento
- `optimizeMonitoringServices()`: Optimizar servicios de monitoreo
- `analyzeExternalServicePerformance()`: Analizar rendimiento de servicios externos

## 🧪 Testing

### Test Suites

#### SystemIntegrationTest
**Ubicación**: `tests/Feature/SystemIntegrationTest.php`
**Propósito**: Tests de integración del sistema completo.

#### JobsIntegrationTest
**Ubicación**: `tests/Feature/JobsIntegrationTest.php`
**Propósito**: Tests de jobs y procesamiento asíncrono.

#### CommandsIntegrationTest
**Ubicación**: `tests/Feature/CommandsIntegrationTest.php`
**Propósito**: Tests de comandos artisan.

#### ExternalServicesTest
**Ubicación**: `tests/Feature/ExternalServicesTest.php`
**Propósito**: Tests de servicios externos.

#### OptimizationTest
**Ubicación**: `tests/Feature/OptimizationTest.php`
**Propósito**: Tests de servicios de optimización.

#### MiddlewareIntegrationTest
**Ubicación**: `tests/Feature/MiddlewareIntegrationTest.php`
**Propósito**: Tests de middleware.

#### CompleteSystemTest
**Ubicación**: `tests/Feature/CompleteSystemTest.php`
**Propósito**: Tests del sistema completo.

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

# Ejecutar tests en paralelo
php artisan test --parallel
```

## 📊 Monitoreo y Métricas

### Métricas Disponibles

#### Sistema
- **Uptime**: Tiempo de actividad
- **Response Time**: Tiempo de respuesta
- **Memory Usage**: Uso de memoria
- **CPU Usage**: Uso de CPU
- **Disk Usage**: Uso de disco

#### Base de Datos
- **Query Time**: Tiempo de consultas
- **Slow Queries**: Consultas lentas
- **Connection Count**: Número de conexiones
- **Index Usage**: Uso de índices

#### Cache
- **Hit Rate**: Tasa de aciertos
- **Miss Rate**: Tasa de fallos
- **Memory Usage**: Uso de memoria
- **Key Count**: Número de claves

#### Jobs
- **Processing Time**: Tiempo de procesamiento
- **Success Rate**: Tasa de éxito
- **Failure Rate**: Tasa de fallos
- **Queue Size**: Tamaño de cola

#### Servicios Externos
- **Response Time**: Tiempo de respuesta
- **Success Rate**: Tasa de éxito
- **Error Rate**: Tasa de errores
- **Availability**: Disponibilidad

### Alertas

#### Sistema
- **High Memory Usage**: Uso alto de memoria
- **Slow Response Time**: Tiempo de respuesta lento
- **High CPU Usage**: Uso alto de CPU
- **Disk Space Low**: Espacio en disco bajo

#### Base de Datos
- **Slow Queries**: Consultas lentas
- **High Connection Count**: Alto número de conexiones
- **Index Issues**: Problemas con índices

#### Cache
- **Low Hit Rate**: Tasa de aciertos baja
- **High Memory Usage**: Uso alto de memoria
- **Expired Keys**: Claves expiradas

#### Jobs
- **High Failure Rate**: Alta tasa de fallos
- **Long Processing Time**: Tiempo de procesamiento largo
- **Queue Backup**: Cola respaldada

#### Servicios Externos
- **Service Unavailable**: Servicio no disponible
- **High Error Rate**: Alta tasa de errores
- **Slow Response Time**: Tiempo de respuesta lento

## 🔒 Seguridad

### Características de Seguridad

#### Middleware de Seguridad
- **XSS Protection**: Protección contra XSS
- **CSRF Protection**: Protección contra CSRF
- **Input Validation**: Validación de entrada
- **Data Sanitization**: Sanitización de datos
- **Rate Limiting**: Limitación de velocidad

#### Autenticación
- **User Authentication**: Autenticación de usuarios
- **Session Management**: Gestión de sesiones
- **Password Security**: Seguridad de contraseñas
- **Two-Factor Authentication**: Autenticación de dos factores

#### Autorización
- **Role-Based Access**: Acceso basado en roles
- **Permission Management**: Gestión de permisos
- **Resource Protection**: Protección de recursos
- **API Security**: Seguridad de API

#### Encriptación
- **Data Encryption**: Encriptación de datos
- **Communication Security**: Seguridad de comunicación
- **Key Management**: Gestión de claves
- **Secure Storage**: Almacenamiento seguro

#### Logs de Seguridad
- **Security Events**: Eventos de seguridad
- **Access Logs**: Logs de acceso
- **Error Logs**: Logs de errores
- **Audit Trail**: Rastro de auditoría

## 🚀 Deployment

### Requisitos de Producción

#### Servidor
- **OS**: Ubuntu 20.04+ / CentOS 8+ / RHEL 8+
- **RAM**: 4GB mínimo, 8GB recomendado
- **CPU**: 2 cores mínimo, 4 cores recomendado
- **Disk**: 50GB mínimo, 100GB recomendado

#### Software
- **PHP**: >= 8.1
- **MySQL**: >= 8.0
- **Redis**: >= 6.0
- **Nginx**: >= 1.18 / Apache: >= 2.4
- **Composer**: >= 2.0
- **Node.js**: >= 16.0

#### Extensiones PHP
- **Required**: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml
- **Recommended**: redis, imagick, gd, curl, zip

### Pasos de Deployment

#### 1. Preparar Servidor
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
sudo apt install -y nginx mysql-server redis-server php8.1-fpm php8.1-mysql php8.1-redis php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

#### 2. Configurar Base de Datos
```bash
# Crear base de datos
mysql -u root -p
CREATE DATABASE modustack_elymar_luxury;
CREATE USER 'modustack'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON modustack_elymar_luxury.* TO 'modustack'@'localhost';
FLUSH PRIVILEGES;
```

#### 3. Configurar Aplicación
```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/ModuStackElyMarLuxury.git
cd ModuStackElyMarLuxury

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 4. Configurar Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/ModuStackElyMarLuxury/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### 5. Configurar Workers
```bash
# Crear supervisor config
sudo nano /etc/supervisor/conf.d/modustack-workers.conf

[program:modustack-workers]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/ModuStackElyMarLuxury/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/ModuStackElyMarLuxury/storage/logs/worker.log
stopwaitsecs=3600
```

#### 6. Configurar Cron
```bash
# Agregar tareas cron
crontab -e

* * * * * cd /path/to/ModuStackElyMarLuxury && php artisan schedule:run >> /dev/null 2>&1
```

#### 7. Ejecutar Migraciones
```bash
# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeders
php artisan db:seed --force

# Limpiar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### 8. Configurar Monitoreo
```bash
# Instalar herramientas de monitoreo
sudo apt install -y htop iotop nethogs

# Configurar logs
sudo nano /etc/logrotate.d/modustack

/path/to/ModuStackElyMarLuxury/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 644 www-data www-data
}
```

## 📞 Soporte y Mantenimiento

### Contacto
- **Email**: soporte@modustack.com
- **Documentación**: https://docs.modustack.com
- **Issues**: https://github.com/tu-usuario/ModuStackElyMarLuxury/issues

### Recursos Adicionales
- **Wiki**: https://github.com/tu-usuario/ModuStackElyMarLuxury/wiki
- **Ejemplos**: https://github.com/tu-usuario/ModuStackElyMarLuxury/examples
- **Tutoriales**: https://github.com/tu-usuario/ModuStackElyMarLuxury/tutorials

### Mantenimiento Regular
- **Backups**: Respaldos diarios
- **Updates**: Actualizaciones de seguridad
- **Monitoring**: Monitoreo continuo
- **Optimization**: Optimización periódica
- **Security**: Auditorías de seguridad

---

**ModuStackElyMarLuxury** - Sistema completo de gestión empresarial

