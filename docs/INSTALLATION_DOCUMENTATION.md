# 🚀 Documentación de Instalación - ModuStackElyMarLuxury

## 📋 Descripción General

Esta documentación cubre la instalación completa de ModuStackElyMarLuxury, incluyendo requisitos del sistema, instalación paso a paso, configuración y verificación.

## 🎯 Requisitos del Sistema

### Requisitos Mínimos

#### Servidor
- **OS**: Ubuntu 20.04+ / CentOS 8+ / RHEL 8+ / Windows 10+
- **RAM**: 4GB mínimo, 8GB recomendado
- **CPU**: 2 cores mínimo, 4 cores recomendado
- **Disk**: 50GB mínimo, 100GB recomendado
- **Network**: Conexión a internet estable

#### Software Base
- **PHP**: >= 8.1
- **Composer**: >= 2.0
- **Node.js**: >= 16.0
- **NPM**: >= 8.0
- **Git**: >= 2.0

#### Base de Datos
- **MySQL**: >= 8.0
- **MariaDB**: >= 10.3
- **PostgreSQL**: >= 13.0 (opcional)

#### Servicios Adicionales
- **Redis**: >= 6.0 (recomendado)
- **Nginx**: >= 1.18 / Apache: >= 2.4
- **SSL**: Certificado SSL válido

### Requisitos Recomendados

#### Servidor de Producción
- **OS**: Ubuntu 22.04 LTS
- **RAM**: 16GB
- **CPU**: 8 cores
- **Disk**: 200GB SSD
- **Network**: 1Gbps

#### Software
- **PHP**: 8.2
- **Composer**: 2.5
- **Node.js**: 18.0
- **MySQL**: 8.0
- **Redis**: 7.0
- **Nginx**: 1.22

## 🔧 Instalación Paso a Paso

### 1. Preparar el Sistema

#### Ubuntu/Debian
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias básicas
sudo apt install -y curl wget git unzip software-properties-common

# Instalar PHP 8.1
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-bcmath php8.1-intl php8.1-soap php8.1-xmlrpc

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Instalar MySQL
sudo apt install -y mysql-server mysql-client

# Instalar Redis
sudo apt install -y redis-server

# Instalar Nginx
sudo apt install -y nginx
```

#### CentOS/RHEL
```bash
# Actualizar sistema
sudo yum update -y

# Instalar EPEL
sudo yum install -y epel-release

# Instalar dependencias básicas
sudo yum install -y curl wget git unzip

# Instalar PHP 8.1
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
sudo yum module enable php:remi-8.1 -y
sudo yum install -y php php-fpm php-cli php-mysql php-redis php-curl php-gd php-mbstring php-xml php-zip php-bcmath php-intl

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs

# Instalar MySQL
sudo yum install -y mysql-server mysql

# Instalar Redis
sudo yum install -y redis

# Instalar Nginx
sudo yum install -y nginx
```

#### Windows
```bash
# Instalar Chocolatey
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Instalar dependencias
choco install php composer nodejs mysql redis nginx -y
```

### 2. Configurar Base de Datos

#### MySQL
```bash
# Iniciar MySQL
sudo systemctl start mysql
sudo systemctl enable mysql

# Configurar MySQL
sudo mysql_secure_installation

# Crear base de datos
mysql -u root -p
CREATE DATABASE modustack_elymar_luxury;
CREATE USER 'modustack'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON modustack_elymar_luxury.* TO 'modustack'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### PostgreSQL (Opcional)
```bash
# Instalar PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Crear base de datos
sudo -u postgres psql
CREATE DATABASE modustack_elymar_luxury;
CREATE USER modustack WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE modustack_elymar_luxury TO modustack;
\q
```

### 3. Configurar Redis

```bash
# Iniciar Redis
sudo systemctl start redis
sudo systemctl enable redis

# Verificar Redis
redis-cli ping
# Debe responder: PONG

# Configurar Redis (opcional)
sudo nano /etc/redis/redis.conf
# Cambiar: requirepass your_redis_password
# Cambiar: maxmemory 256mb
# Cambiar: maxmemory-policy allkeys-lru

# Reiniciar Redis
sudo systemctl restart redis
```

