<?php

declare(strict_types=1);

use App\Http\Controllers\Chat\AgentStreamController;
use App\Http\Controllers\Chat\ChatInsightsController;
use App\Http\Controllers\Chat\ChatPageController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Internal\SpecStatusController;
use App\Http\Controllers\Llm\LlmModelController;
use App\Http\Controllers\Mcp\McpProcessController;
use App\Http\Controllers\Mcp\McpStatusController;
use App\Http\Controllers\Projects\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', ChatPageController::class)->name('chat.index');

Route::get('/internal/spec-status', SpecStatusController::class)
    ->name('internal.spec-status');

Route::get('/conversations', [ConversationController::class, 'index']);
Route::post('/conversations', [ConversationController::class, 'store']);
Route::patch('/conversations/{conversation}', [ConversationController::class, 'update']);
Route::post('/conversations/{conversation}/summary', [ConversationController::class, 'summary']);
Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);
Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);

Route::post('/chat/{conversation}/stream', AgentStreamController::class)
    ->middleware('throttle:10,1')
    ->name('chat.stream');

Route::get('/chat/insights', ChatInsightsController::class);

Route::get('/projects', [ProjectController::class, 'index']);
Route::post('/projects/index', [ProjectController::class, 'indexRepository']);
Route::post('/projects/clone', [ProjectController::class, 'cloneRepository']);
Route::delete('/projects/{name}', [ProjectController::class, 'destroy']);

Route::get('/mcp/status', [McpStatusController::class, 'show']);
Route::post('/mcp/start', [McpProcessController::class, 'start']);
Route::post('/mcp/stop', [McpProcessController::class, 'stop']);

Route::get('/llm/models', [LlmModelController::class, 'index']);
Route::put('/llm/models', [LlmModelController::class, 'sync']);
