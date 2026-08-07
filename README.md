<div align="center">

<img src="public/logo.svg" width="96" alt="KelvCMC logo">

# KelvCMC — Kelv Cloud Management Center

**An open source alternative to WHMCS, Blesta and Paymenter for hosting companies.**

Self-hosted · Laravel 12 · Filament · Livewire · MIT License

[Installation](#installation) · [Documentation](docs/) · [Plugin development](docs/plugins.md) · [Theme development](docs/themes.md) · [API](docs/api.md)

</div>

---

KelvCMC is a complete cloud management & billing platform designed for hosting providers. It runs on a classic **Plesk** shared-hosting stack (PHP 8.4+, MariaDB/MySQL, Composer) and includes everything you need to run a hosting business: storefront, billing with VAT and coupons, automated provisioning through Plesk/Pterodactyl/Proxmox, a support ticket system, payment gateways, a REST API and a modular plugin architecture.

## ✨ Features

| Area | Details |
| --- | --- |
| **Client portal** | Registration, secure login, **2FA** (TOTP), dashboard with charts, services, order history, PDF invoices, tickets, payment methods, account credit |
| **Admin panel** | Filament-based dark UI: users, products & plans, servers, orders, services, invoices, payments, tickets, coupons, roles & permissions, audit logs, settings, themes, plugins |
| **Billing** | Automatic renewal invoices, subscriptions, email reminders, **VAT**, promo codes, overdue/suspension workflow, PDF invoices (dompdf) |
| **Store** | Web hosting, VPS, Minecraft servers, FiveM servers, domains, licenses, custom services |
| **Payments** | Extensible gateway system: **Stripe, PayPal, Mollie, Coinbase Commerce, bank transfer, internal credit** |
| **Hosting integrations** | **Plesk** (clients, domains, MySQL DBs, SSL, suspend/delete), **Pterodactyl** (servers, nodes, allocations, suspension), **Proxmox** (VMs, CPU/RAM/disk, suspend), **Cloudflare & PowerDNS** for DNS |
| **Support** | Ticket categories, departments, priorities, private staff notes, attachments, history |
| **API** | Public REST API (Sanctum) with OpenAPI/Swagger spec — `GET /api/v1/users`, `GET /api/v1/products`, `POST /api/v1/orders`, `GET /api/v1/services`, invoices & more |
| **Security** | CSRF, rate limiting, RBAC roles/permissions, audit logs, encrypted sensitive data (credentials, 2FA secrets) |
| **Architecture** | Modular `app/Modules` + plugin system in `plugins/`, queue jobs with Redis support, Docker support optional |

## 🧰 Tech stack

- **Laravel 12** · PHP 8.4+ · MariaDB/MySQL
- **Filament 3** admin panel · **Livewire 3** · **Tailwind CSS** (Vite)
- **Redis** for queues/cache (falls back to database)
- Laravel **Queue**, scheduler (cron), Guzzle HTTP client
- DomPDF for invoice generation

## 🚀 Installation

### Requirements

- PHP **8.4+** with `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `zip`
- MariaDB 10.6+ / MySQL 8+
- Composer 2
- Node.js 20+ (only to build frontend assets)

### Quick start

```bash
git clone https://github.com/kelv/kelvcmc.git
cd kelvcmc
cp .env.example .env            # set DB credentials, APP_URL, mail, gateways...
composer install
npm install && npm run build
php artisan kelvcmc:install --force # interactive setup, migrations, settings and admin
php artisan queue:work          # in a separate terminal / supervisor
```

Then open `/admin` and sign in with the administrator credentials chosen during setup.

Want demo products and sample data?

```bash
php artisan db:seed --class "Database\\Seeders\\DemoDataSeeder"
```

Or use the one-shot installer:

```bash
./install.sh --demo
```

For a production database without demo records, use `php artisan kelvcmc:install --no-demo --force`.

> **Plesk users:** Change the document root to `public/` in Plesk → Hosting Settings, or use the included root `index.php` fallback. Full guide: [docs/installation-plesk.md](docs/installation-plesk.md) · Production hardening: [docs/production.md](docs/production.md)

### Scheduled tasks (production)

The Laravel scheduler drives billing automation (renewals at 00:30, reminders at 09:00, suspensions at 01:00). Add one cron entry:

```
* * * * * cd /path/to/kelvcmc && php artisan schedule:run >> /dev/null 2>&1
```

And run the queue worker under supervisor:

```
php artisan queue:work --tries=3 --timeout=120
```

## 🔌 Payment gateways

Enable gateways in `.env`, then they appear on the client checkout:

| Gateway | Env keys |
| --- | --- |
| Stripe | `GATEWAY_STRIPE_ENABLED`, `GATEWAY_STRIPE_SECRET_KEY`, `GATEWAY_STRIPE_WEBHOOK_SECRET` |
| PayPal | `GATEWAY_PAYPAL_ENABLED`, `GATEWAY_PAYPAL_CLIENT_ID`, `GATEWAY_PAYPAL_SECRET`, `GATEWAY_PAYPAL_SANDBOX` |
| Mollie | `GATEWAY_MOLLIE_ENABLED`, `GATEWAY_MOLLIE_API_KEY` |
| Coinbase Commerce | `GATEWAY_COINBASE_ENABLED`, `GATEWAY_COINBASE_API_KEY`, `GATEWAY_COINBASE_WEBHOOK_SECRET` |
| Bank transfer | `GATEWAY_BANKTRANSFER_ENABLED`, `GATEWAY_BANKTRANSFER_DETAILS` |
| Internal credit | `GATEWAY_CREDIT_ENABLED` |

Webhook endpoints: `POST /api/webhooks/{gateway}`.

## 🌐 Hosting integrations

Configure in `.env` — see [docs/installation-plesk.md](docs/installation-plesk.md) for Plesk and [docs/production.md](docs/production.md) for Pterodactyl/Proxmox/Cloudflare/PowerDNS details.

- **Plesk** (XML API): auto-creates Plesk clients, webspaces/domains, MySQL databases + users, Let's Encrypt SSL; suspends on non-payment; deletes on termination.
- **Pterodactyl** (Application API): creates game servers on a node with an allocation, memory/disk/CPU limits; suspends/unsuspends/deletes.
- **Proxmox** (PVE API): creates QEMU VMs with CPU/RAM/disk from the plan; start/stop on suspend; deletes on termination.
- **Cloudflare / PowerDNS**: zone and DNS record management (used by the Domain module).

Each product has a **module** (`plesk`, `pterodactyl`, `proxmox` or `manual`) selecting its provisioning driver. Products without an enabled integration fall back to *manual* provisioning.

## 📁 Project structure

```
app/
├── Console/Commands/        # artisan commands (kelvcmc:*)
├── Enums/                   # statuses & cycles
├── Filament/                # admin resources, pages, widgets
├── Http/Controllers/        # web, client & API controllers
├── Http/Middleware/         # role, permission, 2FA
├── Integrations/            # Plesk, Pterodactyl, Proxmox, DNS providers
├── Jobs/                    # provisioning, invoices, reminders
├── Livewire/                # (reserved for interactive components)
├── Mail/                    # generic mailable
├── Models/                  # Eloquent models
├── Modules/                 # modular extensions (Domain module included)
├── Payments/                # gateway contract, manager, 6 gateways
├── Providers/               # app, auth, modules, payments, integrations, Filament
├── Services/                # Billing, Payments, Provisioning, Orders, Tickets, ...
└── Support/                 # helpers, TOTP, audit logger
modules/  → (modules live in app/Modules)
plugins/  → drop-in plugins (HelloWorld example included)
resources/ → views (client portal, storefront, mail, PDF), css, js
routes/    → web, api, console
database/  → migrations, seeders
docs/      → full documentation
```

## 🧩 Modules & plugins

KelvCMC is modular:

- **Modules** (`app/Modules/{Name}/module.json`) are first-party feature bundles with their own controllers, views and Filament widgets. The bundled **Domain module** demonstrates availability checks + DNS management.
- **Plugins** (`plugins/{name}/plugin.json`) are third-party extensions — any directory with a `plugin.json` is auto-discovered. The **HelloWorld** plugin shows how to register routes, views, providers and Filament items.

Read the guides: [Plugin development](docs/plugins.md) · [Theme development](docs/themes.md).

## 🔐 Security

- Session + CSRF protection (Laravel defaults), login rate limiting, API throttling
- **2FA** with TOTP (Google Authenticator compatible), recovery codes
- **RBAC** — roles (`super-admin`, `admin`, `support`, `client`) with granular permissions, managed in the admin panel
- **Audit logs** of admin & auth actions (pruned after retention period)
- Sensitive data (server credentials, service passwords, gateway tokens, 2FA secrets) is **encrypted at rest** via Laravel's `encrypted` casts
- Admin panel requires authentication; `super-admin` gate bypass is restricted to that role

## 🧪 Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Super admin | chosen during install | chosen during install |
| Client | client@kelvcmc.local | password |

## 📄 License

[MIT](LICENSE) © 2026 KelvCMC Contributors

---

<div align="center">Built with ❤️ and Laravel · Not affiliated with WHMCS, Blesta, or Plesk</div>