### 4. Clonar el Repositorio

```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/ModuStackElyMarLuxury.git
cd ModuStackElyMarLuxury

# Verificar rama
git branch -a
git checkout main
```

### 5. Instalar Dependencias

#### Dependencias PHP
```bash
# Instalar dependencias de Composer
composer install --no-dev --optimize-autoloader

# Verificar instalación
composer show
```

#### Dependencias Node.js
```bash
# Instalar dependencias de NPM
npm install

# Compilar assets
npm run build

# Verificar instalación
npm list
```

### 6. Configurar Entorno

#### Archivo de Configuración
```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

#### Variables de Entorno
```env
# .env
APP_NAME="ModuStackElyMarLuxury"
APP_ENV=production
APP_KEY=base64:generated_key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modustack_elymar_luxury
DB_USERNAME=modustack
DB_PASSWORD=secure_password

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# Servicios externos
EXTERNAL_API_BASE_URL=https://api.example.com
EXTERNAL_API_KEY=your_api_key
EXTERNAL_API_TIMEOUT=30
EXTERNAL_API_RETRY_ATTEMPTS=3

MAIL_EXTERNAL_PROVIDER=smtp
MAIL_EXTERNAL_API_KEY=your_email_api_key
MAIL_TIMEOUT=30

SMS_PROVIDER=twilio
SMS_API_KEY=your_sms_api_key
SMS_API_SECRET=your_sms_api_secret
SMS_FROM_NUMBER=+1234567890
SMS_TIMEOUT=30

PUSH_PROVIDER=fcm
PUSH_API_KEY=your_push_api_key
PUSH_API_SECRET=your_push_api_secret
PUSH_TIMEOUT=30

STORAGE_EXTERNAL_PROVIDER=aws_s3
STORAGE_API_KEY=your_storage_api_key
STORAGE_API_SECRET=your_storage_api_secret
STORAGE_BUCKET=your_bucket
STORAGE_REGION=us-east-1
STORAGE_TIMEOUT=30

MONITORING_PROVIDER=datadog
MONITORING_API_KEY=your_monitoring_api_key
MONITORING_API_SECRET=your_monitoring_api_secret
MONITORING_TIMEOUT=30
```

### 7. Ejecutar Migraciones

```bash
# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeders
php artisan db:seed --force

# Verificar migraciones
php artisan migrate:status
```

### 8. Configurar Permisos

```bash
# Configurar permisos de directorios
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Verificar permisos
ls -la storage/
ls -la bootstrap/cache/
```

### 9. Configurar Nginx

#### Archivo de Configuración
```bash
# Crear configuración de Nginx
sudo nano /etc/nginx/sites-available/modustack-elymar-luxury
```

#### Configuración Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /path/to/ModuStackElyMarLuxury/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to hidden files
    location ~ /\.ht {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

#### Activar Sitio
```bash
# Activar sitio
sudo ln -s /etc/nginx/sites-available/modustack-elymar-luxury /etc/nginx/sites-enabled/

# Verificar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx
```

### 10. Configurar SSL (Opcional)

#### Let's Encrypt
```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Verificar renovación automática
sudo certbot renew --dry-run
```

### 11. Configurar Workers

#### Supervisor
```bash
# Instalar Supervisor
sudo apt install -y supervisor

# Crear configuración de workers
sudo nano /etc/supervisor/conf.d/modustack-workers.conf
```

#### Configuración Supervisor
```ini
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

#### Iniciar Supervisor
```bash
# Recargar configuración
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar workers
sudo supervisorctl start modustack-workers:*

# Verificar estado
sudo supervisorctl status
```

### 12. Configurar Cron

```bash
# Editar crontab
crontab -e

# Agregar tareas cron
* * * * * cd /path/to/ModuStackElyMarLuxury && php artisan schedule:run >> /dev/null 2>&1
```

### 13. Configurar Monitoreo

