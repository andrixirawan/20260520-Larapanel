<?php

namespace App\Actions\Post;

use App\Models\Post\Post;
use App\Services\Post\PostCoverService;
use App\Services\Post\PostSlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class CreatePostAction
{
    public function __construct(
        private readonly PostCoverService $postCoverService,
        private readonly PostSlugService $postSlugService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Request $request, array $validated): Post
    {
        $attributes = Arr::except($validated, 'remove_cover');
        $attributes['slug'] = $this->postSlugService->ensureUnique($attributes['slug'] ?: $attributes['title']);
        $attributes['user_id'] = $request->user()->id;
        $attributes['author'] = $request->user()->name;

        if ($request->hasFile('cover')) {
            $attributes['cover'] = $this->postCoverService->store($request);
        }

        return Post::query()->create($attributes);
    }
}
