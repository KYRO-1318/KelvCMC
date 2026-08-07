# KelvCMC — installation professionnelle

KelvCMC est un panel Laravel 12 pour hébergement, commandes, facturation et support.

## Prérequis

- Ubuntu 24.04 LTS
- PHP 8.4 avec `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `zip`, `gd`, `bcmath`
- MariaDB 10.6+ ou MySQL 8+
- Composer 2
- Node.js 20+ et npm
- Nginx

## Installation VPS Ubuntu 24.04

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mariadb-server git unzip curl \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath
```

Installer Composer et Node.js, puis préparer la base :

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

sudo mariadb
```

```sql
CREATE DATABASE kelvcmc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kelvcmc'@'127.0.0.1' IDENTIFIED BY 'mot-de-passe-fort';
GRANT ALL PRIVILEGES ON kelvcmc.* TO 'kelvcmc'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

Récupérer et construire l’application :

```bash
cd /var/www
git clone https://github.com/kelv/kelvcmc.git kelvcmc
cd kelvcmc
composer install --no-dev --optimize-autoloader --prefer-dist
npm install
npm run build
cp .env.example .env
```

## Installation CLI recommandée

Renseigner les identifiants DB dans `.env`, puis lancer :

```bash
php artisan kelvcmc:install --force
```

La commande vérifie PHP, extensions, Composer, Node et npm, génère `APP_KEY`, exécute les migrations, crée les rôles/permissions, demande le site, la devise, la langue et le premier administrateur, puis crée `storage/installed.lock`.

L'installation CLI et web initialise aussi le catalogue de démonstration demandé par KelvCMC. Pour une base de production sans données de démonstration, utilisez `--no-demo` avec la commande CLI. Pour ajouter le catalogue sur une base de démonstration vierge :

```bash
php artisan kelvcmc:install --force --demo
```

L’installateur est protégé contre une seconde installation. Utiliser `--force` uniquement pour une réparation maîtrisée et sauvegarder la base avant toute opération.

## Assistant web

Après avoir copié `.env.example` et installé Composer, ouvrir :

```text
https://votre-domaine.tld/install
```

Les six étapes sont :

1. vérification serveur ;
2. connexion MySQL/MariaDB ;
3. génération de `APP_KEY` ;
4. migrations ;
5. paramètres du site et compte admin ;
6. verrouillage et fin.

Le fichier `storage/installed.lock` désactive automatiquement `/install` après réussite. L’assistant web crée aussi le lien `public/storage`.

## Nginx

```nginx
server {
    listen 80;
    server_name panel.example.com;
    root /var/www/kelvcmc/public;
    index index.php;

    # Block access to sensitive files
    location ~ /\.(?!well-known).* { deny all; }
    location ~ /(composer\.(json|lock)|package\.json|package-lock\.json|vite\.config\.js|tailwind\.config\.js|artisan|phpunit\.xml) { deny all; }
    location ~ /(storage/.*\.(log|sql|zip|tar|gz)|vendor/.*) { deny all; }

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
}
```

```bash
sudo chown -R www-data:www-data /var/www/kelvcmc
sudo chmod -R ug+rwX storage bootstrap/cache
sudo systemctl reload nginx php8.4-fpm
```

Activer HTTPS avec Certbot avant d’utiliser des paiements :

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d panel.example.com
```

## Apache (Plesk / cPanel)

Si vous utilisez Apache au lieu de Nginx (ex. Plesk), le fichier `.htaccess` dans `public/` est déjà présent. Ajoutez ceci pour protéger les fichiers sensibles :

```apache
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
<FilesMatch "(composer\.(json|lock)|package\.json|package-lock\.json|vite\.config\.js|tailwind\.config\.js|artisan|phpunit\.xml)">
    Require all denied
</FilesMatch>
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /index.php?/$1 [L]
</IfModule>
```

## Résolution de l'erreur « Please provide a valid cache path »

Cette erreur survient sur Plesk ou après un `git clone` frais car `storage/framework/cache/`, `storage/framework/sessions/` et `storage/framework/views/` sont absents du dépôt. KelvCMC inclut désormais des `.gitkeep` pour éviter cela, mais si vous rencontrez l'erreur :

```bash
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views
mkdir -p storage/logs storage/app/private storage/app/public

# Sur Plesk (remplacer par votre utilisateur de souscription)
chown -R votre_utilisateur:psacln storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Puis relancer `composer install`.

## Vérification finale

```bash
php artisan optimize:clear
php artisan migrate --force
npm run build
php artisan kelvcmc:doctor
```

URLs principales : `/`, `/login`, `/dashboard`, `/admin`.

## Production

```bash
* * * * * cd /var/www/kelvcmc && php artisan schedule:run >> /dev/null 2>&1
```

Lancer un worker supervisé :

```bash
php artisan queue:work --tries=3 --timeout=120
```

Checklist :

- `APP_ENV=production`, `APP_DEBUG=false` ;
- mot de passe admin changé et 2FA activée ;
- clés de paiement et SMTP configurées ;
- sauvegardes quotidiennes de la base et de `storage/` ;
- `.env` non accessible publiquement ;
- `storage/installed.lock` présent ;
- `php artisan optimize` exécuté après configuration.