#### Instalar Herramientas
```bash
# Instalar herramientas de monitoreo
sudo apt install -y htop iotop nethogs

# Instalar herramientas de logs
sudo apt install -y logrotate
```

#### Configurar Logrotate
```bash
# Crear configuración de logrotate
sudo nano /etc/logrotate.d/modustack
```

#### Configuración Logrotate
```
/path/to/ModuStackElyMarLuxury/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 644 www-data www-data
    postrotate
        /bin/kill -USR1 `cat /var/run/nginx.pid 2>/dev/null` 2>/dev/null || true
    endscript
}
```

## 🔍 Verificación de Instalación

### 1. Verificar Servicios

```bash
# Verificar PHP
php --version

# Verificar Composer
composer --version

# Verificar Node.js
node --version
npm --version

# Verificar MySQL
mysql --version

# Verificar Redis
redis-cli --version

# Verificar Nginx
nginx -v
```

### 2. Verificar Aplicación

```bash
# Verificar configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar base de datos
php artisan migrate:status

# Verificar workers
php artisan queue:work --once

# Verificar comandos
php artisan list
```

### 3. Verificar Tests

```bash
# Ejecutar tests
php artisan test

# Verificar cobertura
php artisan test --coverage
```

### 4. Verificar Optimización

```bash
# Ejecutar optimización
php artisan optimize

# Verificar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 🚀 Comandos de Instalación Rápida

### Script de Instalación Automática

```bash
#!/bin/bash
# install.sh

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Instalando ModuStackElyMarLuxury...${NC}"

# Actualizar sistema
echo -e "${YELLOW}📦 Actualizando sistema...${NC}"
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
echo -e "${YELLOW}📦 Instalando dependencias...${NC}"
sudo apt install -y curl wget git unzip software-properties-common

# Instalar PHP 8.1
echo -e "${YELLOW}🐘 Instalando PHP 8.1...${NC}"
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-bcmath php8.1-intl

# Instalar Composer
echo -e "${YELLOW}🎼 Instalando Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js
echo -e "${YELLOW}📦 Instalando Node.js...${NC}"
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Instalar MySQL
echo -e "${YELLOW}🗄️ Instalando MySQL...${NC}"
sudo apt install -y mysql-server mysql-client

# Instalar Redis
echo -e "${YELLOW}🔴 Instalando Redis...${NC}"
sudo apt install -y redis-server

# Instalar Nginx
echo -e "${YELLOW}🌐 Instalando Nginx...${NC}"
sudo apt install -y nginx

# Instalar Supervisor
echo -e "${YELLOW}👷 Instalando Supervisor...${NC}"
sudo apt install -y supervisor

# Clonar repositorio
echo -e "${YELLOW}📥 Clonando repositorio...${NC}"
git clone https://github.com/tu-usuario/ModuStackElyMarLuxury.git
cd ModuStackElyMarLuxury

# Instalar dependencias
echo -e "${YELLOW}📦 Instalando dependencias PHP...${NC}"
composer install --no-dev --optimize-autoloader

echo -e "${YELLOW}📦 Instalando dependencias Node.js...${NC}"
npm install
npm run build

# Configurar entorno
echo -e "${YELLOW}⚙️ Configurando entorno...${NC}"
cp .env.example .env
php artisan key:generate

# Configurar base de datos
echo -e "${YELLOW}🗄️ Configurando base de datos...${NC}"
mysql -u root -p -e "CREATE DATABASE modustack_elymar_luxury;"
mysql -u root -p -e "CREATE USER 'modustack'@'localhost' IDENTIFIED BY 'secure_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON modustack_elymar_luxury.* TO 'modustack'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Ejecutar migraciones
echo -e "${YELLOW}🗄️ Ejecutando migraciones...${NC}"
php artisan migrate --force
php artisan db:seed --force

# Configurar permisos
echo -e "${YELLOW}🔐 Configurando permisos...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Configurar Nginx
echo -e "${YELLOW}🌐 Configurando Nginx...${NC}"
sudo cp nginx.conf /etc/nginx/sites-available/modustack-elymar-luxury
sudo ln -s /etc/nginx/sites-available/modustack-elymar-luxury /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# Configurar Supervisor
echo -e "${YELLOW}👷 Configurando Supervisor...${NC}"
sudo cp supervisor.conf /etc/supervisor/conf.d/modustack-workers.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start modustack-workers:*

