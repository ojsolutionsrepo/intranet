<?php

namespace App\Console\Commands;

use App\Modules\Projects\Services\ProjectSyncService;
use Illuminate\Console\Command;

class SyncIntegrationsCommand extends Command
{
    protected $signature = 'intranet:sync-integrations';

    protected $description = 'Pull Plane and Governex projects into the local mirror';

    public function handle(ProjectSyncService $sync): int
    {
        $result = $sync->syncAll();
        $this->info("Plane: {$result['plane']}, Governex: {$result['governex']}");
        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
