<?php

use App\Http\Controllers\DailyQuest\DashboardController;
use App\Http\Controllers\DailyQuest\HistoryController;
use App\Http\Controllers\DailyQuest\TaskCategoryController;
use App\Http\Controllers\DailyQuest\TaskController;
use App\Http\Controllers\DailyQuest\TaskInstanceController;
use App\Http\Controllers\DailyQuest\TodayController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('today', [TodayController::class, 'index'])->name('today');
    Route::patch('instances/{instance}/complete', [TaskInstanceController::class, 'complete'])->name('instances.complete');
    Route::patch('instances/{instance}/uncomplete', [TaskInstanceController::class, 'uncomplete'])->name('instances.uncomplete');

    Route::patch('tasks/{task}/pause', [TaskController::class, 'pause'])->name('tasks.pause');
    Route::post('tasks/{task}/duplicate', [TaskController::class, 'duplicate'])->name('tasks.duplicate');
    Route::resource('tasks', TaskController::class);

    Route::get('history', [HistoryController::class, 'index'])->name('history');
    Route::get('history/{date}', [HistoryController::class, 'show'])->name('history.show');

    Route::resource('categories', TaskCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
});