# Configurar Cron
echo -e "${YELLOW}⏰ Configurando Cron...${NC}"
(crontab -l 2>/dev/null; echo "* * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Optimizar aplicación
echo -e "${YELLOW}⚡ Optimizando aplicación...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✅ Instalación completada!${NC}"
echo -e "${GREEN}🌐 Accede a: http://your-domain.com${NC}"
```

### Ejecutar Script

```bash
# Hacer ejecutable
chmod +x install.sh

# Ejecutar instalación
./install.sh
```

## 🔧 Configuración Post-Instalación

### 1. Configurar Variables de Entorno

```bash
# Editar archivo .env
nano .env

# Configurar variables específicas
APP_URL=https://your-domain.com
DB_PASSWORD=your_secure_password
REDIS_PASSWORD=your_redis_password
MAIL_FROM_ADDRESS=your_email@domain.com
```

### 2. Configurar Servicios Externos

```bash
# Configurar APIs externas
EXTERNAL_API_BASE_URL=https://api.your-provider.com
EXTERNAL_API_KEY=your_api_key

# Configurar email
MAIL_EXTERNAL_PROVIDER=smtp
MAIL_EXTERNAL_API_KEY=your_email_api_key

# Configurar SMS
SMS_PROVIDER=twilio
SMS_API_KEY=your_sms_api_key
SMS_API_SECRET=your_sms_api_secret

# Configurar push notifications
PUSH_PROVIDER=fcm
PUSH_API_KEY=your_push_api_key

# Configurar almacenamiento
STORAGE_EXTERNAL_PROVIDER=aws_s3
STORAGE_API_KEY=your_storage_api_key
STORAGE_API_SECRET=your_storage_api_secret

# Configurar monitoreo
MONITORING_PROVIDER=datadog
MONITORING_API_KEY=your_monitoring_api_key
```

### 3. Configurar Optimización

```bash
# Ejecutar optimización inicial
php artisan optimize:all

# Configurar optimización automática
php artisan schedule:list
```

### 4. Configurar Monitoreo

```bash
# Instalar herramientas de monitoreo
sudo apt install -y htop iotop nethogs

# Configurar alertas
php artisan system:monitor start
```

## 🧪 Verificación Final

### 1. Tests de Funcionalidad

```bash
# Ejecutar tests completos
php artisan test

# Verificar tests específicos
php artisan test tests/Feature/SystemIntegrationTest.php
php artisan test tests/Feature/JobsIntegrationTest.php
php artisan test tests/Feature/CommandsIntegrationTest.php
php artisan test tests/Feature/ExternalServicesTest.php
php artisan test tests/Feature/OptimizationTest.php
```

### 2. Verificación de Servicios

```bash
# Verificar estado del sistema
php artisan system:status

# Verificar monitoreo
php artisan system:monitor status

# Verificar workers
php artisan workers:status

# Verificar jobs
php artisan jobs:manage status
```

### 3. Verificación de Optimización

```bash
# Verificar optimización de base de datos
php artisan optimize:database

# Verificar optimización de cache
php artisan optimize:cache

# Verificar optimización de consultas
php artisan optimize:queries

# Verificar optimización de memoria
php artisan optimize:memory

# Verificar optimización de archivos
php artisan optimize:files

# Verificar optimización de jobs
php artisan optimize:jobs

# Verificar optimización de servicios externos
php artisan optimize:external-services
```

## 🎯 Conclusión

La instalación de ModuStackElyMarLuxury está **completamente documentada** y proporciona:

- **Instalación paso a paso** detallada
- **Configuración automática** de todos los servicios
- **Verificación completa** de funcionalidad
- **Optimización automática** del sistema
- **Monitoreo integrado** de todos los componentes

El sistema está **completamente instalado** y listo para uso en producción.

---

**ModuStackElyMarLuxury** - Sistema completo de gestión empresarial



