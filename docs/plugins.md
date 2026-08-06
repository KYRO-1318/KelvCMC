# KelvCMC developer guide — modules & plugins

KelvCMC has two extension mechanisms:

1. **Modules** — first-party feature bundles living in `app/Modules/{Name}` with a `module.json` manifest. Enabled/disabled from the admin panel.
2. **Plugins** — third-party extensions living in `plugins/{Name}` with a `plugin.json` manifest, discovered automatically. This is what you install from other developers.

Both can register routes, views, Filament widgets/resources, payment gateways and integration providers.

---

## Modules

### Anatomy of a module

```
app/Modules/Domain/                     # example module shipped with KelvCMC
├── module.json                         # manifest (required)
├── DomainModule.php                    # Module subclass (required)
├── Http/Controllers/…                  # your controllers
├── Filament/…                          # optional Filament widgets/resources
└── resources/views/…                   # views (namespaced module-domain::…)
```

`module.json`:

```json
{
    "id": "domain",
    "name": "Domain Tools",
    "version": "1.0.0",
    "class": "App\\Modules\\Domain\\DomainModule",
    "description": "What this module does.",
    "views": "app/Modules/Domain/resources/views"
}
```

The module class:

```php
namespace App\Modules\Domain;

use App\Modules\Module;

class DomainModule extends Module
{
    public function name(): string { return 'Domain Tools'; }
    public function description(): string { return '…'; }

    public function boot(): void
    {
        // Register routes, events, commands...
        $this->app['router']->middleware(['web', 'auth'])
            ->prefix('domains')
            ->group(function ($router) {
                $router->get('/', [Http\Controllers\DomainController::class, 'index'])
                    ->name('modules.domain.index');
            });
    }

    /** Extra sidebar links for the client portal. */
    public function navItems(): array
    {
        return [['label' => 'Domains', 'route' => 'modules.domain.index', 'icon' => 'globe']];
    }

    /** Filament widgets contributed to the admin dashboard. */
    public function filamentWidgets(): array { return []; }

    /** Filament resources contributed to the admin panel. */
    public function filamentResources(): array { return []; }
}
```

Views are available under the `module-domain::` namespace (from the `views` key of the manifest).

---

## Plugins

### Anatomy of a plugin

```
plugins/HelloWorld/                     # example plugin shipped with KelvCMC
├── plugin.json                         # manifest (required)
├── HelloWorldServiceProvider.php       # providers listed in the manifest
└── resources/views/…                   # views (namespaced plugin-hello-world::…)
```

`plugin.json`:

```json
{
    "name": "my-plugin",
    "version": "1.0.0",
    "namespace": "Plugins\\MyPlugin",
    "providers": ["Plugins\\MyPlugin\\MyPluginServiceProvider"],
    "description": "What this plugin does.",
    "views": "plugins/my-plugin/resources/views",
    "routes": "plugins/my-plugin/routes/web.php"
}
```

- The `Plugins\\` PSR-4 namespace is already mapped to `plugins/` in `composer.json` — no extra setup needed.
- Providers are registered automatically when the plugin is enabled (admin panel → System → Plugins).
- The manifest `routes` file is loaded at discovery time.

```php
<?php

namespace Plugins\MyPlugin;

use Illuminate\Support\ServiceProvider;

class MyPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register routes:
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        // Contribute a client portal nav item:
        // (use the App\Modules\Module contract methods, or Filament::serving hooks)
    }
}
```

---

## Extending core systems

### 1. Payment gateways

Implement `App\Payments\Contracts\PaymentGateway`:

```php
namespace Plugins\MyPlugin\Gateways;

use App\Payments\Contracts\PaymentGateway;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class MyGateway implements PaymentGateway
{
    public function id(): string { return 'mygateway'; }
    public function name(): string { return 'My Gateway'; }
    public function isEnabled(): bool { return (bool) config('services.mygateway.enabled'); }
    public function supportsRecurring(): bool { return true; }
    public function supportsRefunds(): bool { return false; }

    public function createPayment(Payment $payment, Invoice $invoice, ?PaymentMethod $method = null): array
    {
        // call your provider's API...
        return [
            'transaction_id' => 'remote-id',
            'redirect_url' => 'https://provider/pay/...',
            'metadata' => ['anything' => 'stored'],
        ];
    }

    public function verify(Payment $payment): bool { /* check status */ }
    public function refund(Payment $payment): bool { return false; }
    public function handleWebhook(Request $request): mixed { /* idempotent! */ }
}
```

Register it in your provider:

```php
public function boot(): void
{
    $this->app->make(PaymentGatewayManager::class)->register('mygateway', MyGateway::class);
    $this->app->make(IntegrationManager::class)->register('my-integration', MyProvider::class);
}
```

> For fully configured gateways, add them to `config/payments.php` instead.

### 2. Hosting integrations

Implement `App\Integrations\Contracts\HostingProvider` (`provision`, `suspend`, `unsuspend`, `terminate`) and register it:

```php
app(IntegrationManager::class)->register('my-module', MyProvider::class);
```

Then create products whose `module` equals `my-module`. KelvCMC will route provisioning automatically.

### 3. DNS providers

Implement `App\Integrations\Contracts\DnsProvider` and swap it in the `DnsManager` (see `app/Integrations/Dns/DnsManager.php`).

### 4. Client portal pages

Views can use the `<x-client-layout>` component to inherit the dark SaaS design:

```blade
<x-client-layout title="My page">
    <div class="card">…</div>
</x-client-layout>
```

### 5. Themes

See [themes.md](themes.md) — a plugin can also register its own theme by adding an entry to `config/themes.php` and shipping a CSS file.

---

## Best practices

- Keep plugin code **namespaced** under `Plugins\…` and never modify core files.
- Use KelvCMC services (`BillingService`, `ProvisioningService`, `PaymentService`, `OrderService`) instead of duplicating logic.
- Webhooks must be **idempotent** (guard on payment status).
- Respect the audit trail: call `AuditLogger::record(...)` for admin-visible actions.
- Lock provisioning behind the queue (`ProvisionServiceJob`) for long operations.
- Every module/plugin ships a `README.md` and version.

Next: [Themes](themes.md) · [API](api.md)
