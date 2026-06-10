<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController extends Controller
{
    public function home(Request $request): Response
    {
        return Inertia::render('welcome', [
            'posts' => $this->filteredPosts($request),
            'filters' => $this->postFilters($request),
            'sortOptions' => $this->sortOptions(),
        ]);
    }

    public function publicShow(Post $post): Response
    {
        return Inertia::render('public-posts/show', [
            'post' => $this->postData($post),
        ]);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('posts/index', [
            'posts' => $this->filteredPosts($request),
            'filters' => $this->postFilters($request),
            'sortOptions' => $this->sortOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('posts/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = Arr::except($this->validatedPost($request), 'remove_cover');
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title']);
        $validated['author'] = $request->user()->name;

        if ($request->hasFile('cover')) {
            $validated['cover'] = $this->storeCover($request);
        }

        Post::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post created.')]);

        return to_route('posts.index');
    }

    public function show(Post $post): Response
    {
        return Inertia::render('posts/show', [
            'post' => $this->postData($post),
        ]);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('posts/edit', [
            'post' => $this->postData($post),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $validated = Arr::except($this->validatedPost($request, $post), 'remove_cover');
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title'], $post);

        if ($request->boolean('remove_cover') || $request->hasFile('cover')) {
            $this->deleteCover($post);
            $validated['cover'] = null;
        }

        if ($request->hasFile('cover')) {
            $validated['cover'] = $this->storeCover($request);
        }

        $post->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('posts.index');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->deleteCover($post);
        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post deleted.')]);

        return to_route('posts.index');
    }

    public function cover(Post $post): StreamedResponse
    {
        abort_unless($post->cover && Storage::disk($this->coverDisk())->exists($post->cover), 404);

        return Storage::disk($this->coverDisk())->response($post->cover);
    }

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

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    private function filteredPosts(Request $request): LengthAwarePaginator
    {
        $filters = $this->postFilters($request);
        $sortColumns = [
            'id' => 'id',
            'title' => 'title',
            'author' => 'author',
            'created_at' => 'created_at',
        ];

        return Post::query()
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
            ->orderBy($sortColumns[$filters['sort']], $filters['direction'])
            ->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->through(fn (Post $post): array => $this->postData($post))
            ->withQueryString();
    }

    /**
     * @return array{search: string, author: string, sort: string, direction: string, per_page: int}
     */
    private function postFilters(Request $request): array
    {
        $sortOptions = $this->sortOptions();

        return [
            'search' => TableQuery::search($request),
            'author' => trim($request->string('author')->toString()),
            'sort' => TableQuery::sort($request, $sortOptions, 'created_at'),
            'direction' => TableQuery::direction($request),
            'per_page' => TableQuery::perPage($request),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sortOptions(): array
    {
        return [
            'id' => __('ID'),
            'title' => __('Title'),
            'author' => __('Author'),
            'created_at' => __('Created at'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function postData(Post $post): array
    {
        return [
            'public_id' => $post->public_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'cover' => $post->cover,
            'cover_url' => $post->cover ? route('posts.cover', $post, false) : null,
            'body' => $post->body,
            'author' => $post->author,
            'created_at' => $post->created_at?->toISOString(),
            'updated_at' => $post->updated_at?->toISOString(),
        ];
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
                'cover' => __('The cover image could not be uploaded.'),
            ]);
        }

        $storedCover = $cover->storeAs(
            trim(config('uploads.posts.directory', 'uploads/posts/covers'), '/'),
            $cover->hashName(),
            ['disk' => $this->coverDisk()],
        );

        if (! is_string($storedCover)) {
            throw ValidationException::withMessages([
                'cover' => __('The cover image could not be uploaded. Please check storage permissions.'),
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
