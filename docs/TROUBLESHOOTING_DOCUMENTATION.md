# 🔧 Documentación de Troubleshooting - ModuStackElyMarLuxury

## 📋 Descripción General

Esta documentación cubre la resolución de problemas comunes en ModuStackElyMarLuxury, incluyendo problemas de instalación, configuración, deployment, rendimiento y mantenimiento.

## 🚨 Problemas Comunes

### Problemas de Instalación

#### Error: "Class 'Redis' not found"
**Síntomas**: Error al ejecutar tests o usar cache Redis
**Causa**: Extensión Redis de PHP no instalada
**Solución**:
```bash
# Ubuntu/Debian
sudo apt install -y php8.1-redis
sudo systemctl restart php8.1-fpm

# CentOS/RHEL
sudo yum install -y php-redis
sudo systemctl restart php-fpm

# Verificar instalación
php -m | grep redis
```

#### Error: "Composer not found"
**Síntomas**: Error al ejecutar `composer install`
**Causa**: Composer no instalado o no en PATH
**Solución**:
```bash
# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verificar instalación
composer --version
```

#### Error: "Node.js not found"
**Síntomas**: Error al ejecutar `npm install`
**Causa**: Node.js no instalado
**Solución**:
```bash
# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verificar instalación
node --version
npm --version
```

#### Error: "MySQL connection failed"
**Síntomas**: Error de conexión a base de datos
**Causa**: MySQL no configurado correctamente
**Solución**:
```bash
# Verificar estado de MySQL
sudo systemctl status mysql

# Iniciar MySQL si está detenido
sudo systemctl start mysql
sudo systemctl enable mysql

# Verificar conexión
mysql -u root -p -e "SHOW DATABASES;"

# Crear base de datos
mysql -u root -p -e "CREATE DATABASE modustack_elymar_luxury;"
mysql -u root -p -e "CREATE USER 'modustack'@'localhost' IDENTIFIED BY 'secure_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON modustack_elymar_luxury.* TO 'modustack'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

### Problemas de Configuración

#### Error: "APP_KEY not set"
**Síntomas**: Error al acceder a la aplicación
**Causa**: Clave de aplicación no generada
**Solución**:
```bash
# Generar clave de aplicación
php artisan key:generate

# Verificar clave
grep APP_KEY .env
```

#### Error: "Database connection failed"
**Síntomas**: Error de conexión a base de datos
**Causa**: Configuración incorrecta de base de datos
**Solución**:
```bash
# Verificar configuración
cat .env | grep DB_

# Probar conexión
php artisan tinker
>>> DB::connection()->getPdo();

# Verificar credenciales
mysql -u modustack -p modustack_elymar_luxury
```

#### Error: "Cache connection failed"
**Síntomas**: Error de conexión a cache
**Causa**: Redis no configurado correctamente
**Solución**:
```bash
# Verificar estado de Redis
sudo systemctl status redis

# Iniciar Redis si está detenido
sudo systemctl start redis
sudo systemctl enable redis

# Probar conexión
redis-cli ping

# Verificar configuración
cat .env | grep REDIS_
```

#### Error: "Queue connection failed"
**Síntomas**: Error de conexión a queue
**Causa**: Redis no configurado para queue
**Solución**:
```bash
# Verificar configuración de queue
cat .env | grep QUEUE_

# Probar queue
php artisan queue:work --once

# Verificar workers
php artisan workers:status
```

### Problemas de Deployment

#### Error: "Permission denied"
**Síntomas**: Error de permisos al acceder a archivos
**Causa**: Permisos incorrectos en directorios
**Solución**:
```bash
# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Verificar permisos
ls -la storage/
ls -la bootstrap/cache/
```

#### Error: "Nginx configuration test failed"
**Síntomas**: Error al reiniciar Nginx
**Causa**: Configuración incorrecta de Nginx
**Solución**:
```bash
# Verificar configuración
sudo nginx -t

# Verificar archivos de configuración
sudo nano /etc/nginx/sites-available/modustack-elymar-luxury

