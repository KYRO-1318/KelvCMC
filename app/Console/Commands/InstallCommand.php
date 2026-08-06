<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'kelvcmc:install {--demo : Seed demo data}
                            {--force : Run in production without confirmation}';

    protected $description = 'Install KelvCMC: run migrations, create the admin account and seed demo data.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Running in production: pass --force to continue.');

            return self::FAILURE;
        }

        $this->info('Installing KelvCMC...');

        if (! file_exists(base_path('.env'))) {
            $this->info('Creating .env from .env.example');
            copy(base_path('.env.example'), base_path('.env'));
        }

        if (! config('app.key')) {
            $this->info('Generating application key...');
            Artisan::call('key:generate');
        }

        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true], $this->getOutput());

        $this->info('Seeding base data (roles & permissions)...');
        Artisan::call('db:seed', ['--force' => true], $this->getOutput());

        if ($this->option('demo')) {
            $this->info('Seeding demo data...');
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--force' => true], $this->getOutput());
        }

        $this->info('Creating storage link...');
        Artisan::call('storage:link');

        $this->newLine();
        $this->info('✅ KelvCMC installed.');
        $this->info('Admin panel:    '.rtrim(config('app.url'), '/').'/admin');
        $this->info('Client portal:  '.rtrim(config('app.url'), '/'));
        $this->info('Default admin:  admin@kelvcmc.local / password (change it immediately!)');

        return self::SUCCESS;
    }
}
