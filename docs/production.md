# KelvCMC production guide

Everything you need to run KelvCMC reliably in production: server setup, background jobs, Redis, hardening and monitoring.

## Server requirements

| Component | Minimum | Recommended |
| --- | --- | --- |
| PHP | 8.4 | 8.4 with OPcache |
| MariaDB/MySQL | 10.6 / 8.0 | MariaDB 10.11+ |
| RAM | 1 GB | 2 GB+ |
| Redis | — | 1 instance (queues + cache) |
| Node (build only) | 20 | 20+ |

## Background processing

KelvCMC uses **Laravel Queues** for emails, provisioning, webhooks and billing jobs.

### 1. Redis

```bash
apt install redis-server
```

`.env`:

```ini
REDIS_CLIENT=phpredis       # or predis (already in composer.json)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis        # optional, great for multi-node
```

### 2. Supervisor (queue worker)

`/etc/supervisor/conf.d/kelvcmc-worker.conf`:

```ini
[program:kelvcmc-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kelvcmc/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/kelvcmc/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start kelvcmc-worker:*
```

### 3. Scheduler (cron)

```bash
* * * * * cd /var/www/kelvcmc && php artisan schedule:run >> /dev/null 2>&1
```

The schedule (defined in `bootstrap/app.php`):

| Time | Task |
| --- | --- |
| 00:30 | `kelvcmc:invoices:generate` — renewal invoices + mark overdue |
| 09:00 | `kelvcmc:invoices:remind` — email reminders |
| 01:00 | `kelvcmc:services:suspend-expired` — suspend & terminate unpaid services |
| Every 5 min | `kelvcmc:services:provision-pending` — provision queued services |
| 04:00 | `kelvcmc:audit:prune` — prune old audit logs |

## Web server (Nginx)

```nginx
server {
    listen 443 ssl http2;
    server_name panel.yourcompany.com;

    root /var/www/kelvcmc/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/panel.yourcompany.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/panel.yourcompany.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Hardening checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Strong `APP_KEY` (never share it — it encrypts credentials, passwords and 2FA secrets)
- [ ] HTTPS only (HSTS header)
- [ ] Admin panel protected by 2FA — enable *Settings → Security → Force 2FA for administrators*
- [ ] Restrict `/admin` by IP if desired (nginx `allow/deny`)
- [ ] Use a unique administrator password and enable 2FA
- [ ] `.env` permissions: `chmod 600 .env` (owner `www-data`)
- [ ] Filesystem permissions: `chown -R www-data:www-data storage bootstrap/cache`
- [ ] Database user with least privilege (only `kelvcmc` database)
- [ ] Rate limiting enabled (defaults: 5 login attempts/min, API 60/min)
- [ ] Audit logs retention configured in `.env` (`KELVCMC_AUDIT_RETENTION_DAYS`)

## Pterodactyl / Proxmox / DNS integrations

### Pterodactyl

```ini
PTERODACTYL_ENABLED=true
PTERODACTYL_URL=https://panel.pterodactyl.yourcompany.com
PTERODACTYL_API_KEY=ptla_xxxxxxxx
```

Then create a product with module `pterodactyl` and, in its *metadata* config, set the `pterodactyl_user_id`, `egg_id`, `node_id` and `allocation_id` used for provisioning (these are per-checkout values your order form supplies, or defaults handled in `PterodactylHostingProvider`). See `app/Integrations/Pterodactyl/` for the full request shape.

### Proxmox

```ini
PROXMOX_ENABLED=true
PROXMOX_URL=https://pve.yourcompany.com:8006
PROXMOX_USER=kelvcmc@pve
PROXMOX_TOKEN_NAME=kelvcmc
PROXMOX_TOKEN_VALUE=your-token
PROXMOX_VERIFY_SSL=false
```

VMs are created on the node referenced by the product's server (`hostname` = node name), with resources read from the plan (`cpu_cores`, `ram_mb`, `disk_mb`).

### DNS (Cloudflare / PowerDNS)

```ini
CLOUDFLARE_ENABLED=true
CLOUDFLARE_API_TOKEN=...
CLOUDFLARE_ZONE_ID=...
# or
POWERDNS_ENABLED=true
POWERDNS_URL=https://pdns.yourcompany.com:8081
POWERDNS_API_KEY=...
```

Used by the **Domain module** (`/domains`) for availability checks and DNS record management.

## Monitoring & backups

- Back up the **database** nightly and the **storage/** directory (uploads, logs, PDFs).
- Watch `storage/logs/laravel.log` and `failed_jobs` table for stuck jobs:
  ```bash
  php artisan queue:failed
  php artisan queue:retry all
  ```
- Optional: enable Laravel Telescope-like logging or ship logs to Papertrail (see `config/logging.php`).

## Scaling

- Add more queue workers: `numprocs` in supervisor (Redis locks prevent overlaps).
- Cache store: Redis. Database: use a managed MySQL/MariaDB if traffic grows.
- Sessions in Redis allow multiple web nodes behind a load balancer.

Next: [Plugins](plugins.md) · [Themes](themes.md) · [API](api.md)
