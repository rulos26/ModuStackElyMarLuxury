# ⚙️ Documentación de Configuración - ModuStackElyMarLuxury

## 📋 Descripción General

Esta documentación cubre toda la configuración de ModuStackElyMarLuxury, incluyendo variables de entorno, configuración de servicios, middleware, jobs, comandos artisan y optimización.

## 🔧 Variables de Entorno

### Configuración Básica

#### Aplicación
```env
# .env
APP_NAME="ModuStackElyMarLuxury"
APP_ENV=production
APP_KEY=base64:generated_key
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_TIMEZONE=UTC
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

#### Base de Datos
```env
# Base de datos principal
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modustack_elymar_luxury
DB_USERNAME=modustack
DB_PASSWORD=secure_password

# Base de datos de testing
DB_TESTING_CONNECTION=sqlite
DB_TESTING_DATABASE=:memory:

# Base de datos de respaldo
DB_BACKUP_CONNECTION=mysql
DB_BACKUP_HOST=127.0.0.1
DB_BACKUP_PORT=3306
DB_BACKUP_DATABASE=modustack_elymar_luxury_backup
DB_BACKUP_USERNAME=modustack
DB_BACKUP_PASSWORD=secure_password
```

#### Cache y Sesiones
```env
# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=modustack
CACHE_TTL=3600

# Sesiones
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

#### Queue y Jobs
```env
# Queue
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database
QUEUE_RETRY_AFTER=90
QUEUE_MAX_TRIES=3
QUEUE_TIMEOUT=300

# Workers
QUEUE_WORKERS=4
QUEUE_WORKER_TIMEOUT=3600
QUEUE_WORKER_MEMORY=128
QUEUE_WORKER_SLEEP=3
```

#### Redis
```env
# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
REDIS_DB=0
REDIS_CLUSTER=false
REDIS_PREFIX=modustack
```

### Configuración de Servicios Externos

#### APIs Externas
```env
# API Externa
EXTERNAL_API_BASE_URL=https://api.example.com
EXTERNAL_API_KEY=your_api_key
EXTERNAL_API_SECRET=your_api_secret
EXTERNAL_API_TIMEOUT=30
EXTERNAL_API_RETRY_ATTEMPTS=3
EXTERNAL_API_RETRY_DELAY=1000
EXTERNAL_API_RATE_LIMIT=100
EXTERNAL_API_RATE_LIMIT_WINDOW=60
```

#### Email
```env
# Mail básico
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Mail externo
MAIL_EXTERNAL_PROVIDER=smtp
MAIL_EXTERNAL_API_KEY=your_email_api_key
MAIL_EXTERNAL_API_SECRET=your_email_api_secret
MAIL_EXTERNAL_BASE_URL=https://api.email-provider.com
MAIL_EXTERNAL_TIMEOUT=30
MAIL_EXTERNAL_RETRY_ATTEMPTS=3
MAIL_EXTERNAL_RATE_LIMIT=100
MAIL_EXTERNAL_RATE_LIMIT_WINDOW=60
```

#### SMS
```env
# SMS
SMS_PROVIDER=twilio
SMS_API_KEY=your_sms_api_key
SMS_API_SECRET=your_sms_api_secret
SMS_FROM_NUMBER=+1234567890
SMS_BASE_URL=https://api.twilio.com
SMS_TIMEOUT=30
SMS_RETRY_ATTEMPTS=3
SMS_RATE_LIMIT=100
SMS_RATE_LIMIT_WINDOW=60
```

#### Push Notifications
```env
# Push Notifications
PUSH_PROVIDER=fcm
PUSH_API_KEY=your_push_api_key
PUSH_API_SECRET=your_push_api_secret
PUSH_BASE_URL=https://fcm.googleapis.com
PUSH_TIMEOUT=30
PUSH_RETRY_ATTEMPTS=3
PUSH_RATE_LIMIT=100
PUSH_RATE_LIMIT_WINDOW=60
```

#### Almacenamiento
```env
# Almacenamiento
STORAGE_EXTERNAL_PROVIDER=aws_s3
STORAGE_API_KEY=your_storage_api_key
STORAGE_API_SECRET=your_storage_api_secret
STORAGE_BUCKET=your_bucket
STORAGE_REGION=us-east-1
STORAGE_BASE_URL=https://s3.amazonaws.com
STORAGE_TIMEOUT=30
STORAGE_RETRY_ATTEMPTS=3
STORAGE_RATE_LIMIT=100
STORAGE_RATE_LIMIT_WINDOW=60
```

