<?php

declare(strict_types=1);

namespace App\Domains\Agent\Services;

use App\Domains\Mcp\Contracts\McpClientInterface;
use InvalidArgumentException;

final class ToolExecutionService
{
    /** @var list<string> */
    private const AGENT_TOOLS = [
        'search_graph',
        'get_code_snippet',
        'trace_path',
    ];

    public function __construct(
        private readonly McpClientInterface $mcpClient,
        private readonly McpToolResultCache $toolCache,
    ) {}

    /** @return list<array<string, mixed>> */
    public function openAiToolDefinitions(?string $activeProjectName = null): array
    {
        return array_map(fn (string $name) => [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $this->descriptionFor($name),
                'parameters' => $this->parametersFor($name),
            ],
        ], $this->allowedTools($activeProjectName));
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{result: mixed, summary: string}
     */
    public function execute(string $name, array $arguments, ?string $activeProjectName = null): array
    {
        if (! in_array($name, $this->allowedTools($activeProjectName), true)) {
            throw new InvalidArgumentException("Tool not allowed: {$name}");
        }

        $arguments = $this->normalizeArguments($name, $arguments);
        $project = $activeProjectName ?? (string) ($arguments['project'] ?? '');

        $cached = $this->toolCache->get($name, $arguments, $project !== '' ? $project : null);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->mcpClient->callTool($name, $arguments);
        $encoded = json_encode($result);
        $summary = is_string($encoded) ? mb_substr($encoded, 0, 200) : '';

        $payload = ['result' => $result, 'summary' => $summary];
        $this->toolCache->put($name, $arguments, $project !== '' ? $project : null, $payload);

        return $payload;
    }

    /** @return list<string> */
    private function allowedTools(?string $activeProjectName): array
    {
        if ($activeProjectName === null || trim($activeProjectName) === '') {
            return [];
        }

        return self::AGENT_TOOLS;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function normalizeArguments(string $name, array $arguments): array
    {
        if ($name !== 'get_code_snippet') {
            return $arguments;
        }

        if (! isset($arguments['qualified_name']) || $arguments['qualified_name'] === '') {
            foreach (['function_name', 'symbol', 'name'] as $legacyKey) {
                if (isset($arguments[$legacyKey]) && is_string($arguments[$legacyKey]) && $arguments[$legacyKey] !== '') {
                    $arguments['qualified_name'] = $arguments[$legacyKey];
                    break;
                }
            }
        }

        unset($arguments['function_name'], $arguments['symbol'], $arguments['name']);

        return $arguments;
    }

    private function descriptionFor(string $name): string
    {
        return match ($name) {
            'search_graph' => 'Search the code knowledge graph. Returns results with qualified_name — use that for get_code_snippet.',
            'get_code_snippet' => 'Retrieve source code for a symbol. Requires qualified_name from search_graph results.',
            'trace_path' => 'Trace call/data flow paths between symbols. Use only for flow or caller questions.',
            default => $name,
        };
    }

    /** @return array<string, mixed> */
    private function parametersFor(string $name): array
    {
        return match ($name) {
            'get_code_snippet' => [
                'type' => 'object',
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Indexed project name'],
                    'qualified_name' => ['type' => 'string', 'description' => 'Full qualified_name from search_graph'],
                ],
                'required' => ['qualified_name'],
            ],
            'trace_path' => [
                'type' => 'object',
                'properties' => [
                    'project' => ['type' => 'string'],
                    'from' => ['type' => 'string', 'description' => 'Source symbol qualified_name or name'],
                    'to' => ['type' => 'string', 'description' => 'Target symbol (optional)'],
                ],
                'required' => ['from'],
            ],
            default => [
                'type' => 'object',
                'properties' => [
                    'project' => ['type' => 'string'],
                    'query' => ['type' => 'string'],
                ],
                'required' => ['query'],
            ],
        };
    }
}
