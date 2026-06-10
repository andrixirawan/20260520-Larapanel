<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $this->filters($request);

        $posts = Post::query()
            ->when($filters['search'], function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->when($filters['author'], fn ($query, string $author) => $query->where('author', 'like', "%{$author}%"))
            ->when(
                $filters['sort'] === 'oldest',
                fn ($query) => $query->oldest(),
                fn ($query) => $query->when(
                    $filters['sort'] === 'title',
                    fn ($query) => $query->orderBy('title')->orderByDesc('id'),
                    fn ($query) => $query->when(
                        $filters['sort'] === 'author',
                        fn ($query) => $query->orderBy('author')->orderByDesc('id'),
                        fn ($query) => $query->latest(),
                    ),
                ),
            )
            ->paginate($filters['per_page'])
            ->withQueryString();

        return PostResource::collection($posts)
            ->additional([
                'filters' => $filters,
                'sort_options' => $this->sortOptions(),
            ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedPost($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title']);
        $validated['author'] = $request->user()->name;

        if ($request->hasFile('cover')) {
            $validated['cover'] = $this->storeCover($request);
        }

        $post = Post::create($validated);

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

    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $this->validatedPost($request, $post);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title'], $post);

        if ($request->boolean('remove_cover') || $request->hasFile('cover')) {
            $this->deleteCover($post);
            $validated['cover'] = null;
        }

        if ($request->hasFile('cover')) {
            $validated['cover'] = $this->storeCover($request);
        }

        unset($validated['remove_cover']);

        $post->update($validated);

        return response()->json([
            'message' => 'Post updated.',
            'data' => PostResource::make($post->refresh()),
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->deleteCover($post);
        $post->delete();

        return response()->json([
            'message' => 'Post deleted.',
        ]);
    }

    /**
     * @return array{search: string, author: string, sort: string, per_page: int}
     */
    private function filters(Request $request): array
    {
        $sort = $request->string('sort')->toString();
        $perPage = $request->integer('per_page', 10);

        return [
            'search' => trim($request->string('search')->toString()),
            'author' => trim($request->string('author')->toString()),
            'sort' => array_key_exists($sort, $this->sortOptions()) ? $sort : 'latest',
            'per_page' => in_array($perPage, [5, 10, 15, 25], true) ? $perPage : 10,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sortOptions(): array
    {
        return [
            'latest' => 'Newest first',
            'oldest' => 'Oldest first',
            'title' => 'Title A-Z',
            'author' => 'Author A-Z',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPost(Request $request, ?Post $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('posts', 'slug')->ignore($post),
            ],
            'cover' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('uploads.posts.mimes', ['jpg', 'jpeg', 'png', 'webp'])),
                'max:'.config('uploads.posts.max_size', 2048),
            ],
            'body' => ['required', 'string'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $value, ?Post $post = null): string
    {
        $slug = Str::slug($value) ?: Str::random(8);
        $baseSlug = $slug;
        $counter = 2;

        while (Post::query()
            ->where('slug', $slug)
            ->when($post, fn ($query) => $query->whereKeyNot($post->id))
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function storeCover(Request $request): string
    {
        $cover = $request->file('cover');

        if (! $cover) {
            throw ValidationException::withMessages([
                'cover' => 'The cover image could not be uploaded.',
            ]);
        }

        $storedCover = $cover->storeAs(
            trim(config('uploads.posts.directory', 'uploads/posts/covers'), '/'),
            $cover->hashName(),
            ['disk' => $this->coverDisk()],
        );

        if (! is_string($storedCover)) {
            throw ValidationException::withMessages([
                'cover' => 'The cover image could not be uploaded. Please check storage permissions.',
            ]);
        }

        return $storedCover;
    }

    private function deleteCover(Post $post): void
    {
        if ($post->getRawOriginal('cover')) {
            Storage::disk($this->coverDisk())->delete($post->getRawOriginal('cover'));
        }
    }

    private function coverDisk(): string
    {
        return config('uploads.posts.disk', 'public');
    }
}
