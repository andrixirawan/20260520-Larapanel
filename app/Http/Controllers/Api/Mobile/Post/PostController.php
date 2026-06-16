<?php

namespace App\Http\Controllers\Api\Mobile\Post;

use App\Actions\Post\CreatePostAction;
use App\Actions\Post\DeletePostAction;
use App\Actions\Post\UpdatePostAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post\Post;
use App\Queries\Post\PostIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function __construct(
        private readonly PostIndexQuery $postIndexQuery,
        private readonly CreatePostAction $createPostAction,
        private readonly UpdatePostAction $updatePostAction,
        private readonly DeletePostAction $deletePostAction,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $scope = $this->postIndexQuery->mobileScope($request);
        $owner = $scope === 'mine' ? $request->user() : null;
        $posts = $this->postIndexQuery->paginateForMobile($request, $owner);

        return $this->indexResponse($request, $posts, $scope);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->createPostAction->handle($request, $request->validated());

        return response()->json([
            'message' => 'Post created.',
            'data' => PostResource::make($post),
        ], Response::HTTP_CREATED);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'data' => PostResource::make($post),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $post = $this->updatePostAction->handle($request, $post, $request->validated());

        return response()->json([
            'message' => 'Post updated.',
            'data' => PostResource::make($post),
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->deletePostAction->handle($post);

        return response()->json([
            'message' => 'Post deleted.',
        ]);
    }

    private function indexResponse(
        Request $request,
        AnonymousResourceCollection|\Illuminate\Pagination\LengthAwarePaginator $posts,
        string $scope,
    ): AnonymousResourceCollection {
        return PostResource::collection($posts)
            ->additional([
                'filters' => $this->postIndexQuery->mobileFilters($request),
                'sort_options' => $this->postIndexQuery->mobileSortOptions(),
                'scope' => $scope,
            ]);
    }
}
