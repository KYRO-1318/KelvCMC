# Plugins

Drop a plugin here and it is discovered automatically by KelvCMC.

A plugin is a directory containing a `plugin.json` manifest:

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

- The `namespace` must match the `Plugins\\` PSR-4 prefix (already mapped to `plugins/` in `composer.json`).
- Providers are registered on boot. Use them to register routes, Filament resources/pages, Livewire components, payment gateways, and integration providers.
- A plugin can contribute **payment gateways** by adding to `config/payments.php`, or **hosting providers** via the `IntegrationManager`.

See `docs/plugins.md` for the complete developer guide. The bundled `HelloWorld` plugin is a working example.
