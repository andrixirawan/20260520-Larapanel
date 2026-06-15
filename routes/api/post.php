<?php

use App\Http\Controllers\Api\Mobile\Post\PostController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::middleware('mobile.auth')->group(function () {
        Route::apiResource('posts', PostController::class)
            ->middlewareFor(['index', 'show'], 'can:'.AccessControl::PERMISSION_POSTS_VIEW)
            ->middlewareFor('store', 'can:'.AccessControl::PERMISSION_POSTS_CREATE)
            ->middlewareFor('update', 'can:'.AccessControl::PERMISSION_POSTS_UPDATE)
            ->middlewareFor('destroy', 'can:'.AccessControl::PERMISSION_POSTS_DELETE);
    });
});