#### Monitoreo
```env
# Monitoreo
MONITORING_PROVIDER=datadog
MONITORING_API_KEY=your_monitoring_api_key
MONITORING_API_SECRET=your_monitoring_api_secret
MONITORING_BASE_URL=https://api.datadoghq.com
MONITORING_TIMEOUT=30
MONITORING_RETRY_ATTEMPTS=3
MONITORING_RATE_LIMIT=100
MONITORING_RATE_LIMIT_WINDOW=60
```

### Configuración de Optimización

#### Optimización de Base de Datos
```env
# Optimización de base de datos
DB_OPTIMIZATION_ENABLED=true
DB_OPTIMIZATION_INTERVAL=3600
DB_OPTIMIZATION_THRESHOLD=2.0
DB_OPTIMIZATION_INDEXES=true
DB_OPTIMIZATION_QUERIES=true
DB_OPTIMIZATION_TABLES=true
DB_OPTIMIZATION_CLEANUP=true
```

#### Optimización de Cache
```env
# Optimización de cache
CACHE_OPTIMIZATION_ENABLED=true
CACHE_OPTIMIZATION_INTERVAL=1800
CACHE_OPTIMIZATION_THRESHOLD=0.8
CACHE_OPTIMIZATION_REDIS=true
CACHE_OPTIMIZATION_DATABASE=true
CACHE_OPTIMIZATION_SESSION=true
CACHE_OPTIMIZATION_CLEANUP=true
```

#### Optimización de Consultas
```env
# Optimización de consultas
QUERY_OPTIMIZATION_ENABLED=true
QUERY_OPTIMIZATION_INTERVAL=3600
QUERY_OPTIMIZATION_THRESHOLD=2.0
QUERY_OPTIMIZATION_SLOW_QUERIES=true
QUERY_OPTIMIZATION_N_PLUS_ONE=true
QUERY_OPTIMIZATION_JOINS=true
QUERY_OPTIMIZATION_SUBQUERIES=true
```

#### Optimización de Memoria
```env
# Optimización de memoria
MEMORY_OPTIMIZATION_ENABLED=true
MEMORY_OPTIMIZATION_INTERVAL=1800
MEMORY_OPTIMIZATION_THRESHOLD=0.8
MEMORY_OPTIMIZATION_PHP=true
MEMORY_OPTIMIZATION_REDIS=true
MEMORY_OPTIMIZATION_CLEANUP=true
```

#### Optimización de Archivos
```env
# Optimización de archivos
FILE_OPTIMIZATION_ENABLED=true
FILE_OPTIMIZATION_INTERVAL=3600
FILE_OPTIMIZATION_THRESHOLD=10485760
FILE_OPTIMIZATION_LOGS=true
FILE_OPTIMIZATION_CACHE=true
FILE_OPTIMIZATION_SESSIONS=true
FILE_OPTIMIZATION_CLEANUP=true
```

#### Optimización de Jobs
```env
# Optimización de jobs
JOB_OPTIMIZATION_ENABLED=true
JOB_OPTIMIZATION_INTERVAL=1800
JOB_OPTIMIZATION_THRESHOLD=100
JOB_OPTIMIZATION_QUEUES=true
JOB_OPTIMIZATION_WORKERS=true
JOB_OPTIMIZATION_RETRY=true
JOB_OPTIMIZATION_CLEANUP=true
```

#### Optimización de Servicios Externos
```env
# Optimización de servicios externos
EXTERNAL_SERVICE_OPTIMIZATION_ENABLED=true
EXTERNAL_SERVICE_OPTIMIZATION_INTERVAL=3600
EXTERNAL_SERVICE_OPTIMIZATION_THRESHOLD=30
EXTERNAL_SERVICE_OPTIMIZATION_APIS=true
EXTERNAL_SERVICE_OPTIMIZATION_EMAIL=true
EXTERNAL_SERVICE_OPTIMIZATION_SMS=true
EXTERNAL_SERVICE_OPTIMIZATION_PUSH=true
EXTERNAL_SERVICE_OPTIMIZATION_STORAGE=true
EXTERNAL_SERVICE_OPTIMIZATION_MONITORING=true
```

## 🛡️ Configuración de Middleware

### SecurityMiddleware

#### Configuración
```php
// config/middleware.php
'security' => [
    'enabled' => env('SECURITY_MIDDLEWARE_ENABLED', true),
    'xss_protection' => env('SECURITY_XSS_PROTECTION', true),
    'csrf_protection' => env('SECURITY_CSRF_PROTECTION', true),
    'input_validation' => env('SECURITY_INPUT_VALIDATION', true),
    'data_sanitization' => env('SECURITY_DATA_SANITIZATION', true),
    'security_headers' => env('SECURITY_HEADERS', true),
    'rate_limiting' => env('SECURITY_RATE_LIMITING', true),
    'rate_limit' => env('SECURITY_RATE_LIMIT', 100),
    'rate_limit_window' => env('SECURITY_RATE_LIMIT_WINDOW', 60),
],
```

