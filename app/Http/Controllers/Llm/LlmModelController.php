<?php

declare(strict_types=1);

namespace App\Http\Controllers\Llm;

use App\Domains\Agent\Services\LlmModelCatalogService;
use App\Domains\Agent\Services\SyncLlmModelProfilesService;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncLlmModelsRequest;
use Illuminate\Http\JsonResponse;

final class LlmModelController extends Controller
{
    public function index(LlmModelCatalogService $catalog): JsonResponse
    {
        return response()->json([
            'models' => array_map(
                fn ($profile) => $profile->toArray(),
                $catalog->listAll(),
            ),
            'env' => $catalog->envSnapshot(),
        ]);
    }

    public function sync(SyncLlmModelsRequest $request, SyncLlmModelProfilesService $sync): JsonResponse
    {
        $models = $sync->execute(
            $request->validated('models'),
            $request->validated('removed_ids') ?? [],
        );

        return response()->json([
            'models' => array_map(fn ($profile) => $profile->toArray(), $models),
        ]);
    }
}
