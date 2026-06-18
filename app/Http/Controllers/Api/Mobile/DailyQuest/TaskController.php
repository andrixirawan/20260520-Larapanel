<?php

namespace App\Http\Controllers\Api\Mobile\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyQuest\StoreTaskRequest;
use App\Http\Resources\DailyQuest\TaskCategoryResource;
use App\Http\Resources\DailyQuest\TaskInstanceResource;
use App\Http\Resources\DailyQuest\TaskResource;
use App\Models\DailyQuest\Task;
use App\Services\DailyQuest\TaskSchedulerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskSchedulerService $taskSchedulerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString() ?: 'active';
        $search = $request->string('search')->toString();

        $query = $request->user()
            ->tasks()
            ->withTrashed()
            ->with('category')
            ->orderByDesc('created_at');

        $query = match ($status) {
            'archived' => $query->onlyTrashed(),
            'paused' => $query->where('is_active', false)->whereNull('deleted_at'),
            'all' => $query,
            default => $query->where('is_active', true)->whereNull('deleted_at'),
        };

        if ($search !== '') {
            $query->where(fn (Builder $builder): Builder => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        return response()->json([
            'data' => TaskResource::collection($query->get())->resolve(),
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'paused', 'label' => 'Paused'],
                ['value' => 'archived', 'label' => 'Archived'],
                ['value' => 'all', 'label' => 'All'],
            ],
            'categories' => TaskCategoryResource::collection(
                $request->user()->categories()->orderBy('name')->get(),
            )->resolve(),
            'recurrence_types' => $this->recurrenceTypes(),
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->taskAttributes());

        $this->taskSchedulerService->generateForDate(
            $request->user(),
            now($request->user()->timezone)->startOfDay(),
        );

        return response()->json([
            'message' => 'Task created.',
            'data' => TaskResource::make($task->load('category')),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, string $task): JsonResponse
    {
        $record = $this->resolveOwnedTask($request, $task);
        $record->load([
            'category',
            'instances' => fn ($query) => $query->latest('scheduled_date')->limit(14),
        ]);

        return response()->json([
            'data' => TaskResource::make($record),
            'recent_instances' => TaskInstanceResource::collection($record->instances)->resolve(),
            'categories' => TaskCategoryResource::collection(
                $request->user()->categories()->orderBy('name')->get(),
            )->resolve(),
            'recurrence_types' => $this->recurrenceTypes(),
        ]);
    }

    public function update(StoreTaskRequest $request, string $task): JsonResponse
    {
        $record = $this->resolveOwnedTask($request, $task);
        $attributes = $request->taskAttributes();

        if (! $request->has('is_active')) {
            $attributes['is_active'] = $record->is_active;
        }

        $record->update($attributes);

        return response()->json([
            'message' => 'Task updated.',
            'data' => TaskResource::make($record->refresh()->load('category')),
        ]);
    }

    public function destroy(Request $request, string $task): JsonResponse
    {
        $record = $this->resolveOwnedTask($request, $task);
        $record->delete();

        return response()->json([
            'message' => 'Task archived.',
        ]);
    }

    public function pause(Request $request, string $task): JsonResponse
    {
        $record = $this->resolveOwnedTask($request, $task);
        abort_if($record->trashed(), 404);

        $record->update([
            'is_active' => ! $record->is_active,
        ]);

        return response()->json([
            'message' => $record->fresh()->is_active ? 'Task resumed.' : 'Task paused.',
            'data' => TaskResource::make($record->refresh()->load('category')),
        ]);
    }

    public function duplicate(Request $request, string $task): JsonResponse
    {
        $record = $this->resolveOwnedTask($request, $task);
        $duplicate = $record->replicate([
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $duplicate->name = __(':name (Copy)', ['name' => $record->name]);
        $duplicate->is_active = true;
        $duplicate->deleted_at = null;
        $duplicate->save();

        return response()->json([
            'message' => 'Task duplicated.',
            'data' => TaskResource::make($duplicate->load('category')),
        ], Response::HTTP_CREATED);
    }

    private function resolveOwnedTask(Request $request, string $identifier): Task
    {
        $task = $request->user()
            ->tasks()
            ->withTrashed()
            ->whereKey($identifier)
            ->first();

        abort_unless($task instanceof Task, 404);

        return $task;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function recurrenceTypes(): array
    {
        return [
            ['value' => 'daily', 'label' => 'Daily'],
            ['value' => 'specific_days', 'label' => 'Specific days'],
            ['value' => 'one_time', 'label' => 'One time'],
            ['value' => 'x_days', 'label' => 'X days'],
            ['value' => 'date_range', 'label' => 'Date range'],
        ];
    }
}
