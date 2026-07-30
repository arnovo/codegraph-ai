<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Domains\Internal\Services\BuildProjectStatusService;
use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class SpecStatusController extends Controller
{
    public function __invoke(
        BuildProjectStatusService $status,
        McpProcessManagerInterface $mcpProcess,
    ): Response {
        $data = $status->execute()->toArray();
        $mcp = $mcpProcess->status();

        $data['runtime'] = [
            [
                'name' => 'App',
                'value' => config('app.url'),
                'ok' => true,
            ],
            [
                'name' => 'MCP RPC',
                'value' => config('mcp.rpc_url'),
                'ok' => $mcp->status === 'running',
            ],
            [
                'name' => 'MCP UI',
                'value' => config('mcp.ui_url'),
                'ok' => $mcp->status === 'running',
            ],
            [
                'name' => 'MCP status',
                'value' => $mcp->status.($mcp->message ? ' — '.$mcp->message : ''),
                'ok' => $mcp->status === 'running',
            ],
            [
                'name' => 'LLM',
                'value' => config('llm.driver').(filled(config('llm.api_key')) ? ' (key set)' : ' (sin API key)'),
                'ok' => filled(config('llm.api_key')) || config('llm.driver') === 'custom',
            ],
            [
                'name' => 'DB',
                'value' => config('database.default').' @ '.config('database.connections.'.config('database.default').'.database'),
                'ok' => true,
            ],
        ];

        return Inertia::render('Internal/SpecStatus', $data);
    }
}
