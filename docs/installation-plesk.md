# Installing KelvCMC on Plesk shared hosting

KelvCMC is designed to run on a classic **Plesk** stack: PHP 8.4+ with MariaDB/MySQL and Composer, served through the Plesk web server (Apache/Nginx). This guide walks you through the whole setup.

> **Tip:** A Plesk **Dedicated/VPS** subscription is recommended. Very restricted shared plans (no SSH, no cron, no Composer) will not be able to run the scheduler or queue worker.

---

## 1. Create the subscription & database

1. In Plesk, create a **Subscription** for KelvCMC:
   - Domain: `panel.yourcompany.com`
   - PHP version: **8.4** (Plesk → PHP Settings → choose "8.4.x" and enable extensions: `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `zip`, `openssl`).
2. Create a **Database**:
   - Plesk → Databases → *Add Database*
   - Name: `kelvcmc`, type **MySQL/MariaDB**, create a dedicated user with a strong password.
3. Note the **docroot** path (usually `/var/www/vhosts/panel.yourcompany.com/httpdocs`).

## 2. Upload the code

```bash
# from your local machine
scp -r kelvcmc root@your-server:/tmp/kelvcmc
# or clone directly on the server
cd /var/www/vhosts/panel.yourcompany.com/httpdocs
git clone https://github.com/kelv/kelvcmc.git .
```

Move all files so that `artisan` and `public/` are at the docroot level:

```
httpdocs/
├── app/
├── public/        ← Plesk must serve this directory
├── artisan
└── ...
```

> **CRITICAL — Document root:** Laravel requires the document root to be `public/`, NOT the project root. If you get "File not found" or a blank page, this is the cause.
>
> **Method A (recommended):** In Plesk, go to *Hosting Settings* → change *Document root* from `httpdocs` to `httpdocs/public`. Then reload the page.
>
> **Method B (fallback):** If you cannot change the document root, KelvCMC includes a root `index.php` and `.htaccess` that automatically forward all requests to `public/`. Just make sure `mod_rewrite` is enabled and `.htaccess` files are allowed (Plesk → Apache & nginx Settings → "Allow .htaccess").

## 3. Fix storage permissions (critical!)

On Plesk, the web server runs as the subscription system user, but SSH runs as your login user. Without correct permissions, Laravel cannot write to `storage/` and `bootstrap/cache/`.

```bash
cd httpdocs

# Create the storage directories if they don't exist (fresh git clone)
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views
mkdir -p storage/logs storage/app/private storage/app/public bootstrap/cache

# Set the correct group ownership (replace "systeemgebruiker" with your subscription user)
chown -R systeemgebruiker:psacln storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

> The `.gitkeep` files in `storage/` ensure the directories survive a git clone. If you deployed via Plesk Git, run the `mkdir` and `chmod` commands via SSH after the first deployment.

## 4. Install dependencies

```bash
cd httpdocs
composer install --no-dev --optimize-autoloader --prefer-dist
```

If Composer is not available in your SSH session, download it once:

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

## 5. Environment

```bash
cp .env.example .env
nano .env          # fill in DB_* with the Plesk database credentials
```

The `kelvcmc:install` wizard handles `APP_KEY` generation automatically.

Required `.env` values on Plesk:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://panel.yourcompany.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kelvcmc
DB_USERNAME=kelvcmc_user
DB_PASSWORD=your_strong_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database     # or redis if available
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
```

> **Never commit `.env`** — it is gitignored.

## 6. Setup wizard

```bash
php artisan kelvcmc:install --force
```

The interactive wizard generates `APP_KEY`, runs migrations, creates roles/permissions, default settings, the storage link, and your first administrator. Use `--no-demo` for a clean production database.

> Use a unique administrator password and enable 2FA after the first login.

## 7. Build frontend assets

```bash
npm install && npm run build
```

(If Node is unavailable, download and build locally, then upload `public/build/`.)

## 8. SSL & docroot

1. Plesk → *SSL/TLS Certificates* → issue a **Let's Encrypt** certificate for `panel.yourcompany.com`.
2. Make sure `https://` redirects work (Plesk → Hosting → "SSL/TLS" → enable redirect to HTTPS).

## 9. Scheduler (cron) & queue worker

Billing automation relies on the Laravel scheduler:

```
* * * * * cd /var/www/vhosts/panel.yourcompany.com/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

Add it via SSH (`crontab -e`) or Plesk → **Scheduled Tasks** → *Run a command*:

| When | Command |
| --- | --- |
| Every minute | `cd httpdocs && php artisan schedule:run` |

For the **queue worker**, run continuously (background task in Plesk, or systemd/supervisor on a VPS):

```bash
php artisan queue:work --tries=3 --timeout=120
```

> On shared Plesk without SSH persistence, you can approximate the worker with a cron job every minute:
> `cd httpdocs && php artisan queue:work --stop-when-empty` — this processes queued jobs (emails, provisioning) within a minute. A real daemon is better on a VPS.

## 10. Plesk API integration (optional)

To let KelvCMC provision hosting automatically on the **same** Plesk server:

1. Create a dedicated **Plesk API user** (Plesk → Users → Create user with "API" access) or use the admin account.
2. Enable API access: Plesk → *Tools & Settings → API Restrictions* (or Plesk 18: *Developer → API*) — enable RPC and key management.
3. Set in `.env`:

```ini
PLESK_ENABLED=true
PLESK_HOST=your-server-hostname-or-ip
PLESK_PORT=8443
PLESK_USERNAME=your_api_user
PLESK_PASSWORD=your_api_password
PLESK_VERIFY_SSL=false
```

4. Create a **server** (Admin → Hosting → Servers) with integration `plesk`, and a **server group**.
5. Assign products (e.g. "Shared Web Hosting") to that server group and set their module to `plesk`.

KelvCMC will then create Plesk clients, webspaces, databases and Let's Encrypt certs automatically, and suspend them on non-payment.

## 11. Diagnostics

Run the health checker after installation and after every major change:

```bash
php artisan kelvcmc:doctor
```

It verifies PHP, extensions, APP_KEY, database, filesystem permissions, cache, queue, Filament assets, and configuration.

## 12. Post-install checklist

- [ ] Admin password changed, 2FA enabled for admins (Settings → Security)
- [ ] `APP_DEBUG=false` and a strong `APP_KEY`
- [ ] SMTP configured and test emails received
- [ ] Cron entry added, queue worker running
- [ ] Storage `chmod -R 775 storage bootstrap/cache`
- [ ] `bootstrap/cache/` exists and is writable (missing = 500 error)
- [ ] HTTPS forced, `.env` unreachable from the web
- [ ] Gateway keys and Plesk/Pterodactyl/Proxmox credentials configured

---

Next: [Production hardening](production.md) · [Developer guide: plugins](plugins.md) · [Developer guide: themes](themes.md) · [API documentation](api.md)
