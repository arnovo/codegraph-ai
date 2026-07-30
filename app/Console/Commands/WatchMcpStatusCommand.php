<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Mcp\Services\PublishMcpStatusService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Console\Command;
use Throwable;

final class WatchMcpStatusCommand extends Command
{
    protected $signature = 'mcp:watch-status {--interval=5 : Seconds between health checks}';

    protected $description = 'Broadcast MCP status changes over WebSocket (Reverb)';

    public function handle(PublishMcpStatusService $publisher): int
    {
        $interval = max(1, (int) $this->option('interval'));

        $this->info("Watching MCP status every {$interval}s…");

        while (true) {
            try {
                $publisher->publishIfChanged();
            } catch (BroadcastException|Throwable $e) {
                $this->warn('Broadcast failed: '.$e->getMessage());
            }

            sleep($interval);
        }
    }
}