#### Variables de Entorno
```env
# Security Middleware
SECURITY_MIDDLEWARE_ENABLED=true
SECURITY_XSS_PROTECTION=true
SECURITY_CSRF_PROTECTION=true
SECURITY_INPUT_VALIDATION=true
SECURITY_DATA_SANITIZATION=true
SECURITY_HEADERS=true
SECURITY_RATE_LIMITING=true
SECURITY_RATE_LIMIT=100
SECURITY_RATE_LIMIT_WINDOW=60
```

### PerformanceMiddleware

#### Configuración
```php
// config/middleware.php
'performance' => [
    'enabled' => env('PERFORMANCE_MIDDLEWARE_ENABLED', true),
    'response_time_monitoring' => env('PERFORMANCE_RESPONSE_TIME_MONITORING', true),
    'memory_monitoring' => env('PERFORMANCE_MEMORY_MONITORING', true),
    'performance_analysis' => env('PERFORMANCE_ANALYSIS', true),
    'performance_metrics' => env('PERFORMANCE_METRICS', true),
    'performance_alerts' => env('PERFORMANCE_ALERTS', true),
    'response_time_threshold' => env('PERFORMANCE_RESPONSE_TIME_THRESHOLD', 2.0),
    'memory_threshold' => env('PERFORMANCE_MEMORY_THRESHOLD', 0.8),
],
```

#### Variables de Entorno
```env
# Performance Middleware
PERFORMANCE_MIDDLEWARE_ENABLED=true
PERFORMANCE_RESPONSE_TIME_MONITORING=true
PERFORMANCE_MEMORY_MONITORING=true
PERFORMANCE_ANALYSIS=true
PERFORMANCE_METRICS=true
PERFORMANCE_ALERTS=true
PERFORMANCE_RESPONSE_TIME_THRESHOLD=2.0
PERFORMANCE_MEMORY_THRESHOLD=0.8
```

### LoggingMiddleware

#### Configuración
```php
// config/middleware.php
'logging' => [
    'enabled' => env('LOGGING_MIDDLEWARE_ENABLED', true),
    'request_logging' => env('LOGGING_REQUEST_LOGGING', true),
    'response_logging' => env('LOGGING_RESPONSE_LOGGING', true),
    'error_logging' => env('LOGGING_ERROR_LOGGING', true),
    'activity_logging' => env('LOGGING_ACTIVITY_LOGGING', true),
    'log_analysis' => env('LOGGING_ANALYSIS', true),
    'log_level' => env('LOGGING_LEVEL', 'info'),
    'log_retention' => env('LOGGING_RETENTION', 30),
],
```

#### Variables de Entorno
```env
# Logging Middleware
LOGGING_MIDDLEWARE_ENABLED=true
LOGGING_REQUEST_LOGGING=true
LOGGING_RESPONSE_LOGGING=true
LOGGING_ERROR_LOGGING=true
LOGGING_ACTIVITY_LOGGING=true
LOGGING_ANALYSIS=true
LOGGING_LEVEL=info
LOGGING_RETENTION=30
```

## ⚡ Configuración de Jobs

### SystemIntegrationJob

#### Configuración
```php
// config/jobs.php
'system_integration' => [
    'enabled' => env('SYSTEM_INTEGRATION_JOB_ENABLED', true),
    'timeout' => env('SYSTEM_INTEGRATION_JOB_TIMEOUT', 300),
    'retry_attempts' => env('SYSTEM_INTEGRATION_JOB_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('SYSTEM_INTEGRATION_JOB_RETRY_DELAY', 60),
    'queue' => env('SYSTEM_INTEGRATION_JOB_QUEUE', 'default'),
    'priority' => env('SYSTEM_INTEGRATION_JOB_PRIORITY', 1),
],
```

#### Variables de Entorno
```env
# System Integration Job
SYSTEM_INTEGRATION_JOB_ENABLED=true
SYSTEM_INTEGRATION_JOB_TIMEOUT=300
SYSTEM_INTEGRATION_JOB_RETRY_ATTEMPTS=3
SYSTEM_INTEGRATION_JOB_RETRY_DELAY=60
SYSTEM_INTEGRATION_JOB_QUEUE=default
SYSTEM_INTEGRATION_JOB_PRIORITY=1
```

### LoggingJob

