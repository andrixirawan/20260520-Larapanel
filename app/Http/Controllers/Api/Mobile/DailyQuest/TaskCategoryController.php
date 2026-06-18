<?php

namespace App\Http\Controllers\Api\Mobile\DailyQuest;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyQuest\StoreTaskCategoryRequest;
use App\Http\Resources\DailyQuest\TaskCategoryResource;
use App\Models\DailyQuest\TaskCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = $request->user()
            ->categories()
            ->withCount('tasks')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => TaskCategoryResource::collection($categories)->resolve(),
        ]);
    }

    public function store(StoreTaskCategoryRequest $request): JsonResponse
    {
        $category = $request->user()->categories()->create($request->validated());

        return response()->json([
            'message' => 'Category created.',
            'data' => TaskCategoryResource::make($category->loadCount('tasks')),
        ], Response::HTTP_CREATED);
    }

    public function update(StoreTaskCategoryRequest $request, string $category): JsonResponse
    {
        $record = $this->resolveOwnedCategory($request, $category);
        $record->update($request->validated());

        return response()->json([
            'message' => 'Category updated.',
            'data' => TaskCategoryResource::make($record->refresh()->loadCount('tasks')),
        ]);
    }

    public function destroy(Request $request, string $category): JsonResponse
    {
        $record = $this->resolveOwnedCategory($request, $category);
        $record->delete();

        return response()->json([
            'message' => 'Category deleted.',
        ]);
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
