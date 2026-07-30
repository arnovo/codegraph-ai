<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Chat\Contracts\MessageRepositoryInterface;
use App\Domains\Chat\Services\CreateConversationService;
use App\Domains\Chat\Services\DeleteConversationService;
use App\Domains\Chat\Services\GenerateConversationSummaryService;
use App\Domains\Chat\Services\ListConversationsService;
use App\Domains\Chat\Services\RenameConversationService;
use App\Domains\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DomainException;

final class ConversationController extends Controller
{
    public function index(ListConversationsService $service): JsonResponse
    {
        return response()->json($service->execute());
    }

    public function store(Request $request, CreateConversationService $create): JsonResponse
    {
        $conversation = $create->execute(
            $request->string('title')->toString() ?: null,
            $request->string('primary_project_name')->toString() ?: null,
        );

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'primary_project_name' => $conversation->primary_project_name,
            'summary' => $conversation->summary,
            'summary_message_count' => $conversation->summary_message_count,
            'messages_count' => 0,
            'created_at' => $conversation->created_at?->toIso8601String(),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, Conversation $conversation, RenameConversationService $rename): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:255']]);
        $updated = $rename->execute($conversation, $request->string('title')->toString());

        return response()->json([
            'id' => $updated->id,
            'title' => $updated->title,
            'primary_project_name' => $updated->primary_project_name,
            'summary' => $updated->summary,
            'summary_message_count' => $updated->summary_message_count,
            'updated_at' => $updated->updated_at?->toIso8601String(),
        ]);
    }

    public function summary(
        Conversation $conversation,
        GenerateConversationSummaryService $service,
    ): JsonResponse {
        try {
            return response()->json($service->execute($conversation));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function destroy(Conversation $conversation, DeleteConversationService $delete): Response
    {
        $delete->execute($conversation);

        return response()->noContent();
    }

    public function messages(Conversation $conversation, MessageRepositoryInterface $messages): JsonResponse
    {
        return response()->json(array_map(
            static fn ($m) => [
                'id' => $m->id,
                'role' => $m->role->value,
                'content' => $m->content,
                'metadata' => $m->metadata,
                'created_at' => $m->created_at?->toIso8601String(),
            ],
            $messages->forConversation($conversation),
        ));
    }
}
