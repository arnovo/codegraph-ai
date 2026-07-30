<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domains\Chat\Contracts\ChatInsightsRepositoryInterface;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Message;
use Illuminate\Database\Eloquent\Builder;

final class EloquentChatInsightsRepository implements ChatInsightsRepositoryInterface
{
    public function countUserQuestions(?string $projectName = null): int
    {
        return $this->userMessageQuery($projectName)->count();
    }

    public function countUserQuestionsSince(int $days, ?string $projectName = null): int
    {
        return $this->userMessageQuery($projectName)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function countConversationsSince(int $days): int
    {
        return (int) Message::query()
            ->getConnection()
            ->table('conversations')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function userMessagesByDay(int $days, ?string $projectName = null): array
    {
        $rows = $this->userMessageQuery($projectName)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw('date(created_at) as day, count(*) as aggregate')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return array_map(
            static fn (object $row): array => [
                'date' => (string) $row->day,
                'count' => (int) $row->aggregate,
            ],
            $rows->all(),
        );
    }

    public function topProjectsByQuestionCount(int $limit = 5): array
    {
        $rows = Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('messages.role', MessageRole::User->value)
            ->selectRaw('conversations.primary_project_name as project_name, count(*) as aggregate')
            ->groupBy('conversations.primary_project_name')
            ->orderByDesc('aggregate')
            ->limit($limit)
            ->get();

        return array_map(
            static fn (object $row): array => [
                'project_name' => $row->project_name !== null ? (string) $row->project_name : null,
                'question_count' => (int) $row->aggregate,
            ],
            $rows->all(),
        );
    }

    public function userMessageContents(?string $projectName = null): array
    {
        $query = Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('messages.role', MessageRole::User->value);

        if ($projectName !== null && $projectName !== '') {
            $query->where('conversations.primary_project_name', $projectName);
        }

        return $query
            ->get(['messages.content', 'conversations.primary_project_name'])
            ->map(static fn (Message $message): array => [
                'content' => (string) $message->content,
                'project_name' => $message->getAttribute('primary_project_name'),
            ])
            ->all();
    }

    public function assistantMessageMetadata(?string $projectName = null): array
    {
        $query = Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('messages.role', MessageRole::Assistant->value);

        if ($projectName !== null && $projectName !== '') {
            $query->where('conversations.primary_project_name', $projectName);
        }

        return $query
            ->get(['messages.metadata', 'conversations.primary_project_name'])
            ->map(static fn (Message $message): array => [
                'metadata' => is_array($message->metadata) ? $message->metadata : [],
                'project_name' => $message->getAttribute('primary_project_name'),
            ])
            ->all();
    }

    /**
     * @return Builder<Message>
     */
    private function userMessageQuery(?string $projectName = null): Builder
    {
        $query = Message::query()->where('role', MessageRole::User->value);

        if ($projectName === null || $projectName === '') {
            return $query;
        }

        return $query->whereHas(
            'conversation',
            static fn (Builder $builder): Builder => $builder->where('primary_project_name', $projectName),
        );
    }
}
