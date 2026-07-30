<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mcp;

use App\Domains\Mcp\Contracts\McpProcessManagerInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class McpStatusController extends Controller
{
    public function show(McpProcessManagerInterface $manager): JsonResponse
    {
        return response()->json($manager->status()->toArray());
    }
}
