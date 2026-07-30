<?php

declare(strict_types=1);

namespace App\Domains\Chat\Contracts;

interface ChatInsightsRepositoryInterface
{
    public function countUserQuestions(?string $projectName = null): int;

    public function countUserQuestionsSince(int $days, ?string $projectName = null): int;

    public function countConversationsSince(int $days): int;

    /**
     * @return list<array{date: string, count: int}>
     */
    public function userMessagesByDay(int $days, ?string $projectName = null): array;

    /**
     * @return list<array{project_name: string|null, question_count: int}>
     */
    public function topProjectsByQuestionCount(int $limit = 5): array;

    /**
     * @return list<array{content: string, project_name: string|null}>
     */
    public function userMessageContents(?string $projectName = null): array;

    /**
     * @return list<array{metadata: array<string, mixed>, project_name: string|null}>
     */
    public function assistantMessageMetadata(?string $projectName = null): array;
}