#### Configuración
```php
// config/jobs.php
'logging' => [
    'enabled' => env('LOGGING_JOB_ENABLED', true),
    'timeout' => env('LOGGING_JOB_TIMEOUT', 300),
    'retry_attempts' => env('LOGGING_JOB_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('LOGGING_JOB_RETRY_DELAY', 60),
    'queue' => env('LOGGING_JOB_QUEUE', 'default'),
    'priority' => env('LOGGING_JOB_PRIORITY', 1),
],
```

#### Variables de Entorno
```env
# Logging Job
LOGGING_JOB_ENABLED=true
LOGGING_JOB_TIMEOUT=300
LOGGING_JOB_RETRY_ATTEMPTS=3
LOGGING_JOB_RETRY_DELAY=60
LOGGING_JOB_QUEUE=default
LOGGING_JOB_PRIORITY=1
```

### BackupJob

#### Configuración
```php
// config/jobs.php
'backup' => [
    'enabled' => env('BACKUP_JOB_ENABLED', true),
    'timeout' => env('BACKUP_JOB_TIMEOUT', 3600),
    'retry_attempts' => env('BACKUP_JOB_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('BACKUP_JOB_RETRY_DELAY', 300),
    'queue' => env('BACKUP_JOB_QUEUE', 'default'),
    'priority' => env('BACKUP_JOB_PRIORITY', 1),
    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
],
```

#### Variables de Entorno
```env
# Backup Job
BACKUP_JOB_ENABLED=true
BACKUP_JOB_TIMEOUT=3600
BACKUP_JOB_RETRY_ATTEMPTS=3
BACKUP_JOB_RETRY_DELAY=300
BACKUP_JOB_QUEUE=default
BACKUP_JOB_PRIORITY=1
BACKUP_RETENTION_DAYS=30
```

### NotificationJob

#### Configuración
```php
// config/jobs.php
'notification' => [
    'enabled' => env('NOTIFICATION_JOB_ENABLED', true),
    'timeout' => env('NOTIFICATION_JOB_TIMEOUT', 300),
    'retry_attempts' => env('NOTIFICATION_JOB_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('NOTIFICATION_JOB_RETRY_DELAY', 60),
    'queue' => env('NOTIFICATION_JOB_QUEUE', 'default'),
    'priority' => env('NOTIFICATION_JOB_PRIORITY', 1),
],
```

#### Variables de Entorno
```env
# Notification Job
NOTIFICATION_JOB_ENABLED=true
NOTIFICATION_JOB_TIMEOUT=300
NOTIFICATION_JOB_RETRY_ATTEMPTS=3
NOTIFICATION_JOB_RETRY_DELAY=60
NOTIFICATION_JOB_QUEUE=default
NOTIFICATION_JOB_PRIORITY=1
```

### CleanupJob

#### Configuración
```php
// config/jobs.php
'cleanup' => [
    'enabled' => env('CLEANUP_JOB_ENABLED', true),
    'timeout' => env('CLEANUP_JOB_TIMEOUT', 1800),
    'retry_attempts' => env('CLEANUP_JOB_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('CLEANUP_JOB_RETRY_DELAY', 300),
    'queue' => env('CLEANUP_JOB_QUEUE', 'default'),
    'priority' => env('CLEANUP_JOB_PRIORITY', 1),
],
```

#### Variables de Entorno
```env
# Cleanup Job
CLEANUP_JOB_ENABLED=true
CLEANUP_JOB_TIMEOUT=1800
CLEANUP_JOB_RETRY_ATTEMPTS=3
CLEANUP_JOB_RETRY_DELAY=300
CLEANUP_JOB_QUEUE=default
CLEANUP_JOB_PRIORITY=1
```

## 🎯 Configuración de Comandos Artisan

### System Commands

#### system:status
```php
// config/commands.php
'system_status' => [
    'enabled' => env('SYSTEM_STATUS_COMMAND_ENABLED', true),
    'detailed' => env('SYSTEM_STATUS_DETAILED', false),
    'json' => env('SYSTEM_STATUS_JSON', false),
    'save' => env('SYSTEM_STATUS_SAVE', false),
],
```

#### Variables de Entorno
```env
# System Status Command
SYSTEM_STATUS_COMMAND_ENABLED=true
SYSTEM_STATUS_DETAILED=false
SYSTEM_STATUS_JSON=false
SYSTEM_STATUS_SAVE=false
```

#### system:maintenance
```php
// config/commands.php
'system_maintenance' => [
    'enabled' => env('SYSTEM_MAINTENANCE_COMMAND_ENABLED', true),
    'start' => env('SYSTEM_MAINTENANCE_START', false),
    'stop' => env('SYSTEM_MAINTENANCE_STOP', false),
    'status' => env('SYSTEM_MAINTENANCE_STATUS', false),
],
```

