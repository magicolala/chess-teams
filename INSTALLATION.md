# 🚀 Guide d'Installation - Chess-Teams

Ce guide détaillé vous accompagne dans l'installation et la configuration de Chess-Teams sur différents environnements.

## 📋 Prérequis Système

### Minimaux Requis
- **PHP** : 8.1+ avec extensions : `ctype`, `iconv`, `json`, `tokenizer`
- **Composer** : 2.0+
- **Base de données** : PostgreSQL 13+ ou MySQL 8.0+
- **Serveur web** : Apache 2.4+ ou Nginx 1.18+
- **Git** : Pour cloner le repository

### Recommandés
- **PHP** : 8.2+ (performance améliorée)
- **Redis** : 6.0+ (cache et sessions)
- **Node.js** : 18+ (pour développement frontend)
- **Symfony CLI** : Pour outils de développement

## 🖥️ Installation sur Windows

### 1. Installer PHP avec XAMPP
```bash
# Télécharger XAMPP avec PHP 8.1+
# https://www.apachefriends.org/download.html

# Activer les extensions PHP dans php.ini
extension=ctype
extension=iconv
extension=pdo_mysql
extension=redis  # optionnel
```

### 2. Installer Composer
```bash
# Télécharger depuis https://getcomposer.org/Composer-Setup.exe
# Ou via PowerShell
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

### 3. Configurer la base de données
```sql
-- MySQL/MariaDB
CREATE DATABASE chess_teams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'chess_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON chess_teams.* TO 'chess_user'@'localhost';
```

## 🐧 Installation sur Linux (Ubuntu/Debian)

### 1. Installer PHP et dépendances
```bash
# Mettre à jour les paquets
sudo apt update && sudo apt upgrade -y

# Installer PHP 8.1+
sudo apt install php8.1 php8.1-fpm php8.1-cli php8.1-common \
    php8.1-mysql php8.1-pgsql php8.1-xml php8.1-curl \
    php8.1-gd php8.1-mbstring php8.1-zip php8.1-redis

# Installer Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Installer PostgreSQL (recommandé)
```bash
# Installer PostgreSQL
sudo apt install postgresql postgresql-contrib

# Créer utilisateur et base
sudo -u postgres psql
CREATE DATABASE chess_teams;
CREATE USER chess_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE chess_teams TO chess_user;
\q
```

### 3. Configurer Nginx (optionnel)
```nginx
# /etc/nginx/sites-available/chess-teams
server {
    listen 80;
    server_name chess-teams.local;
    root /path/to/chess-teams/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }
}
```

## 🍎 Installation sur macOS

### 1. Installer via Homebrew
```bash
# Installer Homebrew si nécessaire
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Installer PHP et dépendances
brew install php@8.1 composer postgresql redis

# Démarrer les services
brew services start postgresql
brew services start redis
```

### 2. Configurer PostgreSQL
```bash
# Créer utilisateur et base
createdb chess_teams
psql chess_teams
CREATE USER chess_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE chess_teams TO chess_user;
\q
```

## 📦 Installation du Projet

### 1. Cloner le repository
```bash
git clone https://github.com/magicolala/chess-teams.git
cd chess-teams
```

### 2. Installer les dépendances
```bash
# Dépendances PHP
composer install --optimize-autoloader

# Vérifier l'installation
php bin/console --version
```

### 3. Configuration de l'environnement
```bash
# Copier le fichier d'environnement
cp .env .env.local

# Éditer .env.local
nano .env.local  # ou votre éditeur préféré
```

### Exemple de configuration `.env.local` :
```bash
# Environnement
APP_ENV=dev
APP_SECRET=change-me-to-a-random-secret-key

# Base de données PostgreSQL
DATABASE_URL="postgresql://chess_user:secure_password@127.0.0.1:5432/chess_teams?serverVersion=15&charset=utf8"

# Ou MySQL
# DATABASE_URL="mysql://chess_user:secure_password@127.0.0.1:3306/chess_teams?serverVersion=8.0"

# Redis (optionnel)
REDIS_URL=redis://localhost:6379

# CORS pour développement
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

# Mailer (optionnel)
MAILER_DSN=smtp://localhost:1025
```

