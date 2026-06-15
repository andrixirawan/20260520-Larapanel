<?php

use App\Http\Controllers\Api\Post\PublicPostController;
use App\Http\Controllers\Api\Mobile\Post\PostController;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Route;

Route::get('posts', [PublicPostController::class, 'index'])->name('api.posts.index');
Route::get('posts/{post:slug}', [PublicPostController::class, 'show'])->name('api.posts.show');

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::middleware('mobile.auth')->group(function () {
        Route::get('posts', [PostController::class, 'index'])
            ->name('posts.index')
            ->middleware('can:'.AccessControl::PERMISSION_POSTS_VIEW);

        Route::get('posts/my', [PostController::class, 'mine'])
            ->name('posts.mine')
            ->middleware('can:'.AccessControl::PERMISSION_POSTS_VIEW);

        Route::apiResource('posts', PostController::class)
            ->except(['index'])
            ->middlewareFor('show', 'can:'.AccessControl::PERMISSION_POSTS_VIEW)
            ->middlewareFor('store', 'can:'.AccessControl::PERMISSION_POSTS_CREATE)
            ->middlewareFor('update', [
                'can:'.AccessControl::PERMISSION_POSTS_UPDATE,
                'can:update,post',
            ])
            ->middlewareFor('destroy', [
                'can:'.AccessControl::PERMISSION_POSTS_DELETE,
                'can:delete,post',
            ]);
    });
});