#### Variables de Entorno
```env
# System Maintenance Command
SYSTEM_MAINTENANCE_COMMAND_ENABLED=true
SYSTEM_MAINTENANCE_START=false
SYSTEM_MAINTENANCE_STOP=false
SYSTEM_MAINTENANCE_STATUS=false
```

#### system:monitor
```php
// config/commands.php
'system_monitor' => [
    'enabled' => env('SYSTEM_MONITOR_COMMAND_ENABLED', true),
    'start' => env('SYSTEM_MONITOR_START', false),
    'stop' => env('SYSTEM_MONITOR_STOP', false),
    'status' => env('SYSTEM_MONITOR_STATUS', false),
    'health' => env('SYSTEM_MONITOR_HEALTH', false),
    'alerts' => env('SYSTEM_MONITOR_ALERTS', false),
],
```

#### Variables de Entorno
```env
# System Monitor Command
SYSTEM_MONITOR_COMMAND_ENABLED=true
SYSTEM_MONITOR_START=false
SYSTEM_MONITOR_STOP=false
SYSTEM_MONITOR_STATUS=false
SYSTEM_MONITOR_HEALTH=false
SYSTEM_MONITOR_ALERTS=false
```

### Backup Commands

#### backup:manage
```php
// config/commands.php
'backup_manage' => [
    'enabled' => env('BACKUP_MANAGE_COMMAND_ENABLED', true),
    'list' => env('BACKUP_MANAGE_LIST', false),
    'create' => env('BACKUP_MANAGE_CREATE', false),
    'verify' => env('BACKUP_MANAGE_VERIFY', false),
    'schedule' => env('BACKUP_MANAGE_SCHEDULE', false),
    'retention' => env('BACKUP_MANAGE_RETENTION', 30),
    'type' => env('BACKUP_MANAGE_TYPE', 'database'),
    'compress' => env('BACKUP_MANAGE_COMPRESS', true),
],
```

#### Variables de Entorno
```env
# Backup Manage Command
BACKUP_MANAGE_COMMAND_ENABLED=true
BACKUP_MANAGE_LIST=false
BACKUP_MANAGE_CREATE=false
BACKUP_MANAGE_VERIFY=false
BACKUP_MANAGE_SCHEDULE=false
BACKUP_MANAGE_RETENTION=30
BACKUP_MANAGE_TYPE=database
BACKUP_MANAGE_COMPRESS=true
```

### Notification Commands

#### notification:manage
```php
// config/commands.php
'notification_manage' => [
    'enabled' => env('NOTIFICATION_MANAGE_COMMAND_ENABLED', true),
    'list' => env('NOTIFICATION_MANAGE_LIST', false),
    'send' => env('NOTIFICATION_MANAGE_SEND', false),
    'test' => env('NOTIFICATION_MANAGE_TEST', false),
    'schedule' => env('NOTIFICATION_MANAGE_SCHEDULE', false),
    'channels' => env('NOTIFICATION_MANAGE_CHANNELS', 'email,sms'),
    'template' => env('NOTIFICATION_MANAGE_TEMPLATE', 'default'),
    'priority' => env('NOTIFICATION_MANAGE_PRIORITY', 'normal'),
],
```

#### Variables de Entorno
```env
# Notification Manage Command
NOTIFICATION_MANAGE_COMMAND_ENABLED=true
NOTIFICATION_MANAGE_LIST=false
NOTIFICATION_MANAGE_SEND=false
NOTIFICATION_MANAGE_TEST=false
NOTIFICATION_MANAGE_SCHEDULE=false
NOTIFICATION_MANAGE_CHANNELS=email,sms
NOTIFICATION_MANAGE_TEMPLATE=default
NOTIFICATION_MANAGE_PRIORITY=normal
```

### Cleanup Commands

#### cleanup:manage
```php
// config/commands.php
'cleanup_manage' => [
    'enabled' => env('CLEANUP_MANAGE_COMMAND_ENABLED', true),
    'status' => env('CLEANUP_MANAGE_STATUS', false),
    'run' => env('CLEANUP_MANAGE_RUN', false),
    'schedule' => env('CLEANUP_MANAGE_SCHEDULE', false),
    'type' => env('CLEANUP_MANAGE_TYPE', 'logs'),
    'force' => env('CLEANUP_MANAGE_FORCE', false),
    'dry_run' => env('CLEANUP_MANAGE_DRY_RUN', false),
],
```

#### Variables de Entorno
```env
# Cleanup Manage Command
CLEANUP_MANAGE_COMMAND_ENABLED=true
CLEANUP_MANAGE_STATUS=false
CLEANUP_MANAGE_RUN=false
CLEANUP_MANAGE_SCHEDULE=false
CLEANUP_MANAGE_TYPE=logs
CLEANUP_MANAGE_FORCE=false
CLEANUP_MANAGE_DRY_RUN=false
```

