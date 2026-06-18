<?php

use App\Http\Controllers\Api\Mobile\DailyQuest\DashboardController;
use App\Http\Controllers\Api\Mobile\DailyQuest\HistoryController;
use App\Http\Controllers\Api\Mobile\DailyQuest\ProfileController;
use App\Http\Controllers\Api\Mobile\DailyQuest\TaskCategoryController;
use App\Http\Controllers\Api\Mobile\DailyQuest\TaskController;
use App\Http\Controllers\Api\Mobile\DailyQuest\TaskInstanceController;
use App\Http\Controllers\Api\Mobile\DailyQuest\TodayController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('api.mobile.')->group(function () {
    Route::middleware('mobile.auth')->prefix('daily-quest')->name('daily-quest.')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('today', TodayController::class)->name('today');

        Route::post('instances/{instance}/complete', [TaskInstanceController::class, 'complete'])
            ->name('instances.complete');
        Route::post('instances/{instance}/uncomplete', [TaskInstanceController::class, 'uncomplete'])
            ->name('instances.uncomplete');
        Route::patch('instances/{instance}/notes', [TaskInstanceController::class, 'updateNotes'])
            ->name('instances.notes.update');

        Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('tasks/{task}/pause', [TaskController::class, 'pause'])->name('tasks.pause');
        Route::post('tasks/{task}/duplicate', [TaskController::class, 'duplicate'])->name('tasks.duplicate');

        Route::get('history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('history/{date}', [HistoryController::class, 'show'])->name('history.show');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile/display-name', [ProfileController::class, 'updateDisplayName'])->name('profile.display-name.update');

        Route::get('categories', [TaskCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [TaskCategoryController::class, 'store'])->name('categories.store');
        Route::patch('categories/{category}', [TaskCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [TaskCategoryController::class, 'destroy'])->name('categories.destroy');
    });
});
