<?php

declare(strict_types=1);

namespace App\Domains\Mcp\Services;

use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Events\McpStatusUpdated;
use Illuminate\Support\Facades\Cache;

final class PublishMcpStatusService
{
    private const CACHE_KEY = 'mcp_status_broadcast_hash';

    public function __construct(
        private readonly McpProcessManagerInterface $manager,
    ) {}

    public function publishIfChanged(bool $force = false): void
    {
        $status = $this->manager->status()->toArray();
        $hash = md5(json_encode($status, JSON_THROW_ON_ERROR));

        if (! $force && Cache::get(self::CACHE_KEY) === $hash) {
            return;
        }

        Cache::put(self::CACHE_KEY, $hash, 3600);
        McpStatusUpdated::dispatch($status);
    }
}
