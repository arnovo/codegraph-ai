<?php

declare(strict_types=1);

namespace App\Domains\Chat\DTO;

final readonly class ChatInsightsData
{
    /**
     * @param  list<array{date: string, count: int}>  $messagesByDay
     * @param  list<array{name: string, display_name: string, question_count: int}>  $topProjectsByQuestions
     * @param  list<array{text: string, count: int}>  $frequentQuestions
     * @param  list<array{name: string, count: int}>  $toolsByName
     * @param  list<array{query: string, count: int}>  $topSearchQueries
     * @param  list<array{file: string, count: int}>  $topCitedFiles
     * @param  list<array{model: string, count: int}>  $topModelsByUsage
     */
    public function __construct(
        public ?string $projectName,
        public string $generatedAt,
        public int $totalUserQuestions,
        public int $projectUserQuestions,
        public int $questionsLast7Days,
        public int $questionsLast30Days,
        public int $conversationsThisWeek,
        public ?int $activeProjectSharePercent,
        public array $messagesByDay,
        public array $topProjectsByQuestions,
        public array $frequentQuestions,
        public array $toolsByName,
        public array $topSearchQueries,
        public array $topCitedFiles,
        public array $topModelsByUsage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scope' => [
                'project_name' => $this->projectName,
                'generated_at' => $this->generatedAt,
            ],
            'activity' => [
                'total_user_questions' => $this->totalUserQuestions,
                'project_user_questions' => $this->projectUserQuestions,
                'questions_last_7_days' => $this->questionsLast7Days,
                'questions_last_30_days' => $this->questionsLast30Days,
                'conversations_this_week' => $this->conversationsThisWeek,
                'messages_by_day' => $this->messagesByDay,
            ],
            'projects' => [
                'top_by_questions' => $this->topProjectsByQuestions,
                'active_project_share_percent' => $this->activeProjectSharePercent,
            ],
            'frequent_questions' => $this->frequentQuestions,
            'tools' => [
                'by_name' => $this->toolsByName,
                'top_search_queries' => $this->topSearchQueries,
            ],
            'citations' => [
                'top_files' => $this->topCitedFiles,
            ],
            'models' => [
                'top_by_usage' => $this->topModelsByUsage,
            ],
        ];
    }
}