# Reiniciar Nginx
sudo systemctl restart nginx
```

#### Error: "Supervisor configuration failed"
**Síntomas**: Error al configurar Supervisor
**Causa**: Configuración incorrecta de Supervisor
**Solución**:
```bash
# Verificar configuración
sudo supervisorctl reread
sudo supervisorctl update

# Verificar estado
sudo supervisorctl status

# Reiniciar workers
sudo supervisorctl restart modustack-workers:*
```

### Problemas de Rendimiento

#### Problema: "Slow response time"
**Síntomas**: Tiempo de respuesta lento
**Causa**: Múltiples factores posibles
**Solución**:
```bash
# Verificar uso de recursos
htop
iotop
nethogs

# Optimizar aplicación
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar cache
php artisan cache:clear
php artisan cache:forget all

# Verificar base de datos
php artisan optimize:database
php artisan optimize:queries
```

#### Problema: "High memory usage"
**Síntomas**: Uso alto de memoria
**Causa**: Múltiples factores posibles
**Solución**:
```bash
# Verificar uso de memoria
free -h
ps aux --sort=-%mem | head

# Optimizar memoria
php artisan optimize:memory

# Verificar PHP memory limit
php -i | grep memory_limit

# Ajustar memory limit si es necesario
sudo nano /etc/php/8.1/fpm/php.ini
# memory_limit = 256M
sudo systemctl restart php8.1-fpm
```

#### Problema: "High CPU usage"
**Síntomas**: Uso alto de CPU
**Causa**: Múltiples factores posibles
**Solución**:
```bash
# Verificar uso de CPU
top
htop

# Verificar procesos
ps aux --sort=-%cpu | head

# Optimizar aplicación
php artisan optimize:all

# Verificar workers
php artisan workers:status
sudo supervisorctl status
```

### Problemas de Jobs

#### Problema: "Jobs not processing"
**Síntomas**: Jobs no se procesan
**Causa**: Workers no funcionando
**Solución**:
```bash
# Verificar workers
php artisan workers:status
sudo supervisorctl status

# Reiniciar workers
sudo supervisorctl restart modustack-workers:*

# Verificar queue
php artisan queue:work --once

# Verificar jobs fallidos
php artisan jobs:manage status
```

#### Problema: "Jobs failing"
**Síntomas**: Jobs fallan constantemente
**Causa**: Múltiples factores posibles
**Solución**:
```bash
# Verificar jobs fallidos
php artisan jobs:manage status

# Reintentar jobs fallidos
php artisan jobs:manage retry

# Verificar logs
tail -f storage/logs/laravel.log

# Verificar configuración
php artisan config:show queue
```

### Problemas de Servicios Externos

#### Problema: "External API timeout"
**Síntomas**: Timeout en APIs externas
**Causa**: Configuración incorrecta o problemas de red
**Solución**:
```bash
# Verificar configuración
cat .env | grep EXTERNAL_API

# Probar conexión
curl -I https://api.example.com

# Verificar timeout
php artisan external-services:health

# Ajustar timeout si es necesario
# EXTERNAL_API_TIMEOUT=60
```

#### Problema: "Email not sending"
**Síntomas**: Emails no se envían
**Causa**: Configuración incorrecta de email
**Solución**:
```bash
# Verificar configuración
cat .env | grep MAIL_

# Probar envío
php artisan tinker
>>> Mail::raw('Test email', function($message) { $message->to('test@example.com')->subject('Test'); });

# Verificar logs
tail -f storage/logs/laravel.log
```

#### Problema: "SMS not sending"
**Síntomas**: SMS no se envían
**Causa**: Configuración incorrecta de SMS
**Solución**:
```bash
# Verificar configuración
cat .env | grep SMS_

# Probar envío
php artisan tinker
>>> app(\App\Services\ExternalSmsService::class)->sendSms('+1234567890', 'Test SMS');

# Verificar logs
tail -f storage/logs/laravel.log
```

### Problemas de Optimización

#### Problema: "Optimization not working"
**Síntomas**: Optimización no funciona
**Causa**: Configuración incorrecta
**Solución**:
```bash
# Verificar configuración
cat .env | grep OPTIMIZATION

