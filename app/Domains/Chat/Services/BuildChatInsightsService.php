<?php

declare(strict_types=1);

namespace App\Domains\Chat\Services;

use App\Domains\Chat\Contracts\ChatInsightsRepositoryInterface;
use App\Domains\Chat\DTO\ChatInsightsData;
use App\Domains\Projects\Contracts\ProjectCatalogInterface;

final class BuildChatInsightsService
{
    private const DEFAULT_TITLE = 'Nueva conversación';

    public function __construct(
        private readonly ChatInsightsRepositoryInterface $insights,
        private readonly ProjectCatalogInterface $projects,
    ) {}

    public function execute(?string $projectName = null): ChatInsightsData
    {
        $scopedName = $this->normalizeProjectName($projectName);
        $totalQuestions = $this->insights->countUserQuestions();
        $projectQuestions = $scopedName !== null
            ? $this->insights->countUserQuestions($scopedName)
            : 0;

        return new ChatInsightsData(
            projectName: $scopedName,
            generatedAt: now()->toIso8601String(),
            totalUserQuestions: $totalQuestions,
            projectUserQuestions: $projectQuestions,
            questionsLast7Days: $this->insights->countUserQuestionsSince(7, $scopedName),
            questionsLast30Days: $this->insights->countUserQuestionsSince(30, $scopedName),
            conversationsThisWeek: $this->insights->countConversationsSince(7),
            activeProjectSharePercent: $this->sharePercent($projectQuestions, $totalQuestions, $scopedName),
            messagesByDay: $this->insights->userMessagesByDay(14, $scopedName),
            topProjectsByQuestions: $this->mapTopProjects($this->insights->topProjectsByQuestionCount()),
            frequentQuestions: $this->frequentQuestions($scopedName),
            toolsByName: $this->toolsByName($scopedName),
            topSearchQueries: $this->topSearchQueries($scopedName),
            topCitedFiles: $this->topCitedFiles($scopedName),
            topModelsByUsage: $this->topModelsByUsage($scopedName),
        );
    }

    private function normalizeProjectName(?string $projectName): ?string
    {
        $normalized = trim((string) $projectName);

        return $normalized !== '' ? $normalized : null;
    }

    private function sharePercent(int $projectQuestions, int $totalQuestions, ?string $projectName): ?int
    {
        if ($projectName === null || $totalQuestions === 0) {
            return null;
        }

        return (int) round(($projectQuestions / $totalQuestions) * 100);
    }

    /**
     * @param  list<array{project_name: string|null, question_count: int}>  $rows
     * @return list<array{name: string, display_name: string, question_count: int}>
     */
    private function mapTopProjects(array $rows): array
    {
        $displayNames = [];
        foreach ($this->projects->list() as $project) {
            $displayNames[$project->name] = $project->displayName !== ''
                ? $project->displayName
                : $project->name;
        }

        return array_values(array_filter(array_map(
            static function (array $row) use ($displayNames): ?array {
                $name = (string) ($row['project_name'] ?? '');
                if ($name === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'display_name' => $displayNames[$name] ?? $name,
                    'question_count' => (int) $row['question_count'],
                ];
            },
            $rows,
        )));
    }

    /**
     * @return list<array{text: string, count: int}>
     */
    private function frequentQuestions(?string $projectName): array
    {
        $counts = [];
        foreach ($this->insights->userMessageContents($projectName) as $row) {
            $normalized = $this->normalizeQuestionText((string) $row['content']);
            if ($normalized === '' || $normalized === mb_strtolower(self::DEFAULT_TITLE)) {
                continue;
            }

            $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
        }

        arsort($counts);

        return $this->rankItems($counts, 10);
    }

    /**
     * @return list<array{name: string, count: int}>
     */
    private function toolsByName(?string $projectName): array
    {
        $counts = [];
        foreach ($this->insights->assistantMessageMetadata($projectName) as $row) {
            foreach ($row['metadata']['tools'] ?? [] as $tool) {
                if (! is_array($tool)) {
                    continue;
                }

                $name = (string) ($tool['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }

        arsort($counts);

        return $this->rankNamedItems($counts, 5);
    }

    /**
     * @return list<array{query: string, count: int}>
     */
    private function topSearchQueries(?string $projectName): array
    {
        $counts = [];
        foreach ($this->insights->assistantMessageMetadata($projectName) as $row) {
            foreach ($row['metadata']['tools'] ?? [] as $tool) {
                if (! is_array($tool) || ($tool['name'] ?? '') !== 'search_graph') {
                    continue;
                }

                $arguments = is_array($tool['arguments'] ?? null) ? $tool['arguments'] : [];
                $query = trim((string) ($arguments['query'] ?? ''));
                if ($query === '') {
                    continue;
                }

                $counts[mb_strtolower($query)] = ($counts[mb_strtolower($query)] ?? 0) + 1;
            }
        }

        arsort($counts);

        return array_map(
            static fn (array $item): array => ['query' => $item['name'], 'count' => $item['count']],
            $this->rankNamedItems($counts, 5),
        );
    }

    /**
     * @return list<array{file: string, count: int}>
     */
    private function topCitedFiles(?string $projectName): array
    {
        $counts = [];
        foreach ($this->insights->assistantMessageMetadata($projectName) as $row) {
            foreach ($row['metadata']['citations'] ?? [] as $citation) {
                if (! is_array($citation)) {
                    continue;
                }

                $file = trim((string) ($citation['file'] ?? ''));
                if ($file === '') {
                    continue;
                }

                $counts[$file] = ($counts[$file] ?? 0) + 1;
            }
        }

        arsort($counts);

        return array_map(
            static fn (array $item): array => ['file' => $item['name'], 'count' => $item['count']],
            $this->rankNamedItems($counts, 5),
        );
    }

    /**
     * @return list<array{model: string, count: int}>
     */
    private function topModelsByUsage(?string $projectName): array
    {
        $counts = [];
        foreach ($this->insights->assistantMessageMetadata($projectName) as $row) {
            $model = trim((string) ($row['metadata']['model'] ?? ''));
            if ($model === '') {
                continue;
            }

            $counts[$model] = ($counts[$model] ?? 0) + 1;
        }

        arsort($counts);

        return array_map(
            static fn (array $item): array => ['model' => $item['name'], 'count' => $item['count']],
            $this->rankNamedItems($counts, 5),
        );
    }

    private function normalizeQuestionText(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', mb_strtolower(trim($text))) ?? '';

        return mb_strlen($normalized) > 120 ? mb_substr($normalized, 0, 120) : $normalized;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{text: string, count: int}>
     */
    private function rankItems(array $counts, int $limit): array
    {
        $items = [];
        foreach (array_slice($counts, 0, $limit, true) as $text => $count) {
            $items[] = ['text' => (string) $text, 'count' => (int) $count];
        }

        return $items;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{name: string, count: int}>
     */
    private function rankNamedItems(array $counts, int $limit): array
    {
        $items = [];
        foreach (array_slice($counts, 0, $limit, true) as $name => $count) {
            $items[] = ['name' => (string) $name, 'count' => (int) $count];
        }

        return $items;
    }
}
