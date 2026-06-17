<?php

namespace App\Http\Controllers\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyQuest\StoreTaskCategoryRequest;
use App\Http\Resources\DailyQuest\TaskCategoryResource;
use App\Models\DailyQuest\TaskCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = $request->user()
            ->categories()
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        return Inertia::render('daily-quest/categories/index', [
            'categories' => TaskCategoryResource::collection($categories)->resolve(),
        ]);
    }

    public function store(StoreTaskCategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    public function update(StoreTaskCategoryRequest $request, string $category): RedirectResponse
    {
        $category = $this->resolveOwnedCategory($request, $category);

        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    public function destroy(Request $request, string $category): RedirectResponse
    {
        $category = $this->resolveOwnedCategory($request, $category);

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }

    private function resolveOwnedCategory(Request $request, string $identifier): TaskCategory
    {
        $category = $request->user()
            ->categories()
            ->whereKey($identifier)
            ->first();

        abort_unless($category instanceof TaskCategory, 404);

        return $category;
    }
}
