<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domains\Chat\Contracts\ConversationRepositoryInterface;
use App\Domains\Chat\Enums\MessageRole;
use App\Domains\Chat\Models\Conversation;

final class EloquentConversationRepository implements ConversationRepositoryInterface
{
    /**
     * @return list<Conversation>
     */
    public function allOrderedByUpdatedAt(): array
    {
        return Conversation::query()
            ->withCount([
                'messages as messages_count' => static function ($query): void {
                    $query->whereIn('role', [
                        MessageRole::User->value,
                        MessageRole::Assistant->value,
                    ]);
                },
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->all();
    }

    public function find(string $id): ?Conversation
    {
        return Conversation::query()->find($id);
    }

    public function create(string $title, ?string $primaryProjectName = null): Conversation
    {
        return Conversation::query()->create([
            'title' => $title,
            'primary_project_name' => $primaryProjectName,
        ]);
    }

    public function updateTitle(Conversation $conversation, string $title): Conversation
    {
        $conversation->fill(['title' => $title]);
        $conversation->save();

        return $conversation;
    }

    public function updateSummary(
        Conversation $conversation,
        string $summary,
        int $messageCount,
    ): Conversation {
        $conversation->fill([
            'summary' => $summary,
            'summary_message_count' => $messageCount,
        ]);
        $conversation->save();

        return $conversation;
    }

    public function delete(Conversation $conversation): void
    {
        $conversation->delete();
    }

    public function touchUpdatedAt(Conversation $conversation): void
    {
        $conversation->touch();
    }
}
