# 🚀 Documentación de Deployment - ModuStackElyMarLuxury

## 📋 Descripción General

Esta documentación cubre el deployment completo de ModuStackElyMarLuxury, incluyendo estrategias de deployment, configuración de servidores, CI/CD, monitoreo y mantenimiento.

## 🎯 Estrategias de Deployment

### Deployment Manual

#### Preparación del Servidor
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependencias
sudo apt install -y curl wget git unzip software-properties-common

# Instalar PHP 8.1
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-cli php8.1-mysql php8.1-redis php8.1-curl php8.1-gd php8.1-mbstring php8.1-xml php8.1-zip php8.1-bcmath php8.1-intl

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

# Instalar Supervisor
sudo apt install -y supervisor
```

#### Clonar y Configurar Aplicación
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

# Configurar base de datos
mysql -u root -p -e "CREATE DATABASE modustack_elymar_luxury;"
mysql -u root -p -e "CREATE USER 'modustack'@'localhost' IDENTIFIED BY 'secure_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON modustack_elymar_luxury.* TO 'modustack'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Ejecutar migraciones
php artisan migrate --force
php artisan db:seed --force

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Configurar Servicios
```bash
# Configurar Nginx
sudo cp nginx.conf /etc/nginx/sites-available/modustack-elymar-luxury
sudo ln -s /etc/nginx/sites-available/modustack-elymar-luxury /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# Configurar Supervisor
sudo cp supervisor.conf /etc/supervisor/conf.d/modustack-workers.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start modustack-workers:*