# Ejecutar optimización manual
php artisan optimize:all

# Verificar logs
tail -f storage/logs/laravel.log
```

#### Problema: "Cache not working"
**Síntomas**: Cache no funciona
**Causa**: Configuración incorrecta de cache
**Solución**:
```bash
# Verificar configuración
cat .env | grep CACHE_

# Limpiar cache
php artisan cache:clear

# Verificar Redis
redis-cli ping

# Probar cache
php artisan tinker
>>> Cache::put('test', 'value', 60);
>>> Cache::get('test');
```

## 🔍 Diagnóstico de Problemas

### Herramientas de Diagnóstico

#### Verificar Estado del Sistema
```bash
# Estado general
php artisan system:status

# Estado de monitoreo
php artisan system:monitor status

# Estado de workers
php artisan workers:status

# Estado de jobs
php artisan jobs:manage status

# Estado de servicios externos
php artisan external-services:health
```

#### Verificar Logs
```bash
# Logs de aplicación
tail -f storage/logs/laravel.log

# Logs de Nginx
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Logs de PHP
tail -f /var/log/php8.1-fpm.log

# Logs de MySQL
tail -f /var/log/mysql/error.log

# Logs de Redis
tail -f /var/log/redis/redis-server.log
```

#### Verificar Recursos
```bash
# Uso de CPU
top
htop

# Uso de memoria
free -h
ps aux --sort=-%mem | head

# Uso de disco
df -h
du -sh /var/www/modustack-elymar-luxury

# Uso de red
nethogs
iftop
```

### Comandos de Diagnóstico

#### Diagnóstico Completo
```bash
#!/bin/bash
# diagnose.sh

echo "=== DIAGNÓSTICO COMPLETO ==="

echo "1. Estado del sistema:"
php artisan system:status

echo "2. Estado de monitoreo:"
php artisan system:monitor status

echo "3. Estado de workers:"
php artisan workers:status

echo "4. Estado de jobs:"
php artisan jobs:manage status

echo "5. Estado de servicios externos:"
php artisan external-services:health

echo "6. Uso de recursos:"
echo "CPU:"
top -bn1 | grep "Cpu(s)"
echo "Memoria:"
free -h
echo "Disco:"
df -h

echo "7. Logs recientes:"
tail -n 20 storage/logs/laravel.log

echo "8. Configuración:"
echo "APP_ENV: $(grep APP_ENV .env)"
echo "APP_DEBUG: $(grep APP_DEBUG .env)"
echo "DB_CONNECTION: $(grep DB_CONNECTION .env)"
echo "CACHE_DRIVER: $(grep CACHE_DRIVER .env)"
echo "QUEUE_CONNECTION: $(grep QUEUE_CONNECTION .env)"

echo "=== DIAGNÓSTICO COMPLETADO ==="
```

#### Diagnóstico de Rendimiento
```bash
#!/bin/bash
# performance-diagnose.sh

echo "=== DIAGNÓSTICO DE RENDIMIENTO ==="

echo "1. Tiempo de respuesta:"
curl -w "@curl-format.txt" -o /dev/null -s https://your-domain.com

echo "2. Uso de memoria PHP:"
php -r "echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;"
php -r "echo 'Memory usage: ' . memory_get_usage(true) . ' bytes' . PHP_EOL;"

echo "3. Cache hit rate:"
php artisan tinker <<< "echo 'Cache hit rate: ' . Cache::get('cache_hit_rate', 'N/A') . PHP_EOL;"

echo "4. Base de datos:"
php artisan tinker <<< "echo 'DB connections: ' . DB::connection()->getPdo() ? 'OK' : 'FAIL' . PHP_EOL;"

echo "5. Redis:"
redis-cli ping

echo "6. Workers:"
php artisan workers:status

echo "7. Jobs:"
php artisan jobs:manage status

echo "=== DIAGNÓSTICO DE RENDIMIENTO COMPLETADO ==="
```

### Scripts de Reparación

#### Reparación Automática
```bash
#!/bin/bash
# repair.sh

