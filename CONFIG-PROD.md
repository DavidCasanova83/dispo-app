# 🚀 Configuration Production - Dispo-App

Guide complet pour le déploiement et la configuration en production de l'application Dispo-App.

## 📋 Prérequis Serveur

### Minimum Requis
- **OS**: Ubuntu 20.04+ / Debian 11+ / CentOS 8+
- **PHP**: 8.2 ou supérieur avec extensions requises
- **Base de données**: PostgreSQL 13+ ou MySQL 8.0+
- **Web Server**: Nginx 1.18+ ou Apache 2.4+
- **Redis**: 6.2+ (pour cache et queues)
- **Node.js**: 18+ (pour build des assets)
- **Supervisor**: Pour gestion des workers de queue

### Extensions PHP Requises
```bash
# Ubuntu/Debian
sudo apt install php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml \
php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath \
php8.2-redis php8.2-pgsql php8.2-mysql

# Configuration recommandée
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 20M
post_max_size = 20M
```

## ⚙️ Variables d'Environnement Production

### Fichier .env Production
```env
# Application
APP_NAME="Dispo App"
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE_32_CARACTERES
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Base de données PostgreSQL (recommandé)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dispo_app_prod
DB_USERNAME=dispo_app_user
DB_PASSWORD=MOT_DE_PASSE_SECURISE

# Cache et Sessions Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=MOT_DE_PASSE_REDIS
REDIS_PORT=6379

# Queue Redis
QUEUE_CONNECTION=redis
REDIS_QUEUE_HOST=127.0.0.1
REDIS_QUEUE_PASSWORD=MOT_DE_PASSE_REDIS
REDIS_QUEUE_PORT=6379

# Email (configuration selon votre provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-provider.com
MAIL_PORT=587
MAIL_USERNAME=noreply@votre-domaine.com
MAIL_PASSWORD=MOT_DE_PASSE_EMAIL
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="Dispo App"

# API Apidae
APIDAE_API_KEY=VOTRE_CLE_API_APIDAE
APIDAE_PROJECT_ID=VOTRE_PROJECT_ID
APIDAE_SELECTION_ID=VOTRE_SELECTION_ID

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAYS=14

# Sécurité
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
SANCTUM_STATEFUL_DOMAINS=votre-domaine.com
```

## 🗄️ Configuration Base de Données

### PostgreSQL (Recommandé)
```sql
-- Créer la base de données et l'utilisateur
CREATE DATABASE dispo_app_prod;
CREATE USER dispo_app_user WITH PASSWORD 'MOT_DE_PASSE_SECURISE';
GRANT ALL PRIVILEGES ON DATABASE dispo_app_prod TO dispo_app_user;

-- Optimisations PostgreSQL
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET random_page_cost = 1.1;
SELECT pg_reload_conf();
```

### Sauvegarde Automatique
```bash
# Script de sauvegarde quotidienne
#!/bin/bash
# /etc/cron.daily/backup-dispo-app
BACKUP_DIR="/var/backups/dispo-app"
DATE=$(date +%Y%m%d_%H%M%S)

# Créer le répertoire si nécessaire
mkdir -p $BACKUP_DIR

# Sauvegarde base de données
pg_dump -h localhost -U dispo_app_user dispo_app_prod | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Conserver seulement les 7 derniers jours
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +7 -delete

# Permissions
chmod 600 $BACKUP_DIR/db_backup_$DATE.sql.gz
```

## 🌐 Configuration Web Server

### Nginx (Recommandé)
```nginx
# /etc/nginx/sites-available/dispo-app
server {
    listen 80;
    server_name votre-domaine.com www.votre-domaine.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name votre-domaine.com www.votre-domaine.com;
    root /var/www/dispo-app/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/votre-domaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/votre-domaine.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Performance
    gzip on;
    gzip_vary on;
    gzip_min_length 10240;
    gzip_proxied expired no-cache no-store private must-revalidate;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Laravel configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Security
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Logs
    access_log /var/log/nginx/dispo-app.access.log;
    error_log /var/log/nginx/dispo-app.error.log;
}
```

## 🔄 Configuration Supervisor (Workers Queue)

### Configuration Supervisor
```ini
# /etc/supervisor/conf.d/dispo-app-worker.conf
[program:dispo-app-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dispo-app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=apidae-sync,default
directory=/var/www/dispo-app
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/dispo-app-worker.log
stopwaitsecs=3600

[program:dispo-app-scheduler]
process_name=%(program_name)s
command=php /var/www/dispo-app/artisan schedule:work
directory=/var/www/dispo-app
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/dispo-app-scheduler.log
```

### Commandes Supervisor
```bash
# Relancer la configuration
sudo supervisorctl reread
sudo supervisorctl update

# Gérer les workers
sudo supervisorctl start dispo-app-worker:*
sudo supervisorctl restart dispo-app-worker:*
sudo supervisorctl status

# Logs
sudo supervisorctl tail -f dispo-app-worker:*
```

## ⏰ Configuration Cron (Alternative au Scheduler)

### Crontab Principal
```bash
# Éditer le crontab pour www-data
sudo crontab -u www-data -e

# Ajouter ces lignes
# Laravel Scheduler (principal)
* * * * * cd /var/www/dispo-app && php artisan schedule:run >> /dev/null 2>&1

# Synchronisation manuelle de secours (si scheduler échoue)
0 5 * * * cd /var/www/dispo-app && php artisan queue:work --stop-when-empty --queue=apidae-sync >> /var/log/cron-apidae.log 2>&1

# Nettoyage logs application
0 2 * * 0 cd /var/www/dispo-app && php artisan log:clear --keep=14 >> /dev/null 2>&1

# Nettoyage cache périodique
0 3 * * * cd /var/www/dispo-app && php artisan cache:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache

# Monitoring des jobs échoués
0 8 * * * cd /var/www/dispo-app && php artisan queue:monitor >> /var/log/queue-monitor.log 2>&1
```

