<?php

namespace App\Http\Controllers\Api\Post;

use App\Http\Controllers\Controller;
use App\Http\Resources\Post\PostResource;
use App\Models\Post\Post;
use App\Queries\Post\PostIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicPostController extends Controller
{
    public function __construct(
        private readonly PostIndexQuery $postIndexQuery,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = $this->postIndexQuery->paginateForMobile($request);

        return PostResource::collection($posts)
            ->additional([
                'filters' => $this->postIndexQuery->mobileFilters($request),
                'sort_options' => $this->postIndexQuery->mobileSortOptions(),
            ]);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'data' => PostResource::make($post),
        ]);
    }
}
