<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Projects\Services\GitRepositoriesSyncService;
use Illuminate\Console\Command;

final class SyncGitRepositoriesCommand extends Command
{
    protected $signature = 'git:sync-repos {--loop : Repite la sincronización según GIT_SYNC_INTERVAL_MINUTES}';

    protected $description = 'Clona o actualiza los repositorios definidos en GIT_REPOS_URLS';

    public function handle(GitRepositoriesSyncService $syncService): int
    {
        do {
            $urls = config('projects.git.repos_urls', []);

            if ($urls === []) {
                $this->warn('GIT_REPOS_URLS está vacío. No hay repositorios que sincronizar.');

                return self::SUCCESS;
            }

            $results = $syncService->sync($urls);

            foreach ($results as $result) {
                $this->info(sprintf(
                    '[%s] %s → %s',
                    strtoupper($result['action']),
                    $result['url'],
                    $result['path'],
                ));
            }

            if (! $this->option('loop')) {
                break;
            }

            $minutes = (int) config('projects.git.sync.interval_minutes', 60);
            $this->line(sprintf('Esperando %d minutos…', $minutes));
            sleep(max(1, $minutes) * 60);
        } while (true);

        return self::SUCCESS;
    }
}
