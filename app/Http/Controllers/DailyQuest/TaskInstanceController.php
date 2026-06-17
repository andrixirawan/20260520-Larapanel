<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Jobs\DailyQuest\UpdateUserStatsJob;
use App\Models\DailyQuest\TaskInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TaskInstanceController extends Controller
{
    public function complete(Request $request, string $instance): RedirectResponse
    {
        $instance = $this->resolveOwnedInstance($request, $instance);

        $this->ensureOwnedAndMutable($request, $instance);

        DB::transaction(function () use ($request, $instance): void {
            if ($instance->completed_at !== null) {
                return;
            }

            $instance->forceFill([
                'completed_at' => now($request->user()->timezone),
                'points_awarded' => $instance->task?->points ?? 0,
            ])->save();
        });

        UpdateUserStatsJob::dispatchSync(
            $request->user()->id,
            $instance->scheduled_date?->toDateString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task completed.')]);

        return back();
    }

    public function uncomplete(Request $request, string $instance): RedirectResponse
    {
        $instance = $this->resolveOwnedInstance($request, $instance);

        $this->ensureOwnedAndMutable($request, $instance);

        DB::transaction(function () use ($instance): void {
            if ($instance->completed_at === null) {
                return;
            }

            $instance->forceFill([
                'completed_at' => null,
                'points_awarded' => null,
            ])->save();
        });

        UpdateUserStatsJob::dispatchSync(
            $request->user()->id,
            $instance->scheduled_date?->toDateString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task marked as incomplete.')]);

        return back();
    }

    private function resolveOwnedInstance(Request $request, string $identifier): TaskInstance
    {
        $today = now($request->user()->timezone)->toDateString();
        $taskId = $request->string('task_id')->toString();

        $instance = $request->user()
            ->taskInstances()
            ->where(function ($query) use ($identifier, $taskId, $today): void {
                $query
                    ->whereKey($identifier)
                    ->orWhere(function ($query) use ($identifier, $today): void {
                        $query
                            ->where('task_id', $identifier)
                            ->whereDate('scheduled_date', $today);
                    });

                if ($taskId !== '') {
                    $query->orWhere(function ($query) use ($taskId, $today): void {
                        $query
                            ->where('task_id', $taskId)
                            ->whereDate('scheduled_date', $today);
                    });
                }
            })
            ->first();

        abort_unless($instance instanceof TaskInstance, 404);

        return $instance;
    }

    private function ensureOwnedAndMutable(Request $request, TaskInstance $instance): void
    {
        abort_unless($instance->user_id === $request->user()->id, 404);

        $today = now($request->user()->timezone)->toDateString();

        abort_unless($instance->scheduled_date?->toDateString() === $today, 422);
    }
}