# Configurar Cron
(crontab -l 2>/dev/null; echo "* * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Optimizar aplicación
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Deployment Automático con Scripts

#### Script de Deployment
```bash
#!/bin/bash
# deploy.sh

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Iniciando deployment de ModuStackElyMarLuxury...${NC}"

# Variables
APP_DIR="/var/www/modustack-elymar-luxury"
BACKUP_DIR="/var/backups/modustack-elymar-luxury"
REPO_URL="https://github.com/tu-usuario/ModuStackElyMarLuxury.git"
BRANCH="main"

# Crear directorio de aplicación
echo -e "${YELLOW}📁 Creando directorio de aplicación...${NC}"
sudo mkdir -p $APP_DIR
sudo chown -R www-data:www-data $APP_DIR

# Crear directorio de respaldos
echo -e "${YELLOW}📁 Creando directorio de respaldos...${NC}"
sudo mkdir -p $BACKUP_DIR
sudo chown -R www-data:www-data $BACKUP_DIR

# Respaldar aplicación actual
if [ -d "$APP_DIR" ]; then
    echo -e "${YELLOW}💾 Respaldando aplicación actual...${NC}"
    sudo cp -r $APP_DIR $BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S)
fi

# Clonar/Actualizar repositorio
echo -e "${YELLOW}📥 Clonando/Actualizando repositorio...${NC}"
if [ -d "$APP_DIR/.git" ]; then
    cd $APP_DIR
    sudo -u www-data git pull origin $BRANCH
else
    sudo -u www-data git clone $REPO_URL $APP_DIR
    cd $APP_DIR
    sudo -u www-data git checkout $BRANCH
fi

# Instalar dependencias
echo -e "${YELLOW}📦 Instalando dependencias PHP...${NC}"
sudo -u www-data composer install --no-dev --optimize-autoloader

echo -e "${YELLOW}📦 Instalando dependencias Node.js...${NC}"
sudo -u www-data npm install
sudo -u www-data npm run build

# Configurar entorno
echo -e "${YELLOW}⚙️ Configurando entorno...${NC}"
if [ ! -f "$APP_DIR/.env" ]; then
    sudo -u www-data cp .env.example .env
    sudo -u www-data php artisan key:generate
fi

# Ejecutar migraciones
echo -e "${YELLOW}🗄️ Ejecutando migraciones...${NC}"
sudo -u www-data php artisan migrate --force

# Configurar permisos
echo -e "${YELLOW}🔐 Configurando permisos...${NC}"
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 775 $APP_DIR/storage $APP_DIR/bootstrap/cache

# Configurar Nginx
echo -e "${YELLOW}🌐 Configurando Nginx...${NC}"
sudo cp nginx.conf /etc/nginx/sites-available/modustack-elymar-luxury
sudo ln -sf /etc/nginx/sites-available/modustack-elymar-luxury /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Configurar Supervisor
echo -e "${YELLOW}👷 Configurando Supervisor...${NC}"
sudo cp supervisor.conf /etc/supervisor/conf.d/modustack-workers.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart modustack-workers:*

# Configurar Cron
echo -e "${YELLOW}⏰ Configurando Cron...${NC}"
(crontab -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Optimizar aplicación
echo -e "${YELLOW}⚡ Optimizando aplicación...${NC}"
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Verificar deployment
echo -e "${YELLOW}🔍 Verificando deployment...${NC}"
sudo -u www-data php artisan system:status
sudo -u www-data php artisan system:monitor health

echo -e "${GREEN}✅ Deployment completado!${NC}"
echo -e "${GREEN}🌐 Aplicación disponible en: https://your-domain.com${NC}"
```

#### Ejecutar Script
```bash
# Hacer ejecutable
chmod +x deploy.sh

# Ejecutar deployment
./deploy.sh
```

### Deployment con Docker

#### Dockerfile
```dockerfile
# Dockerfile
FROM php:8.1-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos de aplicación
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Configurar permisos
RUN chown -R www-data:www-data /var/www
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Exponer puerto
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
```

#### docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: modustack-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - modustack

  nginx:
    image: nginx:alpine
    container_name: modustack-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
    depends_on:
      - app
    networks:
      - modustack

  mysql:
    image: mysql:8.0
    container_name: modustack-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: modustack_elymar_luxury
      MYSQL_USER: modustack
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - modustack

  redis:
    image: redis:alpine
    container_name: modustack-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - modustack

volumes:
  mysql_data:

networks:
  modustack:
    driver: bridge
```

#### Comandos Docker
```bash
# Construir y ejecutar
docker-compose up -d --build

# Ejecutar migraciones
docker-compose exec app php artisan migrate --force

# Ejecutar seeders
docker-compose exec app php artisan db:seed --force

# Configurar permisos
docker-compose exec app chown -R www-data:www-data /var/www
docker-compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Optimizar aplicación
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## 🔄 CI/CD Pipeline

### GitHub Actions

#### .github/workflows/deploy.yml
```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, iconv, json, mbstring, pdo, pdo_sqlite, phar, posix, session, simplexml, sqlite3, tokenizer, xml, xmlreader, xmlwriter, zip, zlib
        coverage: xdebug
        
    - name: Install dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader
      
    - name: Copy .env
      run: php -r "file_exists('.env') || copy('.env.example', '.env');"
      
    - name: Generate key
      run: php artisan key:generate
      
    - name: Run tests
      run: php artisan test --coverage
      
    - name: Run optimization tests
      run: php artisan test tests/Feature/OptimizationTest.php

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/modustack-elymar-luxury
          git pull origin main
          composer install --no-dev --optimize-autoloader
          npm install && npm run build
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo supervisorctl restart modustack-workers:*
          sudo systemctl reload nginx
```

### GitLab CI

#### .gitlab-ci.yml
```yaml
stages:
  - test
  - deploy

variables:
  MYSQL_DATABASE: modustack_elymar_luxury_test
  MYSQL_ROOT_PASSWORD: root
  MYSQL_PASSWORD: password

test:
  stage: test
  image: php:8.1
  services:
    - mysql:8.0
    - redis:alpine
  before_script:
    - apt-get update -yqq
    - apt-get install -yqq git curl libmcrypt-dev libjpeg-dev libpng-dev libfreetype6-dev libbz2-dev
    - docker-php-ext-install pdo_mysql
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --no-dev --optimize-autoloader
    - cp .env.example .env
    - php artisan key:generate
  script:
    - php artisan migrate --force
    - php artisan test --coverage
  coverage: '/^\s*Lines:\s*\d+\.\d+\%/'

deploy:
  stage: deploy
  image: alpine:latest
  before_script:
    - apk add --no-cache openssh-client
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
    - mkdir -p ~/.ssh
    - chmod 700 ~/.ssh
    - ssh-keyscan $SERVER_HOST >> ~/.ssh/known_hosts
  script:
    - ssh $SERVER_USER@$SERVER_HOST "cd /var/www/modustack-elymar-luxury && git pull origin main && composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && sudo supervisorctl restart modustack-workers:* && sudo systemctl reload nginx"
  only:
    - main
```

### Jenkins Pipeline

#### Jenkinsfile
```groovy
pipeline {
    agent any
    
    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }
        
        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-dev --optimize-autoloader'
                sh 'npm install && npm run build'
            }
        }
        
        stage('Test') {
            steps {
                sh 'php artisan test --coverage'
            }
        }
        
        stage('Deploy') {
            when {
                branch 'main'
            }
            steps {
                sh '''
                    ssh user@server "cd /var/www/modustack-elymar-luxury && \
                    git pull origin main && \
                    composer install --no-dev --optimize-autoloader && \
                    npm install && npm run build && \
                    php artisan migrate --force && \
                    php artisan config:cache && \
                    php artisan route:cache && \
                    php artisan view:cache && \
                    sudo supervisorctl restart modustack-workers:* && \
                    sudo systemctl reload nginx"
                '''
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            echo 'Deployment successful!'
        }
        failure {
            echo 'Deployment failed!'
        }
    }
}
```

## 📊 Monitoreo de Deployment

### Métricas de Deployment

#### Tiempo de Deployment
```bash
# Medir tiempo de deployment
start_time=$(date +%s)
# ... deployment steps ...
end_time=$(date +%s)
deployment_time=$((end_time - start_time))
echo "Deployment time: ${deployment_time}s"
```

#### Verificación de Deployment
```bash
# Verificar estado del sistema
php artisan system:status

# Verificar monitoreo
php artisan system:monitor health

# Verificar workers
php artisan workers:status

# Verificar jobs
php artisan jobs:manage status

# Verificar servicios externos
php artisan external-services:health
```

#### Alertas de Deployment
```bash
# Configurar alertas
if [ $? -eq 0 ]; then
    echo "Deployment successful"
    # Enviar notificación de éxito
else
    echo "Deployment failed"
    # Enviar notificación de error
fi
```

### Monitoreo Continuo

#### Health Checks
```bash
# Health check endpoint
curl -f https://your-domain.com/health || exit 1

# Verificar servicios
systemctl is-active --quiet nginx || exit 1
systemctl is-active --quiet mysql || exit 1
systemctl is-active --quiet redis || exit 1
systemctl is-active --quiet supervisor || exit 1
```

#### Métricas de Rendimiento
```bash
# Verificar uso de recursos
htop
iotop
nethogs

# Verificar logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log
```

## 🔧 Configuración de Servidores

### Servidor de Producción

#### Especificaciones Mínimas
- **OS**: Ubuntu 22.04 LTS
- **RAM**: 8GB
- **CPU**: 4 cores
- **Disk**: 100GB SSD
- **Network**: 1Gbps

#### Especificaciones Recomendadas
- **OS**: Ubuntu 22.04 LTS
- **RAM**: 16GB
- **CPU**: 8 cores
- **Disk**: 200GB SSD
- **Network**: 1Gbps

### Servidor de Staging

#### Especificaciones
- **OS**: Ubuntu 22.04 LTS
- **RAM**: 4GB
- **CPU**: 2 cores
- **Disk**: 50GB SSD
- **Network**: 100Mbps

### Servidor de Desarrollo

#### Especificaciones
- **OS**: Ubuntu 22.04 LTS
- **RAM**: 2GB
- **CPU**: 1 core
- **Disk**: 25GB SSD
- **Network**: 100Mbps

## 🚀 Estrategias de Deployment

### Blue-Green Deployment

#### Configuración
```bash
# Servidor Blue (Producción actual)
BLUE_SERVER="https://blue.your-domain.com"

# Servidor Green (Nueva versión)
GREEN_SERVER="https://green.your-domain.com"

# Switch de tráfico
switch_traffic() {
    # Cambiar DNS o Load Balancer
    # de BLUE_SERVER a GREEN_SERVER
}
```

#### Script de Blue-Green
```bash
#!/bin/bash
# blue-green-deploy.sh

# Configurar servidor Green
echo "Configurando servidor Green..."
# ... deployment steps ...

# Verificar servidor Green
echo "Verificando servidor Green..."
curl -f $GREEN_SERVER/health || exit 1

# Cambiar tráfico
echo "Cambiando tráfico a servidor Green..."
switch_traffic

# Verificar cambio
echo "Verificando cambio de tráfico..."
curl -f $BLUE_SERVER/health || echo "Servidor Blue desconectado"

# Mantener servidor Blue por 24 horas
echo "Manteniendo servidor Blue por 24 horas..."
sleep 86400

# Limpiar servidor Blue
echo "Limpiando servidor Blue..."
# ... cleanup steps ...
```

### Rolling Deployment

#### Configuración
```bash
# Servidores de aplicación
SERVERS=("server1" "server2" "server3" "server4")

# Deploy en cada servidor
for server in "${SERVERS[@]}"; do
    echo "Deploying to $server..."
    ssh $server "cd /var/www/modustack-elymar-luxury && git pull origin main && composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && sudo supervisorctl restart modustack-workers:* && sudo systemctl reload nginx"
    
    # Verificar servidor
    curl -f https://$server.your-domain.com/health || exit 1
    
    # Esperar antes del siguiente servidor
    sleep 30
done
```

### Canary Deployment

#### Configuración
```bash
# Porcentaje de tráfico para nueva versión
CANARY_PERCENTAGE=10

# Configurar canary
configure_canary() {
    # Configurar load balancer para enviar
    # CANARY_PERCENTAGE% del tráfico a nueva versión
}

# Monitorear canary
monitor_canary() {
    # Monitorear métricas de nueva versión
    # Si hay problemas, revertir
    # Si todo está bien, aumentar porcentaje
}
```

## 🔍 Verificación de Deployment

### Tests de Deployment

#### Tests Automáticos
```bash
# Test de conectividad
curl -f https://your-domain.com/health

# Test de base de datos
php artisan migrate:status

# Test de workers
php artisan workers:status

# Test de jobs
php artisan jobs:manage status

# Test de servicios externos
php artisan external-services:health

# Test de optimización
php artisan optimize:all
```

#### Tests Manuales
```bash
# Verificar funcionalidad
# 1. Acceder a la aplicación
# 2. Verificar login
# 3. Verificar funcionalidades principales
# 4. Verificar jobs
# 5. Verificar servicios externos
# 6. Verificar optimización
```

### Rollback de Deployment

#### Script de Rollback
```bash
#!/bin/bash
# rollback.sh

# Obtener versión anterior
PREVIOUS_VERSION=$(ls -t /var/backups/modustack-elymar-luxury/ | head -n 1)

# Restaurar versión anterior
echo "Restaurando versión anterior: $PREVIOUS_VERSION"
sudo cp -r /var/backups/modustack-elymar-luxury/$PREVIOUS_VERSION/* /var/www/modustack-elymar-luxury/

# Reiniciar servicios
sudo supervisorctl restart modustack-workers:*
sudo systemctl reload nginx

# Verificar rollback
curl -f https://your-domain.com/health || exit 1

echo "Rollback completado"
```

## 📈 Métricas de Deployment

### Métricas de Tiempo
- **Tiempo de Deployment**: Tiempo total del deployment
- **Tiempo de Downtime**: Tiempo de inactividad
- **Tiempo de Verificación**: Tiempo de verificación
- **Tiempo de Rollback**: Tiempo de rollback

### Métricas de Calidad
- **Tasa de Éxito**: Porcentaje de deployments exitosos
- **Tasa de Fallos**: Porcentaje de deployments fallidos
- **Tasa de Rollback**: Porcentaje de rollbacks
- **Tiempo de Recuperación**: Tiempo de recuperación de fallos

### Métricas de Rendimiento
- **Tiempo de Respuesta**: Tiempo de respuesta de la aplicación
- **Uso de Recursos**: Uso de CPU, memoria, disco
- **Throughput**: Número de requests por segundo
- **Error Rate**: Tasa de errores

## 🎯 Conclusión

El deployment de ModuStackElyMarLuxury está **completamente documentado** y proporciona:

- **Deployment manual** paso a paso
- **Deployment automático** con scripts
- **Deployment con Docker** completo
- **CI/CD pipelines** para GitHub, GitLab y Jenkins
- **Estrategias de deployment** avanzadas
- **Monitoreo y verificación** completa
- **Rollback automático** en caso de fallos

El sistema está **completamente desplegado** y listo para uso en producción.

---

**ModuStackElyMarLuxury** - Sistema completo de gestión empresarial