### 4. Initialiser la base de données
```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Charger les données de test (optionnel)
php bin/console doctrine:fixtures:load --no-interaction
```

### 5. Compiler les assets
```bash
# Compiler pour le développement
php bin/console asset-map:compile

# Ou pour la production
php bin/console asset-map:compile --env=prod
```

### 6. Démarrer le serveur
```bash
# Option 1: Symfony CLI (recommandé)
symfony server:start

# Option 2: Serveur PHP intégré
php -S localhost:8000 -t public/

# Option 3: Avec un serveur web configuré
# Configurez Apache/Nginx pour pointer vers public/
```

## 🔧 Configuration Avancée

### Redis pour les sessions (recommandé)
```bash
# .env.local
REDIS_URL=redis://localhost:6379/1

# config/packages/framework.yaml
framework:
    session:
        handler_id: 'redis://localhost:6379/1'
```

### Configuration pour la Production
```bash
# Optimisations
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Permissions (Linux/macOS)
sudo chown -R www-data:www-data var/
sudo chmod -R 755 var/
```

### Configuration HTTPS
```bash
# Avec Symfony CLI
symfony server:ca:install
symfony server:start --port=8443

# Ou configurer votre serveur web avec certificats SSL
```

## 🧪 Vérification de l'Installation

### 1. Tests automatisés
```bash
# Exécuter tous les tests
./vendor/bin/phpunit

# Tests avec couverture
./vendor/bin/phpunit --coverage-html coverage/
```

### 2. Vérifications manuelles
```bash
# Vérifier la configuration
php bin/console about

# Vérifier les routes
php bin/console debug:router

# Vérifier la base de données
php bin/console doctrine:schema:validate
```

### 3. Accéder à l'application
- **URL** : http://localhost:8000 (ou votre configuration)
- **Profiler** : http://localhost:8000/_profiler (en développement)
- **API** : http://localhost:8000/api/doc (si configuré)

## ❌ Dépannage Courant

### Erreur de permissions (Linux/macOS)
```bash
sudo chown -R $USER:www-data .
sudo chmod -R 775 var/
```

### Extension PHP manquante
```bash
# Ubuntu/Debian
sudo apt install php8.1-extension-name

# macOS
brew install php@8.1
# puis redémarrer le serveur
```

### Problème de base de données
```bash
# Vérifier la connexion
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:status
```

### Problème d'assets
```bash
# Nettoyer et recompiler
rm -rf public/assets/
php bin/console asset-map:compile
```

## 🔒 Sécurité

### Variables sensibles
```bash
# Générer une clé secrète forte
php bin/console secrets:generate-keys
php bin/console secrets:set APP_SECRET
```

### Configuration Firewall
```yaml
# config/packages/security.yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: auto
            cost: 12
```

## 📈 Performance

### OPcache (Production)
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=4000
```

### APCu (optionnel)
```bash
# Ubuntu/Debian
sudo apt install php8.1-apcu

# Configuration Symfony
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.apcu
```

## 🚀 Déploiement

### Checklist pré-déploiement
- [ ] Tests passants
- [ ] Configuration production
- [ ] Variables d'environnement sécurisées
- [ ] Base de données migrée
- [ ] Assets compilés
- [ ] Permissions correctes
- [ ] Sauvegarde disponible

### Commandes de déploiement
```bash
# Script de déploiement type
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear --env=prod
php bin/console asset-map:compile --env=prod
```

---

🎉 **Félicitations !** Votre installation de Chess-Teams est maintenant prête.

Pour plus d'aide, consultez le [README.md](README.md) ou créez une [issue GitHub](https://github.com/magicolala/chess-teams/issues).

---

Ressources complémentaires:

- Guide rapide et commandes Docker: voir `README.md` (sections Installation et Démarrage Rapide).
- Guide détaillé pour agents IA et contributeurs (Windows/PowerShell, Docker, bonnes pratiques): voir `AGENT_GUIDE.md`.
