<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Agent\Services\McpToolResultCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class McpToolResultCacheTest extends TestCase
{
    public function test_it_returns_cached_tool_payload_for_same_arguments(): void
    {
        config([
            'mcp.tools_cache_enabled' => true,
            'mcp.tools_cache_ttl' => 3600,
        ]);

        Cache::flush();

        $cache = app(McpToolResultCache::class);
        $payload = ['result' => ['hits' => 1], 'summary' => '{"hits":1}'];

        $cache->put('search_graph', ['query' => 'auth', 'project' => 'demo'], 'demo', $payload);

        $hit = $cache->get('search_graph', ['project' => 'demo', 'query' => 'auth'], 'demo');

        $this->assertSame($payload, $hit);
    }

    public function test_it_invalidates_project_entries_after_reindex(): void
    {
        config([
            'mcp.tools_cache_enabled' => true,
            'mcp.tools_cache_ttl' => 3600,
        ]);

        Cache::flush();

        $cache = app(McpToolResultCache::class);
        $payload = ['result' => ['hits' => 1], 'summary' => '{"hits":1}'];

        $cache->put('search_graph', ['query' => 'auth', 'project' => 'demo'], 'demo', $payload);
        $cache->invalidateProject('demo');

        $this->assertNull($cache->get('search_graph', ['query' => 'auth', 'project' => 'demo'], 'demo'));
    }
}
