<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(): Response
    {
        return Inertia::render('posts/index', [
            'posts' => Post::query()
                ->latest()
                ->paginate(10)
                ->withQueryString(),
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
            'post' => $post,
        ]);
    }

    public function edit(Post $post): Response
    {
        return Inertia::render('posts/edit', [
            'post' => $post,
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
            'author' => ['required', 'string', 'max:255'],
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