### Job Commands

#### jobs:manage
```php
// config/commands.php
'jobs_manage' => [
    'enabled' => env('JOBS_MANAGE_COMMAND_ENABLED', true),
    'status' => env('JOBS_MANAGE_STATUS', false),
    'dispatch' => env('JOBS_MANAGE_DISPATCH', false),
    'clear' => env('JOBS_MANAGE_CLEAR', false),
    'retry' => env('JOBS_MANAGE_RETRY', false),
    'queue' => env('JOBS_MANAGE_QUEUE', 'default'),
    'priority' => env('JOBS_MANAGE_PRIORITY', 1),
    'timeout' => env('JOBS_MANAGE_TIMEOUT', 300),
],
```

#### Variables de Entorno
```env
# Jobs Manage Command
JOBS_MANAGE_COMMAND_ENABLED=true
JOBS_MANAGE_STATUS=false
JOBS_MANAGE_DISPATCH=false
JOBS_MANAGE_CLEAR=false
JOBS_MANAGE_RETRY=false
JOBS_MANAGE_QUEUE=default
JOBS_MANAGE_PRIORITY=1
JOBS_MANAGE_TIMEOUT=300
```

### Worker Commands

#### workers:start
```php
// config/commands.php
'workers_start' => [
    'enabled' => env('WORKERS_START_COMMAND_ENABLED', true),
    'workers' => env('WORKERS_START_WORKERS', 1),
    'timeout' => env('WORKERS_START_TIMEOUT', 30),
    'queue' => env('WORKERS_START_QUEUE', 'default'),
    'memory' => env('WORKERS_START_MEMORY', 128),
    'sleep' => env('WORKERS_START_SLEEP', 3),
    'tries' => env('WORKERS_START_TRIES', 3),
],
```

#### Variables de Entorno
```env
# Workers Start Command
WORKERS_START_COMMAND_ENABLED=true
WORKERS_START_WORKERS=1
WORKERS_START_TIMEOUT=30
WORKERS_START_QUEUE=default
WORKERS_START_MEMORY=128
WORKERS_START_SLEEP=3
WORKERS_START_TRIES=3
```

## 🌐 Configuración de Servicios Externos

### ExternalApiService

#### Configuración
```php
// config/external_services.php
'external_api' => [
    'enabled' => env('EXTERNAL_API_SERVICE_ENABLED', true),
    'base_url' => env('EXTERNAL_API_BASE_URL', 'https://api.example.com'),
    'api_key' => env('EXTERNAL_API_KEY'),
    'api_secret' => env('EXTERNAL_API_SECRET'),
    'timeout' => env('EXTERNAL_API_TIMEOUT', 30),
    'retry_attempts' => env('EXTERNAL_API_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('EXTERNAL_API_RETRY_DELAY', 1000),
    'rate_limit' => env('EXTERNAL_API_RATE_LIMIT', 100),
    'rate_limit_window' => env('EXTERNAL_API_RATE_LIMIT_WINDOW', 60),
],
```

### ExternalEmailService

#### Configuración
```php
// config/external_services.php
'external_email' => [
    'enabled' => env('EXTERNAL_EMAIL_SERVICE_ENABLED', true),
    'provider' => env('MAIL_EXTERNAL_PROVIDER', 'smtp'),
    'api_key' => env('MAIL_EXTERNAL_API_KEY'),
    'api_secret' => env('MAIL_EXTERNAL_API_SECRET'),
    'base_url' => env('MAIL_EXTERNAL_BASE_URL', 'https://api.email-provider.com'),
    'timeout' => env('MAIL_EXTERNAL_TIMEOUT', 30),
    'retry_attempts' => env('MAIL_EXTERNAL_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('MAIL_EXTERNAL_RETRY_DELAY', 1000),
    'rate_limit' => env('MAIL_EXTERNAL_RATE_LIMIT', 100),
    'rate_limit_window' => env('MAIL_EXTERNAL_RATE_LIMIT_WINDOW', 60),
],
```

### ExternalSmsService

#### Configuración
```php
// config/external_services.php
'external_sms' => [
    'enabled' => env('EXTERNAL_SMS_SERVICE_ENABLED', true),
    'provider' => env('SMS_PROVIDER', 'twilio'),
    'api_key' => env('SMS_API_KEY'),
    'api_secret' => env('SMS_API_SECRET'),
    'from_number' => env('SMS_FROM_NUMBER'),
    'base_url' => env('SMS_BASE_URL', 'https://api.twilio.com'),
    'timeout' => env('SMS_TIMEOUT', 30),
    'retry_attempts' => env('SMS_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('SMS_RETRY_DELAY', 1000),
    'rate_limit' => env('SMS_RATE_LIMIT', 100),
    'rate_limit_window' => env('SMS_RATE_LIMIT_WINDOW', 60),
],
```

