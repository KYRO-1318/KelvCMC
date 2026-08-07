<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InstallationService
{
    /** @var array<string, string> */
    public const REQUIRED_EXTENSIONS = [
        'pdo' => 'PDO',
        'pdo_mysql' => 'PDO MySQL',
        'mbstring' => 'Mbstring',
        'openssl' => 'OpenSSL',
        'tokenizer' => 'Tokenizer',
        'xml' => 'XML',
        'curl' => 'cURL',
        'zip' => 'ZIP',
        'gd' => 'GD',
        'bcmath' => 'BCMath',
    ];

    public function isInstalled(): bool
    {
        return is_file(storage_path('installed.lock'));
    }

    public function assertWritable(): void
    {
        foreach ([base_path(), storage_path(), base_path('bootstrap/cache')] as $path) {
            if (! is_writable($path)) {
                throw new RuntimeException("Directory is not writable: {$path}");
            }
        }
    }

    /** @return array<string, bool|string> */
    public function serverRequirements(): array
    {
        $requirements = [
            'PHP >= 8.4' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'Composer' => $this->binaryAvailable('composer'),
            'Node.js' => $this->binaryAvailable('node'),
            'npm' => $this->binaryAvailable('npm'),
        ];

        foreach (self::REQUIRED_EXTENSIONS as $extension => $label) {
            $requirements["PHP extension: {$label}"] = extension_loaded($extension);
        }

        return $requirements;
    }

    public function requirementsPass(bool $includeBuildTools = true): bool
    {
        foreach ($this->serverRequirements() as $name => $passed) {
            if (! $passed && ($includeBuildTools || ! in_array($name, ['Composer', 'Node.js', 'npm'], true))) {
                return false;
            }
        }

        return true;
    }

    public function configureEnvironment(array $values): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            if (! is_file(base_path('.env.example'))) {
                throw new RuntimeException('.env.example is missing.');
            }

            copy(base_path('.env.example'), $envPath);
        }

        foreach ($values as $key => $value) {
            $this->setEnvironmentValue($envPath, $key, (string) $value);
        }

        // Refresh the current request/command without requiring a restart.
        config([
            'app.name' => $values['APP_NAME'] ?? config('app.name'),
            'app.url' => $values['APP_URL'] ?? config('app.url'),
            'app.locale' => $values['APP_LOCALE'] ?? config('app.locale'),
            'kelvcmc.brand.name' => $values['KELVCMC_BRAND_NAME'] ?? config('kelvcmc.brand.name'),
            'kelvcmc.billing.currency' => $values['KELVCMC_CURRENCY'] ?? config('kelvcmc.billing.currency'),
            'database.default' => $values['DB_CONNECTION'] ?? config('database.default'),
        ]);

        if (isset($values['DB_HOST'])) {
            $connection = config('database.default');
            config(["database.connections.{$connection}.host" => $values['DB_HOST']]);
            config(["database.connections.{$connection}.port" => $values['DB_PORT'] ?? 3306]);
            config(["database.connections.{$connection}.database" => $values['DB_DATABASE'] ?? 'kelvcmc']);
            config(["database.connections.{$connection}.username" => $values['DB_USERNAME'] ?? 'root']);
            config(["database.connections.{$connection}.password" => $values['DB_PASSWORD'] ?? '']);
        }
    }

    public function generateKey(): void
    {
        if (filled(config('app.key'))) {
            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
        $environment = (string) @file_get_contents(base_path('.env'));
        if (preg_match('/^APP_KEY=(.*)$/m', $environment, $matches)) {
            config(['app.key' => trim($matches[1], "\\\"' ")]);
        }
    }

    public function testDatabaseConnection(): void
    {
        try {
            app('db')->connection()->getPdo();
        } catch (\Throwable $exception) {
            throw new RuntimeException('Database connection failed: '.$exception->getMessage(), 0, $exception);
        }
    }

    public function migrate(): void
    {
        if (Artisan::call('migrate', ['--force' => true]) !== 0) {
            throw new RuntimeException('Database migration failed. Check the Laravel log for details.');
        }
    }

    public function seedBase(): void
    {
        if (Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder', '--force' => true]) !== 0
            || Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder', '--force' => true]) !== 0) {
            throw new RuntimeException('Base data seeding failed. Check the Laravel log for details.');
        }
    }

    public function seedDemo(): void
    {
        if (Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--force' => true]) !== 0) {
            throw new RuntimeException('Demo data seeding failed. Check the Laravel log for details.');
        }
    }

    public function createAdmin(string $name, string $email, string $password): User
    {
        $admin = User::updateOrCreate(
            ['email' => strtolower($email)],
            ['name' => $name, 'password' => Hash::make($password), 'is_active' => true]
        );

        $admin->assignRole('super-admin');

        return $admin;
    }

    public function saveSettings(string $siteName, string $siteUrl, string $currency, string $locale): void
    {
        Setting::set('brand.name', $siteName);
        Setting::set('brand.url', $siteUrl);
        Setting::set('billing.currency', strtoupper($currency), 'billing');
        Setting::set('locale.default', $locale, 'general');
    }

    public function createStorageLink(): void
    {
        if (Artisan::call('storage:link', ['--force' => true]) !== 0) {
            throw new RuntimeException('Unable to create the public storage link.');
        }
    }

    public function lock(): void
    {
        $directory = dirname(storage_path('installed.lock'));

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        // Restore the drivers temporarily replaced by the fresh web bootstrap.
        if (is_file(base_path('.env'))) {
            $environment = (string) file_get_contents(base_path('.env'));
            if (preg_match('/^KELVCMC_INSTALLER_ORIGINAL_SESSION_DRIVER=(.*)$/m', $environment, $match)) {
                $this->setEnvironmentValue(base_path('.env'), 'SESSION_DRIVER', $match[1]);
            }
            if (preg_match('/^KELVCMC_INSTALLER_ORIGINAL_CACHE_STORE=(.*)$/m', $environment, $match)) {
                $this->setEnvironmentValue(base_path('.env'), 'CACHE_STORE', $match[1]);
            }
            $this->removeEnvironmentValue(base_path('.env'), 'KELVCMC_INSTALLER_ORIGINAL_SESSION_DRIVER');
            $this->removeEnvironmentValue(base_path('.env'), 'KELVCMC_INSTALLER_ORIGINAL_CACHE_STORE');
        }
        file_put_contents(storage_path('installed.lock'), now()->toIso8601String().PHP_EOL, LOCK_EX);
    }

    public function output(string $message): string
    {
        return trim(Artisan::output()) === '' ? $message : Artisan::output();
    }

    public function binaryAvailable(string $binary): bool
    {
        $command = PHP_OS_FAMILY === 'Windows' ? "where {$binary}" : "command -v {$binary}";
        $redirect = PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null';
        $output = @shell_exec($command.$redirect);

        return is_string($output) && trim($output) !== '';
    }

    protected function setEnvironmentValue(string $path, string $key, string $value): void
    {
        $contents = is_file($path) ? file_get_contents($path) : '';
        $contents = is_string($contents) ? $contents : '';
        $encoded = preg_match('/\s|#|=/', $value) ? '"'.str_replace('"', '\\"', $value).'"' : $value;
        $line = $key.'='.$encoded;
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            $contents = (string) preg_replace($pattern, $line, $contents);
        } else {
            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $contents, LOCK_EX);
    }

    protected function removeEnvironmentValue(string $path, string $key): void
    {
        $contents = (string) @file_get_contents($path);
        $contents = preg_replace('/^'.preg_quote($key, '/').'=.*(?:\\R|$)/m', '', $contents);
        file_put_contents($path, (string) $contents, LOCK_EX);
    }
}
