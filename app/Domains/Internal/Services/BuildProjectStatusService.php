<?php

declare(strict_types=1);

namespace App\Domains\Internal\Services;

use App\Domains\Internal\DTO\ProjectStatusData;

final class BuildProjectStatusService
{
    /** @var list<string> */
    private const DOMAIN_DIRS = ['Internal', 'Mcp', 'Projects', 'Chat', 'Agent'];

    public function __construct(
        private readonly string $basePath,
    ) {}

    public function execute(): ProjectStatusData
    {
        $featureDir = '.specify/features/001-codebase-memory-chat';
        $tasksPath = $this->basePath.'/'.$featureDir.'/tasks.md';
        $tasks = $this->parseTasks($tasksPath);

        $artifacts = [
            ['name' => 'Constitution', 'path' => '.specify/memory/constitution.md'],
            ['name' => 'Feature spec', 'path' => $featureDir.'/spec.md'],
            ['name' => 'Plan', 'path' => $featureDir.'/plan.md'],
            ['name' => 'Tasks', 'path' => $featureDir.'/tasks.md'],
            ['name' => 'Research', 'path' => $featureDir.'/research.md'],
            ['name' => 'Data model', 'path' => $featureDir.'/data-model.md'],
            ['name' => 'HTTP API', 'path' => $featureDir.'/contracts/http-api.md'],
            ['name' => 'Agent/MCP', 'path' => $featureDir.'/contracts/agent-mcp-tools.md'],
            ['name' => 'Quickstart', 'path' => $featureDir.'/quickstart.md'],
            ['name' => 'Prinex DS', 'path' => $featureDir.'/design-system/codebase-memory-chat/MASTER.md'],
            ['name' => 'Chat UI DS', 'path' => $featureDir.'/design-system/codebase-memory-chat/pages/chat-index.md'],
        ];

        $artifactRows = array_map(fn (array $a) => [
            ...$a,
            'ok' => is_file($this->basePath.'/'.$a['path']),
        ], $artifacts);

        $domainsOk = $this->countExistingDomains();

        $implChecks = [
            ['name' => 'Laravel scaffold (artisan)', 'ok' => is_file($this->basePath.'/artisan')],
            ['name' => sprintf('Domains (%d/5)', $domainsOk), 'ok' => $domainsOk === 5],
            ['name' => 'config/mcp.php + llm.php', 'ok' => is_file($this->basePath.'/config/mcp.php') && is_file($this->basePath.'/config/llm.php')],
            ['name' => 'docker-compose.yml', 'ok' => is_file($this->basePath.'/docker-compose.yml')],
            ['name' => 'Inertia middleware', 'ok' => is_file($this->basePath.'/app/Http/Middleware/HandleInertiaRequests.php')],
            ['name' => 'Frontend build', 'ok' => is_file($this->basePath.'/public/build/manifest.json')],
            ['name' => 'Vue Pages', 'ok' => $this->dirHasFiles('resources/js/Pages')],
            ['name' => 'Chat migrations', 'ok' => $this->hasMigration('create_conversations')],
            ['name' => 'LLM model profiles', 'ok' => $this->hasMigration('llm_model_profiles')],
            ['name' => 'Domain tests', 'ok' => $this->dirHasFiles('tests/Unit/Domains')],
            ['name' => 'Clone Bitbucket (US8)', 'ok' => is_file($this->basePath.'/app/Domains/Projects/Services/CloneAndIndexRepositoryService.php')],
            ['name' => 'MCP Docker service', 'ok' => $this->composeDefinesService('mcp')],
            ['name' => 'Git repos sync', 'ok' => is_file($this->basePath.'/app/Domains/Projects/Services/GitRepositoriesSyncService.php')],
        ];

        $planningOk = count(array_filter($artifactRows, fn ($a) => $a['ok']));
        $planningPct = (int) round(100 * $planningOk / max(count($artifactRows), 1));
        $taskPct = (int) round(100 * $tasks['done'] / max($tasks['total'], 1));
        $implOk = count(array_filter($implChecks, fn ($i) => $i['ok']));
        $implPct = (int) round(100 * $implOk / max(count($implChecks), 1));

        return new ProjectStatusData(
            feature: '001-codebase-memory-chat',
            generatedAt: now()->toIso8601String(),
            progress: [
                'planning' => $planningPct,
                'tasks' => $taskPct,
                'implementation' => $implPct,
                'overall' => (int) round(($planningPct + $taskPct + $implPct) / 3),
            ],
            stack: [
                'backend' => 'Laravel 13 + PHP 8.4 (Docker)',
                'frontend' => 'Inertia 3 + Vue 3 + TypeScript',
                'design' => '@prinex/ui-vue3 (local sibling)',
                'infra' => 'Docker Compose (app/nginx/postgres)',
                'mcp' => 'codebase-memory-mcp 0.8.1 · host :9749',
                'specs' => '.specify/features/001-codebase-memory-chat/',
            ],
            tasks: $tasks,
            artifacts: $artifactRows,
            implementation: $implChecks,
            userStories: [
                ['id' => 'US1', 'priority' => 'P1', 'title' => 'Chat + citas', 'phase' => 3, 'mvp' => true],
                ['id' => 'US2', 'priority' => 'P2', 'title' => 'Repos indexados', 'phase' => 4, 'mvp' => false],
                ['id' => 'US3', 'priority' => 'P3', 'title' => 'Historial', 'phase' => 5, 'mvp' => false],
                ['id' => 'US4', 'priority' => 'P4', 'title' => 'Control MCP', 'phase' => 6, 'mvp' => false],
                ['id' => 'US5', 'priority' => 'P3', 'title' => 'Insights vacío', 'phase' => 14, 'mvp' => false],
                ['id' => 'US6', 'priority' => 'P3', 'title' => 'Perfiles agente', 'phase' => 15, 'mvp' => false],
                ['id' => 'US7', 'priority' => 'P2', 'title' => 'Multi-proyecto', 'phase' => 19, 'mvp' => false],
                ['id' => 'US8', 'priority' => 'P2', 'title' => 'Clonar Bitbucket', 'phase' => 20, 'mvp' => false],
            ],
            nextSteps: $this->buildNextSteps($tasks['items']),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<string>
     */
    private function buildNextSteps(array $items): array
    {
        $pending = array_values(array_filter($items, fn (array $i) => ! ($i['done'] ?? false)));

        if ($pending === []) {
            return ['Ejecutar validación quickstart (T077), README (T076), y tests Phase 7'];
        }

        return array_map(
            fn (array $i) => sprintf('%s — %s', $i['id'], $i['desc']),
            array_slice($pending, 0, 5),
        );
    }

    private function countExistingDomains(): int
    {
        $count = 0;
        foreach (self::DOMAIN_DIRS as $dir) {
            if ($this->dirHasFiles('app/Domains/'.$dir)) {
                $count++;
            }
        }

        return $count;
    }

    private function hasMigration(string $needle): bool
    {
        $dir = $this->basePath.'/database/migrations';
        if (! is_dir($dir)) {
            return false;
        }

        foreach (scandir($dir) ?: [] as $file) {
            if (str_contains($file, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{total: int, done: int, pending: int, phases: list<array<string, mixed>>, items: list<array<string, mixed>>} */
    private function parseTasks(string $path): array
    {
        if (! is_file($path)) {
            return ['total' => 0, 'done' => 0, 'pending' => 0, 'phases' => [], 'items' => []];
        }

        $phases = [];
        $items = [];
        $current = null;

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/^## Phase (\d+): (.+)$/', $line, $m)) {
                $current = ['id' => (int) $m[1], 'name' => trim($m[2]), 'total' => 0, 'done' => 0];
                $phases[] = $current;

                continue;
            }

            if ($current !== null && preg_match('/^- \[( |x|X)\] (T\d+)\s*(.*)$/', $line, $m)) {
                $done = strtolower($m[1]) !== ' ';
                $current['total']++;
                if ($done) {
                    $current['done']++;
                }
                $phases[count($phases) - 1] = $current;
                $items[] = [
                    'id' => $m[2],
                    'done' => $done,
                    'desc' => trim($m[3]),
                    'phase' => $current['id'],
                    'phaseName' => $current['name'],
                ];
            }
        }

        $doneCount = count(array_filter($items, fn ($i) => $i['done']));

        return [
            'total' => count($items),
            'done' => $doneCount,
            'pending' => count($items) - $doneCount,
            'phases' => $phases,
            'items' => $items,
        ];
    }

    private function dirHasFiles(string $relative): bool
    {
        $path = $this->basePath.'/'.$relative;
        if (! is_dir($path)) {
            return false;
        }

        $it = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);

        return $it->valid();
    }

    private function composeDefinesService(string $serviceName): bool
    {
        $composePath = $this->basePath.'/docker-compose.yml';
        if (! is_file($composePath)) {
            return false;
        }

        $contents = (string) file_get_contents($composePath);

        return preg_match('/^\s{2}'.$serviceName.':\s*$/m', $contents) === 1;
    }
}
