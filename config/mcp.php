<?php

declare(strict_types=1);

return [
    'rpc_url' => env('MCP_RPC_URL', 'http://host.docker.internal:9749/rpc'),
    'ui_url' => env('MCP_UI_URL', 'http://localhost:9749'),
    'binary' => env('MCP_BINARY', 'codebase-memory-mcp'),
    'health_timeout' => (int) env('MCP_HEALTH_TIMEOUT', 3),
    'on_host' => filter_var(env('MCP_ON_HOST', true), FILTER_VALIDATE_BOOL),
    'cli_fallback' => filter_var(
        env('MCP_CLI_FALLBACK', ! filter_var(env('MCP_ON_HOST', true), FILTER_VALIDATE_BOOL)),
        FILTER_VALIDATE_BOOL,
    ),
    'repos' => [
        'host_path' => env('REPOS_HOST_PATH', '/repos'),
        'container_path' => env('REPOS_CONTAINER_PATH', '/repos'),
    ],
    'projects_cache_ttl' => (int) env('MCP_PROJECTS_CACHE_TTL', 300),
    'projects_cache_stale_after' => (int) env('MCP_PROJECTS_CACHE_STALE_AFTER', 30),
    'projects_filter' => [
        'prinex_only' => filter_var(env('PROJECTS_FILTER_PRINEX_ONLY', true), FILTER_VALIDATE_BOOL),
        'origin_markers' => [
            'bitbucket.org/prinex/',
            'bitbucket.org:prinex/',
        ],
    ],
    'tools_cache_enabled' => filter_var(env('MCP_TOOLS_CACHE_ENABLED', true), FILTER_VALIDATE_BOOL),
    'tools_cache_ttl' => (int) env('MCP_TOOLS_CACHE_TTL', 3600),
    'docker' => [
        'compose_file' => env('DOCKER_COMPOSE_FILE', base_path('docker-compose.yml')),
        'service_name' => env('MCP_DOCKER_SERVICE', 'mcp'),
        'working_directory' => env('DOCKER_COMPOSE_CWD', base_path()),
    ],
];
