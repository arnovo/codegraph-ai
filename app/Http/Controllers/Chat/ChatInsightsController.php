<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Chat\Services\BuildChatInsightsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ChatInsightsController extends Controller
{
    public function __invoke(Request $request, BuildChatInsightsService $insights): JsonResponse
    {
        $project = $request->query('project');
        $projectName = is_string($project) && trim($project) !== '' ? trim($project) : null;

        return response()->json($insights->execute($projectName)->toArray());
    }
}