## 🔧 Déploiement

### Script de Déploiement
```bash
#!/bin/bash
# deploy.sh - Script de déploiement production

set -e

APP_DIR="/var/www/dispo-app"
BACKUP_DIR="/var/backups/dispo-app"
DATE=$(date +%Y%m%d_%H%M%S)

echo "🚀 Début du déploiement..."

# 1. Sauvegarde avant déploiement
echo "📦 Sauvegarde de la base de données..."
mkdir -p $BACKUP_DIR
pg_dump -h localhost -U dispo_app_user dispo_app_prod | gzip > $BACKUP_DIR/pre_deploy_$DATE.sql.gz

# 2. Mode maintenance
echo "🔧 Activation du mode maintenance..."
cd $APP_DIR
php artisan down --retry=60

# 3. Mise à jour du code
echo "📥 Mise à jour du code..."
git pull origin main

# 4. Installation des dépendances
echo "📚 Installation des dépendances..."
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# 5. Cache et optimisations
echo "⚡ Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Migrations
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# 7. Redémarrage des services
echo "🔄 Redémarrage des workers..."
sudo supervisorctl restart dispo-app-worker:*

# 8. Vérifications
echo "✅ Vérifications..."
php artisan queue:monitor
php artisan schedule:list

# 9. Sortie du mode maintenance
echo "🎉 Désactivation du mode maintenance..."
php artisan up

echo "✅ Déploiement terminé avec succès !"
```

## 📊 Monitoring et Logs

### Configuration des Logs
```php
// config/logging.php (ajouts production)
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 14,
        'permission' => 0664,
    ],
    
    'apidae' => [
        'driver' => 'daily',
        'path' => storage_path('logs/apidae.log'),
        'level' => 'info',
        'days' => 30,
    ],
    
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'warning',
        'days' => 7,
    ],
],
```

### Surveillance Système
```bash
# Script de monitoring /usr/local/bin/monitor-dispo-app.sh
#!/bin/bash

APP_DIR="/var/www/dispo-app"
LOG_FILE="/var/log/dispo-app-monitor.log"

# Vérifier les workers de queue
if ! supervisorctl status dispo-app-worker:* | grep -q RUNNING; then
    echo "$(date): ALERTE - Workers de queue arrêtés" >> $LOG_FILE
    supervisorctl restart dispo-app-worker:*
fi

# Vérifier l'espace disque
DISK_USAGE=$(df /var | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "$(date): ALERTE - Espace disque faible: ${DISK_USAGE}%" >> $LOG_FILE
fi

# Vérifier les logs d'erreur
ERROR_COUNT=$(grep -c "ERROR\|CRITICAL" $APP_DIR/storage/logs/laravel-$(date +%Y-%m-%d).log 2>/dev/null || echo 0)
if [ $ERROR_COUNT -gt 10 ]; then
    echo "$(date): ALERTE - Trop d'erreurs détectées: $ERROR_COUNT" >> $LOG_FILE
fi

# Ajouter au crontab
# */5 * * * * /usr/local/bin/monitor-dispo-app.sh
```

## 🔒 Sécurité

### Checklist Sécurité
- [ ] SSL/TLS configuré avec certificats valides
- [ ] Headers de sécurité configurés
- [ ] Firewall configuré (ports 22, 80, 443 uniquement)
- [ ] Utilisateur dédié pour l'application (www-data)
- [ ] Permissions fichiers restrictives (644 pour fichiers, 755 pour dossiers)
- [ ] Sauvegarde régulière et testée
- [ ] Logs surveillés
- [ ] Updates système automatiques
- [ ] Clés API sécurisées et rotées régulièrement

### Configuration Firewall
```bash
# UFW (Ubuntu)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## 📈 Performance

### Optimisations Recommandées
```bash
# OPcache PHP
echo "opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=0" >> /etc/php/8.2/fpm/conf.d/10-opcache.ini

# Redis tuning
echo "maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1" >> /etc/redis/redis.conf
```

### Métriques à Surveiller
- Temps de réponse moyen < 200ms
- Utilisation CPU < 70%
- Utilisation RAM < 80%
- Taille des logs < 1GB
- Queue jobs en attente < 100
- Taux d'erreur < 1%

## 🆘 Dépannage

### Problèmes Courants
```bash
# Queue bloquée
php artisan queue:restart
supervisorctl restart dispo-app-worker:*

# Cache corrompu
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Permissions
sudo chown -R www-data:www-data /var/www/dispo-app
sudo chmod -R 755 /var/www/dispo-app
sudo chmod -R 775 /var/www/dispo-app/storage
sudo chmod -R 775 /var/www/dispo-app/bootstrap/cache

# Logs de debugging
tail -f /var/www/dispo-app/storage/logs/laravel.log
tail -f /var/log/nginx/dispo-app.error.log
tail -f /var/log/supervisor/dispo-app-worker.log
```

---

## 📞 Support Production

En cas de problème critique :
1. Vérifier les logs dans `/var/www/dispo-app/storage/logs/`
2. Vérifier le status des services : `supervisorctl status`
3. Vérifier l'espace disque : `df -h`
4. Redémarrer les workers si nécessaire
5. Contacter l'équipe technique avec les logs d'erreur

**🚨 En cas de panne totale :**
```bash
# Restauration rapide
cd /var/www/dispo-app
php artisan down
git reset --hard HEAD
composer install --no-dev
php artisan migrate
php artisan up
```