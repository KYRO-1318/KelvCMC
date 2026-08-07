<?php

namespace App\Modules;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ModuleManager
{
    /** @var array<string, Module> keyed by enabled module id */
    protected array $modules = [];

    /** @var array<string, Module> keyed by all discovered module id */
    protected array $availableModules = [];

    /** @var array<string, array<string, mixed>> */
    protected array $moduleManifests = [];

    /** @var array<string, array<string, mixed>> */
    protected array $plugins = [];

    /** @var array<string, array<string, mixed>> */
    protected array $availablePlugins = [];

    protected bool $discovered = false;

    /**
     * Discover modules (app/Modules) and plugins (config('modules.paths')).
     */
    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        // --- Modules ---
        $modulesDir = app_path('Modules');

        foreach (File::directories($modulesDir) as $directory) {
            $manifestPath = $directory.'/module.json';

            if (! File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(File::get($manifestPath), true) ?: [];

            $id = strtolower((string) ($manifest['id'] ?? basename($directory)));
            $this->moduleManifests[$id] = $manifest;

            $class = $manifest['class'] ?? null;
            $module = null;

            if ($class && class_exists($class)) {
                $module = app($class);
                $this->availableModules[$id] = $module;
            }

            if (! $this->shouldLoad($id)) {
                continue;
            }

            if ($module) {
                $this->modules[$id] = $module;
            }

            if (! empty($manifest['views']) && File::isDirectory($manifest['views'])) {
                View::addNamespace('module-'.$id, $manifest['views']);
            }

            if (! empty($manifest['routes'])) {
                $this->loadRoutesFile($manifest['routes']);
            }

            $module = $this->modules[$id] ?? null;
            $provider = $manifest['service_provider'] ?? $module?->serviceProvider();
            if ($provider && class_exists($provider)) {
                app()->register($provider);
            }
            if ($module?->routesPath() && empty($manifest['routes'])) {
                $this->loadRoutesFile($module->routesPath());
            }
        }

        // --- Plugins ---
        foreach (config('modules.paths', []) as $basePath) {
            foreach ($this->scanPluginManifests($basePath) as $manifest) {
                $id = strtolower((string) ($manifest['name'] ?? basename(dirname($manifest['path']))));
                $this->availablePlugins[$id] = $manifest;

                if (! $this->shouldLoad($id)) {
                    continue;
                }

                $this->plugins[$id] = $manifest;

                foreach ($manifest['providers'] ?? [] as $provider) {
                    if (class_exists($provider)) {
                        app()->register($provider);
                    }
                }

                if (! empty($manifest['views']) && File::isDirectory($manifest['views'])) {
                    View::addNamespace('plugin-'.$id, $manifest['views']);
                }

                if (! empty($manifest['routes'])) {
                    $this->loadRoutesFile($manifest['routes']);
                }
            }
        }
    }

    public function boot(): void
    {
        $this->discover();

        foreach ($this->modules as $module) {
            $module->boot();
        }
    }

    /** @return array<string, Module> */
    public function modules(): array
    {
        $this->discover();

        return $this->modules;
    }

    /** @return array<string, Module> */
    public function availableModules(): array
    {
        $this->discover();

        return $this->availableModules;
    }

    /** @return array<string, array<string, mixed>> */
    public function plugins(): array
    {
        $this->discover();

        return $this->plugins;
    }

    /** @return array<string, array<string, mixed>> */
    public function availablePlugins(): array
    {
        $this->discover();

        return $this->availablePlugins;
    }

    public function module(string $id): ?Module
    {
        return $this->availableModules()[$id] ?? null;
    }

    public function isEnabled(string $id): bool
    {
        $configured = config('modules.modules', []);
        $default = $configured === [] || $configured === null
            ? true
            : (bool) ($configured[$id] ?? $configured[ucfirst($id)] ?? true);

        if (! file_exists(storage_path('installed.lock'))) {
            return $default;
        }

        try {
            return (bool) Setting::get("modules.enabled.{$id}", $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    public function enable(string $id): void
    {
        Setting::set("modules.enabled.{$id}", true, 'modules');
    }

    public function disable(string $id): void
    {
        Setting::set("modules.enabled.{$id}", false, 'modules');
    }

    public function install(string $id): bool
    {
        $module = $this->module($id);
        if (! $module) {
            return false;
        }

        $path = $this->moduleManifests[$id]['migrations'] ?? $module->migrationsPath();
        if ($path && is_dir(base_path($path))) {
            Artisan::call('migrate', ['--path' => $path, '--force' => true]);
        }

        $this->enable($id);

        return true;
    }

    public function uninstall(string $id): bool
    {
        if (! $this->module($id)) {
            return false;
        }

        $this->disable($id);

        return true;
    }

    public function navItems(): array
    {
        return collect($this->modules())
            ->flatMap(fn (Module $module) => $module->navItems())
            ->values()
            ->all();
    }

    public function filamentWidgets(): array
    {
        return collect($this->modules())
            ->flatMap(fn (Module $module) => $module->filamentWidgets())
            ->values()
            ->all();
    }

    public function filamentResources(): array
    {
        return collect($this->modules())
            ->flatMap(fn (Module $module) => $module->filamentResources())
            ->values()
            ->all();
    }

    protected function shouldLoad(string $id): bool
    {
        // Settings are stored in the database, which may not exist during the
        // first installer request. Fall back to config until installation ends.
        $configured = config('modules.modules', []);
        $default = $configured === [] || $configured === null
            ? true
            : (bool) ($configured[$id] ?? $configured[ucfirst($id)] ?? true);

        // Settings are unavailable before the schema exists. Configuration is
        // the safe default for the first installer request.
        if (! file_exists(storage_path('installed.lock'))) {
            return $default;
        }

        try {
            return (bool) Setting::get("modules.enabled.{$id}", $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    /** @return array<int, array<string, mixed>> */
    protected function scanPluginManifests(string $basePath): array
    {
        $manifests = [];

        if (! File::isDirectory($basePath)) {
            return $manifests;
        }

        $rootManifest = $basePath.'/plugin.json';

        if (File::exists($rootManifest)) {
            $manifests[] = $this->loadPluginManifest($rootManifest);
        }

        foreach (File::directories($basePath) as $directory) {
            $manifestPath = $directory.'/plugin.json';

            if (File::exists($manifestPath)) {
                $manifests[] = $this->loadPluginManifest($manifestPath);
            }
        }

        return array_filter($manifests);
    }

    /** @return array<string, mixed>|null */
    protected function loadPluginManifest(string $path): ?array
    {
        $manifest = json_decode(File::get($path), true);

        if (! $manifest) {
            return null;
        }

        $manifest['path'] = $path;

        return $manifest;
    }

    protected function loadRoutesFile(string $routes): void
    {
        $file = base_path($routes);

        if (File::exists($file)) {
            require $file;
        }
    }
}
