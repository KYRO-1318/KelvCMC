<?php

namespace App\Console\Commands;

use App\Modules\ModuleManager;
use Illuminate\Console\Command;

class ModulesListCommand extends Command
{
    protected $signature = 'kelvcmc:modules:list';

    protected $description = 'List discovered modules and plugins.';

    public function handle(ModuleManager $manager): int
    {
        $this->newLine();
        $this->info('Modules (app/Modules):');

        foreach ($manager->modules() as $id => $module) {
            $this->line(sprintf('  <info>✔</info> %-24s %s', $id, $module->description()));
        }

        $this->newLine();
        $this->info('Plugins ('.implode(', ', config('modules.paths', [])).'):');

        foreach ($manager->plugins() as $id => $plugin) {
            $this->line(sprintf('  <info>✔</info> %-24s %s', $id, $plugin['description'] ?? ''));
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
