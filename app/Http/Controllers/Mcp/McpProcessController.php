<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mcp;

use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Domains\Mcp\Services\PublishMcpStatusService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class McpProcessController extends Controller
{
    public function start(
        McpProcessManagerInterface $manager,
        PublishMcpStatusService $publisher,
    ): JsonResponse {
        $status = $manager->start()->toArray();
        $publisher->publishIfChanged(force: true);

        return response()->json($status);
    }

    public function stop(
        McpProcessManagerInterface $manager,
        PublishMcpStatusService $publisher,
    ): JsonResponse {
        $status = $manager->stop()->toArray();
        $publisher->publishIfChanged(force: true);

        return response()->json($status);
    }
}
