<?php

namespace App\Console\Commands;

use App\Shared\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GdprRetentionPruneCommand extends Command
{
    protected $signature = 'intranet:gdpr-prune {--dry-run : Report only}';

    protected $description = 'Prune retained personal/operational data per config/gdpr.php';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $days = config('gdpr.retention_days');

        $counts = [];

        $auditBefore = now()->subDays((int) $days['audit_logs']);
        $q = AuditLog::query()->where('created_at', '<', $auditBefore);
        $counts['audit_logs'] = $q->count();
        if (! $dry) {
            $q->delete();
        }

        if (Schema::hasTable('search_zero_results')) {
            $before = now()->subDays((int) $days['search_zero_results']);
            $zq = DB::table('search_zero_results')->where('created_at', '<', $before);
            $counts['search_zero_results'] = $zq->count();
            if (! $dry) {
                $zq->delete();
            }
        }

        if (Schema::hasTable('sso_jtis')) {
            $before = now()->subDays((int) $days['sso_jtis']);
            $sq = DB::table('sso_jtis')->where('expires_at', '<', $before);
            $counts['sso_jtis'] = $sq->count();
            if (! $dry) {
                $sq->delete();
            }
        }

        if (Schema::hasTable('sessions')) {
            $before = now()->subDays((int) $days['sessions'])->getTimestamp();
            $sess = DB::table('sessions')->where('last_activity', '<', $before);
            $counts['sessions'] = $sess->count();
            if (! $dry) {
                $sess->delete();
            }
        }

        foreach ($counts as $table => $n) {
            $this->line(($dry ? '[dry-run] ' : '')."{$table}: {$n}");
        }

        return self::SUCCESS;
    }
}
