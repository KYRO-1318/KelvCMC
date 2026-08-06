<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class AuditPruneCommand extends Command
{
    protected $signature = 'kelvcmc:audit:prune';

    protected $description = 'Delete audit logs older than the configured retention period.';

    public function handle(): int
    {
        $days = (int) config('kelvcmc.security.audit_retention_days', 365);

        if ($days <= 0) {
            $this->info('Retention disabled; nothing pruned.');

            return self::SUCCESS;
        }

        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned {$deleted} audit logs.");

        return self::SUCCESS;
    }
}