### ExternalPushService

#### Configuración
```php
// config/external_services.php
'external_push' => [
    'enabled' => env('EXTERNAL_PUSH_SERVICE_ENABLED', true),
    'provider' => env('PUSH_PROVIDER', 'fcm'),
    'api_key' => env('PUSH_API_KEY'),
    'api_secret' => env('PUSH_API_SECRET'),
    'base_url' => env('PUSH_BASE_URL', 'https://fcm.googleapis.com'),
    'timeout' => env('PUSH_TIMEOUT', 30),
    'retry_attempts' => env('PUSH_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('PUSH_RETRY_DELAY', 1000),
    'rate_limit' => env('PUSH_RATE_LIMIT', 100),
    'rate_limit_window' => env('PUSH_RATE_LIMIT_WINDOW', 60),
],
```

### ExternalStorageService

#### Configuración
```php
// config/external_services.php
'external_storage' => [
    'enabled' => env('EXTERNAL_STORAGE_SERVICE_ENABLED', true),
    'provider' => env('STORAGE_EXTERNAL_PROVIDER', 'aws_s3'),
    'api_key' => env('STORAGE_API_KEY'),
    'api_secret' => env('STORAGE_API_SECRET'),
    'bucket' => env('STORAGE_BUCKET'),
    'region' => env('STORAGE_REGION', 'us-east-1'),
    'base_url' => env('STORAGE_BASE_URL', 'https://s3.amazonaws.com'),
    'timeout' => env('STORAGE_TIMEOUT', 30),
    'retry_attempts' => env('STORAGE_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('STORAGE_RETRY_DELAY', 1000),
    'rate_limit' => env('STORAGE_RATE_LIMIT', 100),
    'rate_limit_window' => env('STORAGE_RATE_LIMIT_WINDOW', 60),
],
```

### ExternalMonitoringService

#### Configuración
```php
// config/external_services.php
'external_monitoring' => [
    'enabled' => env('EXTERNAL_MONITORING_SERVICE_ENABLED', true),
    'provider' => env('MONITORING_PROVIDER', 'datadog'),
    'api_key' => env('MONITORING_API_KEY'),
    'api_secret' => env('MONITORING_API_SECRET'),
    'base_url' => env('MONITORING_BASE_URL', 'https://api.datadoghq.com'),
    'timeout' => env('MONITORING_TIMEOUT', 30),
    'retry_attempts' => env('MONITORING_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('MONITORING_RETRY_DELAY', 1000),
    'rate_limit' => env('MONITORING_RATE_LIMIT', 100),
    'rate_limit_window' => env('MONITORING_RATE_LIMIT_WINDOW', 60),
],
```

## ⚡ Configuración de Optimización

### DatabaseOptimizationService

#### Configuración
```php
// config/optimization.php
'database' => [
    'enabled' => env('DB_OPTIMIZATION_ENABLED', true),
    'interval' => env('DB_OPTIMIZATION_INTERVAL', 3600),
    'threshold' => env('DB_OPTIMIZATION_THRESHOLD', 2.0),
    'indexes' => env('DB_OPTIMIZATION_INDEXES', true),
    'queries' => env('DB_OPTIMIZATION_QUERIES', true),
    'tables' => env('DB_OPTIMIZATION_TABLES', true),
    'cleanup' => env('DB_OPTIMIZATION_CLEANUP', true),
],
```

### CacheOptimizationService

#### Configuración
```php
// config/optimization.php
'cache' => [
    'enabled' => env('CACHE_OPTIMIZATION_ENABLED', true),
    'interval' => env('CACHE_OPTIMIZATION_INTERVAL', 1800),
    'threshold' => env('CACHE_OPTIMIZATION_THRESHOLD', 0.8),
    'redis' => env('CACHE_OPTIMIZATION_REDIS', true),
    'database' => env('CACHE_OPTIMIZATION_DATABASE', true),
    'session' => env('CACHE_OPTIMIZATION_SESSION', true),
    'cleanup' => env('CACHE_OPTIMIZATION_CLEANUP', true),
],
```

### QueryOptimizationService

