<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyQuest\StoreTaskRequest;
use App\Http\Requests\DailyQuest\UpdateTaskRequest;
use App\Http\Resources\DailyQuest\TaskCategoryResource;
use App\Http\Resources\DailyQuest\TaskInstanceResource;
use App\Http\Resources\DailyQuest\TaskResource;
use App\Models\DailyQuest\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString() ?: 'active';
        $search = $request->string('search')->toString();

        $taskQuery = $request->user()
            ->tasks()
            ->withTrashed()
            ->with('category')
            ->orderByDesc('created_at');

        $taskQuery = match ($status) {
            'archived' => $taskQuery->onlyTrashed(),
            'paused' => $taskQuery->where('is_active', false)->whereNull('deleted_at'),
            'all' => $taskQuery,
            default => $taskQuery->where('is_active', true)->whereNull('deleted_at'),
        };

        if ($search !== '') {
            $taskQuery->where(fn (Builder $query): Builder => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        return Inertia::render('daily-quest/tasks/index', [
            'tasks' => TaskResource::collection($taskQuery->get())->resolve(),
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'categories' => TaskCategoryResource::collection(
                $request->user()->categories()->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('daily-quest/tasks/create', [
            'categories' => TaskCategoryResource::collection(
                $request->user()->categories()->orderBy('name')->get(),
            )->resolve(),
            'recurrence_types' => $this->recurrenceTypes(),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $request->user()->tasks()->create($request->taskAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return $this->redirectAfterMutation($request);
    }

    public function show(Request $request, Task $task): Response
    {
        $this->ensureOwnedTask($request, $task);

        $task->load(['category', 'instances' => fn ($query) => $query->latest('scheduled_date')->limit(14)]);

        return Inertia::render('daily-quest/tasks/show', [
            'task' => TaskResource::make($task)->resolve(),
            'recent_instances' => TaskInstanceResource::collection($task->instances)->resolve(),
        ]);
    }

    public function edit(Request $request, Task $task): Response
    {
        $this->ensureOwnedTask($request, $task);
        $task->load('category');

        return Inertia::render('daily-quest/tasks/edit', [
            'task' => TaskResource::make($task)->resolve(),
            'categories' => TaskCategoryResource::collection(
                $request->user()->categories()->orderBy('name')->get(),
            )->resolve(),
            'recurrence_types' => $this->recurrenceTypes(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->ensureOwnedTask($request, $task);

        $task->update($request->taskAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return $this->redirectAfterMutation($request);
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->ensureOwnedTask($request, $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task archived.')]);

        return $this->redirectAfterMutation($request);
    }

    public function pause(Request $request, Task $task): RedirectResponse
    {
        $this->ensureOwnedTask($request, $task);

        abort_if($task->trashed(), 404);

        $task->update([
            'is_active' => ! $task->is_active,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $task->is_active
                ? __('Task resumed.')
                : __('Task paused.'),
        ]);

        return $this->redirectAfterMutation($request);
    }

    public function duplicate(Request $request, Task $task): RedirectResponse
    {
        $this->ensureOwnedTask($request, $task);

        $task->loadMissing('category');

        $duplicate = $task->replicate([
            'public_id',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $duplicate->public_id = (string) Str::ulid();
        $duplicate->name = __(':name (Copy)', ['name' => $task->name]);
        $duplicate->is_active = true;
        $duplicate->deleted_at = null;
        $duplicate->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task duplicated.')]);

        return $this->redirectAfterMutation($request);
    }

    private function ensureOwnedTask(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }

    private function redirectAfterMutation(Request $request): RedirectResponse
    {
        $redirectTo = $request->string('redirect_to')->toString();

        if (
            $redirectTo !== '' &&
            Str::startsWith($redirectTo, '/') &&
            ! Str::startsWith($redirectTo, '//')
        ) {
            return redirect()->to($redirectTo);
        }

        return to_route('tasks.index');
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
