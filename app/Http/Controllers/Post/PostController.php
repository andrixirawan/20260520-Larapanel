<?php

namespace App\Http\Controllers\Post;

use App\Actions\Post\CreatePostAction;
use App\Actions\Post\DeletePostAction;
use App\Actions\Post\UpdatePostAction;
use App\Data\Post\PostData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post\Post;
use App\Queries\Post\PostIndexQuery;
use App\Services\Post\PostCoverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController extends Controller
{
    public function __construct(
        private readonly PostIndexQuery $postIndexQuery,
        private readonly CreatePostAction $createPostAction,
        private readonly UpdatePostAction $updatePostAction,
        private readonly DeletePostAction $deletePostAction,
        private readonly PostCoverService $postCoverService,
    ) {}

    public function home(Request $request): Response
    {
        return Inertia::render('welcome', [
            'posts' => $this->postIndexQuery
                ->paginateForWeb($request)
                ->through(fn (Post $post): array => PostData::fromModel($post)),
            'filters' => $this->postIndexQuery->webFilters($request),
            'sortOptions' => $this->postIndexQuery->webSortOptions(),
        ]);
    }

    public function publicShow(Post $post): Response
    {
        return Inertia::render('public-posts/show', [
            'post' => PostData::fromModel($post),
        ]);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('posts/index', [
            'posts' => $this->postIndexQuery
                ->paginateForWeb($request)
                ->through(fn (Post $post): array => PostData::fromModel($post)),
            'filters' => $this->postIndexQuery->webFilters($request),
            'sortOptions' => $this->postIndexQuery->webSortOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('posts/create');
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->createPostAction->handle($request, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post created.')]);

        return to_route('posts.index');
    }

    public function show(Post $post): Response
    {
        return Inertia::render('posts/show', [
            'post' => PostData::fromModel($post),
        ]);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('posts/edit', [
            'post' => PostData::fromModel($post),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->updatePostAction->handle($request, $post, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('posts.index');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->deletePostAction->handle($post);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post deleted.')]);

        return to_route('posts.index');
    }

    public function cover(Post $post): StreamedResponse
    {
        abort_unless($post->cover && $this->postCoverService->exists($post->cover), 404);

        return $this->postCoverService->response($post->cover);
    }
}
