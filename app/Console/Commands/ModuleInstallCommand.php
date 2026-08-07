<?php

namespace App\Console\Commands;

use App\Modules\ModuleManager;
use Illuminate\Console\Command;

class ModuleInstallCommand extends Command
{
    protected $signature = 'kelvcmc:module:install {id : Module identifier}';

    protected $description = 'Run a module migrations and enable the module.';

    public function handle(ModuleManager $manager): int
    {
        if (! $manager->install(strtolower($this->argument('id')))) {
            $this->error('Module not found.');

            return self::FAILURE;
        }

        $this->info('Module installed and enabled.');

        return self::SUCCESS;
    }
}