#### Configuración
```php
// config/optimization.php
'queries' => [
    'enabled' => env('QUERY_OPTIMIZATION_ENABLED', true),
    'interval' => env('QUERY_OPTIMIZATION_INTERVAL', 3600),
    'threshold' => env('QUERY_OPTIMIZATION_THRESHOLD', 2.0),
    'slow_queries' => env('QUERY_OPTIMIZATION_SLOW_QUERIES', true),
    'n_plus_one' => env('QUERY_OPTIMIZATION_N_PLUS_ONE', true),
    'joins' => env('QUERY_OPTIMIZATION_JOINS', true),
    'subqueries' => env('QUERY_OPTIMIZATION_SUBQUERIES', true),
],
```

### MemoryOptimizationService

#### Configuración
```php
// config/optimization.php
'memory' => [
    'enabled' => env('MEMORY_OPTIMIZATION_ENABLED', true),
    'interval' => env('MEMORY_OPTIMIZATION_INTERVAL', 1800),
    'threshold' => env('MEMORY_OPTIMIZATION_THRESHOLD', 0.8),
    'php' => env('MEMORY_OPTIMIZATION_PHP', true),
    'redis' => env('MEMORY_OPTIMIZATION_REDIS', true),
    'cleanup' => env('MEMORY_OPTIMIZATION_CLEANUP', true),
],
```

### FileOptimizationService

#### Configuración
```php
// config/optimization.php
'files' => [
    'enabled' => env('FILE_OPTIMIZATION_ENABLED', true),
    'interval' => env('FILE_OPTIMIZATION_INTERVAL', 3600),
    'threshold' => env('FILE_OPTIMIZATION_THRESHOLD', 10485760),
    'logs' => env('FILE_OPTIMIZATION_LOGS', true),
    'cache' => env('FILE_OPTIMIZATION_CACHE', true),
    'sessions' => env('FILE_OPTIMIZATION_SESSIONS', true),
    'cleanup' => env('FILE_OPTIMIZATION_CLEANUP', true),
],
```

### JobOptimizationService

#### Configuración
```php
// config/optimization.php
'jobs' => [
    'enabled' => env('JOB_OPTIMIZATION_ENABLED', true),
    'interval' => env('JOB_OPTIMIZATION_INTERVAL', 1800),
    'threshold' => env('JOB_OPTIMIZATION_THRESHOLD', 100),
    'queues' => env('JOB_OPTIMIZATION_QUEUES', true),
    'workers' => env('JOB_OPTIMIZATION_WORKERS', true),
    'retry' => env('JOB_OPTIMIZATION_RETRY', true),
    'cleanup' => env('JOB_OPTIMIZATION_CLEANUP', true),
],
```

### ExternalServiceOptimizationService

#### Configuración
```php
// config/optimization.php
'external_services' => [
    'enabled' => env('EXTERNAL_SERVICE_OPTIMIZATION_ENABLED', true),
    'interval' => env('EXTERNAL_SERVICE_OPTIMIZATION_INTERVAL', 3600),
    'threshold' => env('EXTERNAL_SERVICE_OPTIMIZATION_THRESHOLD', 30),
    'apis' => env('EXTERNAL_SERVICE_OPTIMIZATION_APIS', true),
    'email' => env('EXTERNAL_SERVICE_OPTIMIZATION_EMAIL', true),
    'sms' => env('EXTERNAL_SERVICE_OPTIMIZATION_SMS', true),
    'push' => env('EXTERNAL_SERVICE_OPTIMIZATION_PUSH', true),
    'storage' => env('EXTERNAL_SERVICE_OPTIMIZATION_STORAGE', true),
    'monitoring' => env('EXTERNAL_SERVICE_OPTIMIZATION_MONITORING', true),
],
```

## 🔧 Configuración de Archivos

### Archivos de Configuración

#### config/app.php
```php
return [
    'name' => env('APP_NAME', 'ModuStackElyMarLuxury'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://your-domain.com'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
];
```

#### config/database.php
```php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'modustack_elymar_luxury'),
            'username' => env('DB_USERNAME', 'modustack'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],
];
```

#### config/cache.php
```php
return [
    'default' => env('CACHE_DRIVER', 'redis'),
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => env('CACHE_PREFIX', 'modustack'),
        ],
    ],
];
```

#### config/queue.php
```php
return [
    'default' => env('QUEUE_CONNECTION', 'redis'),
    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => env('QUEUE_RETRY_AFTER', 90),
            'block_for' => null,
        ],
    ],
];
```

## 🎯 Conclusión

La configuración de ModuStackElyMarLuxury está **completamente documentada** y proporciona:

- **Variables de entorno** completas
- **Configuración de middleware** detallada
- **Configuración de jobs** exhaustiva
- **Configuración de comandos** artisan
- **Configuración de servicios externos** completa
- **Configuración de optimización** integral

El sistema está **completamente configurado** y listo para uso en producción.

---

**ModuStackElyMarLuxury** - Sistema completo de gestión empresarial