echo "=== REPARACIÓN AUTOMÁTICA ==="

echo "1. Limpiar cache:"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "2. Optimizar aplicación:"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "3. Reiniciar servicios:"
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart redis
sudo systemctl restart mysql

echo "4. Reiniciar workers:"
sudo supervisorctl restart modustack-workers:*

echo "5. Verificar estado:"
php artisan system:status

echo "=== REPARACIÓN COMPLETADA ==="
```

#### Reparación de Base de Datos
```bash
#!/bin/bash
# repair-database.sh

echo "=== REPARACIÓN DE BASE DE DATOS ==="

echo "1. Verificar conexión:"
php artisan tinker <<< "echo DB::connection()->getPdo() ? 'OK' : 'FAIL';"

echo "2. Ejecutar migraciones:"
php artisan migrate --force

echo "3. Optimizar base de datos:"
php artisan optimize:database

echo "4. Verificar estado:"
php artisan migrate:status

echo "=== REPARACIÓN DE BASE DE DATOS COMPLETADA ==="
```

#### Reparación de Cache
```bash
#!/bin/bash
# repair-cache.sh

echo "=== REPARACIÓN DE CACHE ==="

echo "1. Verificar Redis:"
redis-cli ping

echo "2. Limpiar cache:"
php artisan cache:clear

echo "3. Optimizar cache:"
php artisan optimize:cache

echo "4. Verificar cache:"
php artisan tinker <<< "Cache::put('test', 'value', 60); echo Cache::get('test');"

echo "=== REPARACIÓN DE CACHE COMPLETADA ==="
```

## 📊 Monitoreo de Problemas

### Alertas Automáticas

#### Configurar Alertas
```bash
# Crear script de alertas
cat > /usr/local/bin/modustack-alerts.sh << 'EOF'
#!/bin/bash

# Verificar estado del sistema
if ! php artisan system:status > /dev/null 2>&1; then
    echo "ALERT: Sistema no responde"
    # Enviar notificación
fi

# Verificar uso de memoria
MEMORY_USAGE=$(free | grep Mem | awk '{printf "%.2f", $3/$2 * 100.0}')
if (( $(echo "$MEMORY_USAGE > 80" | bc -l) )); then
    echo "ALERT: Uso de memoria alto: ${MEMORY_USAGE}%"
    # Enviar notificación
fi

# Verificar uso de disco
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "ALERT: Uso de disco alto: ${DISK_USAGE}%"
    # Enviar notificación
fi

# Verificar workers
if ! php artisan workers:status > /dev/null 2>&1; then
    echo "ALERT: Workers no funcionando"
    # Enviar notificación
fi
EOF

chmod +x /usr/local/bin/modustack-alerts.sh

# Configurar cron para alertas
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/local/bin/modustack-alerts.sh") | crontab -
```

### Métricas de Problemas

#### Tiempo de Resolución
```bash
# Medir tiempo de resolución
start_time=$(date +%s)
# ... pasos de resolución ...
end_time=$(date +%s)
resolution_time=$((end_time - start_time))
echo "Tiempo de resolución: ${resolution_time}s"
```

#### Tasa de Problemas
```bash
# Contar problemas por día
grep "ERROR" storage/logs/laravel.log | grep $(date +%Y-%m-%d) | wc -l

# Contar problemas por tipo
grep "Database connection failed" storage/logs/laravel.log | wc -l
grep "Cache connection failed" storage/logs/laravel.log | wc -l
grep "Queue connection failed" storage/logs/laravel.log | wc -l
```

## 🎯 Conclusión

El troubleshooting de ModuStackElyMarLuxury está **completamente documentado** y proporciona:

- **Resolución de problemas** comunes
- **Herramientas de diagnóstico** completas
- **Scripts de reparación** automática
- **Monitoreo de problemas** en tiempo real
- **Métricas de resolución** detalladas

El sistema está **completamente mantenido** y listo para uso en producción.

---

**ModuStackElyMarLuxury** - Sistema completo de gestión empresarial



