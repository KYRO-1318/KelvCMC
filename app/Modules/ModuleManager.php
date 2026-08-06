<?php

namespace App\Modules;

use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ModuleManager
{
    /** @var array<string, Module> keyed by module id */
    protected array $modules = [];

    /** @var array<string, array<string, mixed>> */
    protected array $plugins = [];

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

            if (! $this->isEnabled($id)) {
                continue;
            }

            $class = $manifest['class'] ?? null;

            if ($class && class_exists($class)) {
                $this->modules[$id] = app($class);
            }

            if (! empty($manifest['views']) && File::isDirectory($manifest['views'])) {
                View::addNamespace('module-'.$id, $manifest['views']);
            }

            if (! empty($manifest['routes'])) {
                $this->loadRoutesFile($manifest['routes']);
            }
        }

        // --- Plugins ---
        foreach (config('modules.paths', []) as $basePath) {
            foreach ($this->scanPluginManifests($basePath) as $manifest) {
                $id = strtolower((string) ($manifest['name'] ?? basename(dirname($manifest['path']))));

                if (! $this->isEnabled($id)) {
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

    /** @return array<string, array<string, mixed>> */
    public function plugins(): array
    {
        $this->discover();

        return $this->plugins;
    }

    public function module(string $id): ?Module
    {
        return $this->modules()[$id] ?? null;
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

    protected function isEnabled(string $id): bool
    {
        $configured = config('modules.modules', []);

        if ($configured === [] || $configured === null) {
            $default = true;
        } else {
            $default = (bool) ($configured[$id] ?? $configured[ucfirst($id)] ?? true);
        }

        return (bool) Setting::get("modules.enabled.{$id}", $default);
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
