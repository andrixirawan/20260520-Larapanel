<?php

namespace App\Http\Controllers\Api\Mobile\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\DailyQuest\UpdateTaskInstanceNotesRequest;
use App\Http\Resources\DailyQuest\TaskInstanceResource;
use App\Jobs\DailyQuest\UpdateUserStatsJob;
use App\Models\DailyQuest\TaskInstance;
use App\Services\DailyQuest\TaskSchedulerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskInstanceController extends Controller
{
    public function __construct(
        private readonly TaskSchedulerService $taskSchedulerService,
    ) {}

    public function complete(Request $request, string $instance): JsonResponse
    {
        $record = $this->resolveOwnedInstanceForToday($request, $instance);

        DB::transaction(function () use ($request, $record): void {
            if ($record->completed_at !== null) {
                return;
            }

            $record->forceFill([
                'completed_at' => now($request->user()->timezone),
                'points_awarded' => $record->task?->points ?? 0,
            ])->save();
        });

        UpdateUserStatsJob::dispatchSync(
            $request->user()->id,
            $record->scheduled_date?->toDateString(),
        );

        return response()->json([
            'message' => 'Task completed.',
            'data' => TaskInstanceResource::make($record->refresh()->load('task.category')),
        ]);
    }

    public function uncomplete(Request $request, string $instance): JsonResponse
    {
        $record = $this->resolveOwnedInstanceForToday($request, $instance);

        DB::transaction(function () use ($record): void {
            if ($record->completed_at === null) {
                return;
            }

            $record->forceFill([
                'completed_at' => null,
                'points_awarded' => null,
            ])->save();
        });

        UpdateUserStatsJob::dispatchSync(
            $request->user()->id,
            $record->scheduled_date?->toDateString(),
        );

        return response()->json([
            'message' => 'Task marked as incomplete.',
            'data' => TaskInstanceResource::make($record->refresh()->load('task.category')),
        ]);
    }

    public function updateNotes(UpdateTaskInstanceNotesRequest $request, string $instance): JsonResponse
    {
        $record = $this->resolveOwnedInstanceForToday($request, $instance);
        $record->update([
            'notes' => $request->validated('notes'),
        ]);

        return response()->json([
            'message' => 'Task notes updated.',
            'data' => TaskInstanceResource::make($record->refresh()->load('task.category')),
        ]);
    }

    private function resolveOwnedInstanceForToday(Request $request, string $identifier): TaskInstance
    {
        $today = now($request->user()->timezone)->startOfDay();
        $taskId = $request->string('task_id')->toString();

        $instance = $this->findOwnedInstanceForDate(
            $request,
            $identifier,
            $taskId,
            $today->toDateString(),
        );

        if (! $instance && $taskId !== '') {
            $this->taskSchedulerService->generateForDate($request->user(), $today);

            $instance = $this->findOwnedInstanceForDate(
                $request,
                $identifier,
                $taskId,
                $today->toDateString(),
            );
        }

        abort_unless($instance instanceof TaskInstance, 404);

        return $instance;
    }

    private function findOwnedInstanceForDate(
        Request $request,
        string $identifier,
        string $taskId,
        string $scheduledDate,
    ): ?TaskInstance {
        return $request->user()
            ->taskInstances()
            ->whereDate('scheduled_date', $scheduledDate)
            ->where(function ($query) use ($identifier, $taskId): void {
                $query->whereKey($identifier)
                    ->orWhere('task_id', $identifier);

                if ($taskId !== '') {
                    $query->orWhere('task_id', $taskId);
                }
            })
            ->with('task.category')
            ->first();
    }
}
