<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskCategory;
use App\Support\DailyQuestPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'tasks' => $taskQuery
                ->get()
                ->map(fn (Task $task): array => DailyQuestPayload::task($task)),
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'categories' => $request->user()
                ->categories()
                ->orderBy('name')
                ->get()
                ->map(fn (TaskCategory $category): array => DailyQuestPayload::category($category)),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('daily-quest/tasks/create', [
            'categories' => $request->user()
                ->categories()
                ->orderBy('name')
                ->get()
                ->map(fn (TaskCategory $category): array => DailyQuestPayload::category($category)),
            'recurrence_types' => $this->recurrenceTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTask($request);

        $request->user()->tasks()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('tasks.index');
    }

    public function show(Request $request, Task $task): Response
    {
        $this->ensureOwnedTask($request, $task);

        $task->load(['category', 'instances' => fn ($query) => $query->latest('scheduled_date')->limit(14)]);

        return Inertia::render('daily-quest/tasks/show', [
            'task' => DailyQuestPayload::task($task),
            'recent_instances' => $task->instances->map(fn ($instance): array => DailyQuestPayload::taskInstance($instance)),
        ]);
    }

    public function edit(Request $request, Task $task): Response
    {
        $this->ensureOwnedTask($request, $task);
        $task->load('category');

        return Inertia::render('daily-quest/tasks/edit', [
            'task' => DailyQuestPayload::task($task),
            'categories' => $request->user()
                ->categories()
                ->orderBy('name')
                ->get()
                ->map(fn (TaskCategory $category): array => DailyQuestPayload::category($category)),
            'recurrence_types' => $this->recurrenceTypes(),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->ensureOwnedTask($request, $task);

        $task->update($this->validateTask($request, $task));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return to_route('tasks.index');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->ensureOwnedTask($request, $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task archived.')]);

        return to_route('tasks.index');
    }

    private function ensureOwnedTask(Request $request, Task $task): void
    {
        abort_unless($task->user_id === $request->user()->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTask(Request $request, ?Task $task = null): array
    {
        $validated = $request->validate([
            'category_public_id' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:24'],
            'points' => ['required', 'integer', 'min:0', 'max:100000'],
            'recurrence_type' => ['required', 'string', 'in:daily,specific_days,one_time,x_days,date_range'],
            'recurrence_days' => ['nullable', 'array'],
            'recurrence_days.*' => ['string', 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun'],
            'recurrence_starts_at' => ['nullable', 'date'],
            'recurrence_ends_at' => ['nullable', 'date', 'after_or_equal:recurrence_starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = null;

        if ($request->filled('category_public_id')) {
            $category = $request->user()
                ->categories()
                ->where('public_id', $request->string('category_public_id')->toString())
                ->firstOrFail();
        }

        $validated['category_id'] = $category?->id;
        $validated['is_active'] = $validated['is_active'] ?? $task?->is_active ?? true;

        unset($validated['category_public_id']);

        return $validated;
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
