<?php

declare(strict_types=1);

use App\Http\Controllers\Internal\SpecStatusController;
use Illuminate\Support\Facades\Route;

/*
| Internal routes — NOT linked from chat UI. Direct URL access only.
| Merge into routes/web.php after Laravel scaffold (T001).
|
| URL: GET /internal/spec-status
*/
Route::get('/internal/spec-status', SpecStatusController::class)
    ->name('internal.spec-status');
