<?php

namespace App\Console\Commands;

use App\Services\InstallationService;
use Illuminate\Console\Command;
use RuntimeException;

class InstallCommand extends Command
{
    protected $signature = 'kelvcmc:install
                            {--demo : Seed demo catalog and sample customer data}
                            {--no-demo : Do not seed demo catalog and sample customer data}
                            {--force : Allow installation in production}
                            {--name= : Site name}
                            {--url= : Public site URL}
                            {--currency= : Billing currency (ISO 4217)}
                            {--locale= : Default locale}
                            {--admin-name= : First administrator name}
                            {--admin-email= : First administrator email}
                            {--admin-password= : First administrator password}
                            {--db-connection= : Database driver}
                            {--db-host= : Database host}
                            {--db-port= : Database port}
                            {--db-database= : Database name}
                            {--db-username= : Database username}
                            {--db-password= : Database password}';

    protected $description = 'Install KelvCMC, configure the environment, database and first administrator.';

    public function handle(InstallationService $installer): int
    {
        if ($installer->isInstalled()) {
            $this->warn('KelvCMC is already installed (storage/installed.lock exists). Remove it only after a verified backup if you need to repair the installation.');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Production installation requires --force.');

            return self::FAILURE;
        }

        $this->components->info('KelvCMC installation');
        try {
            $this->checkRequirements($installer);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $dbConnection = $this->option('db-connection') ?: $this->choice('Database driver', ['mysql', 'mariadb'], 0);
        $dbHost = $this->option('db-host') ?: $this->ask('Database host', env('DB_HOST', '127.0.0.1'));
        $dbPort = (int) ($this->option('db-port') ?: $this->ask('Database port', env('DB_PORT', '3306')));
        $dbDatabase = $this->option('db-database') ?: $this->ask('Database name', env('DB_DATABASE', 'kelvcmc'));
        $dbUsername = $this->option('db-username') ?: $this->ask('Database username', env('DB_USERNAME', 'kelvcmc'));
        $dbPassword = $this->option('db-password') ?? $this->secret('Database password', env('DB_PASSWORD', ''));

        $siteName = $this->option('name') ?: $this->ask('Site name', config('kelvcmc.brand.name', 'KelvCMC'));
        $siteUrl = $this->option('url') ?: $this->ask('Site URL', env('APP_URL', 'http://localhost'));
        if (! filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            $this->error('Site URL must be a valid URL.');
            return self::FAILURE;
        }
        $currency = strtoupper($this->option('currency') ?: $this->ask('Billing currency', config('kelvcmc.billing.currency', 'EUR')));
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            $this->error('Currency must be a 3-letter ISO code.');
            return self::FAILURE;
        }
        $locale = $this->option('locale') ?: $this->ask('Default language', config('app.locale', 'en'));
        $adminName = $this->option('admin-name') ?: $this->ask('Administrator name', 'KelvCMC Admin');
        $adminEmail = $this->option('admin-email') ?: $this->ask('Administrator email', 'admin@kelvcmc.local');
        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Administrator email is invalid.');
            return self::FAILURE;
        }
        $adminPassword = $this->option('admin-password') ?: $this->secret('Administrator password (min. 12 characters)');

        if (! is_string($adminPassword) || strlen($adminPassword) < 12) {
            $this->error('The administrator password must contain at least 12 characters.');

            return self::FAILURE;
        }

        try {
            $installer->assertWritable();
            $installer->configureEnvironment([
                'DB_CONNECTION' => $dbConnection,
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $dbDatabase,
                'DB_USERNAME' => $dbUsername,
                'DB_PASSWORD' => $dbPassword,
                'APP_NAME' => $siteName,
                'APP_URL' => $siteUrl,
                'APP_LOCALE' => $locale,
                'KELVCMC_BRAND_NAME' => $siteName,
                'KELVCMC_CURRENCY' => $currency,
            ]);
            $installer->generateKey();
            $installer->testDatabaseConnection();
            $this->components->task('Migrating database', fn () => $installer->migrate());
            $this->components->task('Seeding roles, permissions and defaults', fn () => $installer->seedBase());
            $this->components->task('Creating administrator', fn () => $installer->createAdmin($adminName, $adminEmail, $adminPassword));
            $this->components->task('Saving installation settings', fn () => $installer->saveSettings($siteName, $siteUrl, $currency, $locale));
            $this->components->task('Creating storage link', fn () => $installer->createStorageLink());

            if (! $this->option('no-demo')) {
                $this->components->task('Seeding demo data', fn () => $installer->seedDemo());
            }

            $installer->lock();
        } catch (RuntimeException $exception) {
            $this->newLine();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('KelvCMC is installed.');
        $this->line('Admin panel:   '.rtrim($siteUrl, '/').'/admin');
        $this->line('Client portal: '.rtrim($siteUrl, '/').'/dashboard');

        return self::SUCCESS;
    }

    protected function checkRequirements(InstallationService $installer): void
    {
        $failed = [];

        foreach ($installer->serverRequirements() as $name => $passed) {
            $this->line(($passed ? '<fg=green>✓</>' : '<fg=red>✗</>').' '.$name);

            if (! $passed) {
                $failed[] = $name;
            }
        }

        if ($failed !== []) {
            throw new RuntimeException('Missing requirements: '.implode(', ', $failed));
        }
    }
}
