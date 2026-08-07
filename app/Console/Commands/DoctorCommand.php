<?php

namespace App\Console\Commands;

use App\Services\InstallationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DoctorCommand extends Command
{
    protected $signature = 'kelvcmc:doctor';

    protected $description = 'Diagnose the KelvCMC installation and report any issues.';

    /** @var array<int, array{string, string, bool}> */
    protected array $checks = [];

    public function handle(InstallationService $installer): int
    {
        $this->newLine();
        $this->components->info('KelvCMC Doctor');

        // ---- PHP & Extensions ----
        $this->header('PHP & Extensions');
        $this->check('PHP >= 8.4', fn () => version_compare(PHP_VERSION, '8.4.0', '>='), PHP_VERSION);

        foreach (InstallationService::REQUIRED_EXTENSIONS as $extension => $label) {
            $this->check(
                "Extension: {$label}",
                fn () => extension_loaded($extension),
                extension_loaded($extension) ? 'loaded' : 'missing',
            );
        }

        // ---- APP_KEY ----
        $this->header('Application Key');
        $this->check('APP_KEY', fn () => filled(config('app.key')), config('app.key') ? 'set' : 'missing');

        // ---- Environment ----
        $this->header('Environment');
        $this->check('.env file', fn () => is_file(base_path('.env')));
        $this->check('.env.example file', fn () => is_file(base_path('.env.example')));

        // Check that sensitive env keys are not empty
        $env = is_file(base_path('.env')) ? (string) file_get_contents(base_path('.env')) : '';
        foreach (['DB_DATABASE', 'DB_USERNAME', 'APP_URL'] as $envKey) {
            $this->check(
                ".env {$envKey}",
                fn () => filled(env($envKey)),
                filled(env($envKey)) ? 'set' : 'empty',
            );
        }

        // ---- Database ----
        $this->header('Database');
        try {
            app('db')->connection()->getPdo();
            $this->check('Database connection', fn () => true, 'connected');
        } catch (\Throwable $e) {
            $this->check('Database connection', fn () => false, $e->getMessage());
        }

        // ---- Filesystem ----
        $this->header('Filesystem Permissions');
        foreach ([storage_path(), base_path('bootstrap/cache'), base_path()] as $path) {
            $this->check(
                'Writable: '.str_replace(base_path(), '', $path) ?: '/',
                fn () => is_writable($path),
            );
        }

        // ---- Storage link ----
        $this->check(
            'Storage link',
            fn () => is_link(public_path('storage')) || is_dir(public_path('storage')),
            'exists',
        );

        // ---- installed.lock ----
        $this->check(
            'Installation lock',
            fn () => $installer->isInstalled(),
            $installer->isInstalled() ? 'installed' : 'not installed',
        );

        // ---- Cache ----
        $this->header('Cache');
        foreach (['file', 'database', 'redis'] as $store) {
            try {
                cache()->store($store)->put('kelvcmc:doctor:test', 'ok', 1);
                $value = cache()->store($store)->get('kelvcmc:doctor:test');
                $this->check("Cache store: {$store}", fn () => $value === 'ok');
                cache()->store($store)->forget('kelvcmc:doctor:test');
            } catch (\Throwable) {
                $this->check("Cache store: {$store}", fn () => false, 'unavailable');
            }
        }

        // ---- Queue ----
        $this->header('Queue');
        try {
            $pending = app('db')->table('jobs')->count();
            $failed = app('db')->table('failed_jobs')->count();
            $this->check('Queue connection', fn () => true, "{$pending} pending, {$failed} failed");
        } catch (\Throwable) {
            $this->check('Queue connection', fn () => true, 'jobs table unavailable (migrate first)');
        }

        // ---- Filament ----
        $this->header('Filament');
        $this->check('Filament package', fn () => class_exists(\Filament\FilamentManager::class), 'present');
        $this->check('Admin panel provider', fn () => class_exists(\App\Providers\Filament\AdminPanelProvider::class), 'registered');
        $this->check('Filament assets published', fn () => is_dir(public_path('vendor/filament')), 'published');

        // ---- Modules & Plugins ----
        $this->header('Modules & Plugins');
        $moduleCount = count(File::directories(app_path('Modules')));
        $pluginCount = 0;
        if (is_dir(base_path('plugins'))) {
            foreach (File::directories(base_path('plugins')) as $dir) {
                if (is_file($dir.'/plugin.json')) {
                    $pluginCount++;
                }
            }
            if (is_file(base_path('plugins/plugin.json'))) {
                $pluginCount++;
            }
        }
        $this->check('Modules', fn () => true, "{$moduleCount} discovered");
        $this->check('Plugins', fn () => true, "{$pluginCount} discovered");

        // ---- Configuration ----
        $this->header('Configuration');
        $this->check('App env', fn () => true, (string) config('app.env'));
        $this->check('Debug mode', fn () => true, config('app.debug') ? 'ON (change in production!)' : 'OFF');
        $this->check('App URL', fn () => true, (string) config('app.url'));
        $this->check('Default currency', fn () => true, (string) config('kelvcmc.billing.currency', 'EUR'));

        // ---- Build tools (optional) ----
        $this->header('Build Tools (optional)');
        $this->check('Composer', fn () => $installer->binaryAvailable('composer'));
        $this->check('Node.js', fn () => $installer->binaryAvailable('node'));
        $this->check('npm', fn () => $installer->binaryAvailable('npm'));
        $this->check('Vite build', fn () => is_file(public_path('build/manifest.json')), 'present');

        // ---- Summary ----
        $this->newLine();
        $passed = collect($this->checks)->filter(fn ($c) => $c[2])->count();
        $total = count($this->checks);
        $icon = $passed === $total ? '✅' : '⚠️';

        $this->components->twoColumnDetail(
            "{$icon} {$passed} / {$total} checks passed",
            $passed === $total ? '<info>All good!</info>' : '<warning>Review issues above</warning>',
        );

        if ($passed !== $total) {
            $this->newLine();
            $this->line('  <fg=yellow>Run <options=bold>php artisan kelvcmc:install --force</> to repair the installation.</>');
            $this->line('  <fg=yellow>Ensure <options=bold>storage/</>, <options=bold>bootstrap/cache/</> are writable by the web user.</>');
            $this->line('  <fg=yellow>On Plesk: <options=bold>chown -R systeemgebruiker:psacln storage bootstrap/cache</></>');
        }

        return $passed === $total ? self::SUCCESS : self::FAILURE;
    }

    protected function header(string $label): void
    {
        $this->newLine();
        $this->components->twoColumnDetail("  <fg=gray;options=bold>{$label}</>");
    }

    protected function check(string $label, callable $test, string $detail = ''): void
    {
        try {
            $result = $test();
        } catch (\Throwable $e) {
            $result = false;
            $detail = $e->getMessage();
        }

        $icon = $result ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $detail = $detail ?: ($result ? 'OK' : 'FAIL');
        $this->checks[] = [$label, $detail, (bool) $result];
        $this->components->twoColumnDetail("  {$icon} {$label}", $detail);
    }
}
