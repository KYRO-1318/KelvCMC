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

    public function requirementsPass(bool $includeBuildTools = false): bool
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
        Artisan::call('migrate', ['--force' => true]);
    }

    public function seedBase(): void
    {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder', '--force' => true]);
    }

    public function seedDemo(): void
    {
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--force' => true]);
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

    public function lock(): void
    {
        $directory = dirname(storage_path('installed.lock'));

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(storage_path('installed.lock'), now()->toIso8601String().PHP_EOL, LOCK_EX);
    }

    public function output(string $message): string
    {
        return trim(Artisan::output()) === '' ? $message : Artisan::output();
    }

    protected function binaryAvailable(string $binary): bool
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
}
