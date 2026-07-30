<?php

declare(strict_types=1);

namespace App\Domains\Internal\DTO;

final readonly class ProjectStatusData
{
    /**
     * @param array<string, int> $progress
     * @param array<string, string> $stack
     * @param array<string, mixed> $tasks
     * @param list<array<string, mixed>> $artifacts
     * @param list<array<string, mixed>> $implementation
     * @param list<array<string, mixed>> $userStories
     * @param list<string> $nextSteps
     */
    public function __construct(
        public string $feature,
        public string $generatedAt,
        public array $progress,
        public array $stack,
        public array $tasks,
        public array $artifacts,
        public array $implementation,
        public array $userStories,
        public array $nextSteps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'feature' => $this->feature,
            'generatedAt' => $this->generatedAt,
            'progress' => $this->progress,
            'stack' => $this->stack,
            'tasks' => $this->tasks,
            'artifacts' => $this->artifacts,
            'implementation' => $this->implementation,
            'userStories' => $this->userStories,
            'nextSteps' => $this->nextSteps,
        ];
    }
}
