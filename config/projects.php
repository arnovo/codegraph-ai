<?php

declare(strict_types=1);

return [
    'git' => [
        'allowed_hosts' => array_filter(array_map(
            'trim',
            explode(',', (string) env('GIT_ALLOWED_HOSTS', 'bitbucket.org')),
        )),
        'clone_timeout_seconds' => (int) env('GIT_CLONE_TIMEOUT_SECONDS', 600),
        'username' => env('GIT_USERNAME'),
        'token' => env('GIT_TOKEN'),
        'repos_urls' => array_values(array_filter(array_map(
            'trim',
            preg_split('/[\s,]+/', (string) env('GIT_REPOS_URLS', '')) ?: [],
        ))),
        'sync' => [
            'on_start' => filter_var(env('GIT_SYNC_ON_START', false), FILTER_VALIDATE_BOOL),
            'interval_minutes' => max(1, (int) env('GIT_SYNC_INTERVAL_MINUTES', 60)),
        ],
        'identity' => [
            'name' => env('GIT_USER_NAME', 'Codebase Assistant'),
            'email' => env('GIT_USER_EMAIL', 'codebase-assistant@example.com'),
        ],
    ],
];
