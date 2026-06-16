<?php

namespace App\Actions\Post;

use App\Models\Post\Post;
use App\Services\Post\PostCoverService;
use App\Services\Post\PostSlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class UpdatePostAction
{
    public function __construct(
        private readonly PostCoverService $postCoverService,
        private readonly PostSlugService $postSlugService,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Request $request, Post $post, array $validated): Post
    {
        $attributes = Arr::except($validated, 'remove_cover');
        $slugSource = filled($attributes['slug'] ?? null) ? $attributes['slug'] : $attributes['title'];
        $attributes['slug'] = $this->postSlugService->ensureUnique($slugSource, $post);

        if ($request->boolean('remove_cover') || $request->hasFile('cover')) {
            $this->postCoverService->deleteForPost($post);
            $attributes['cover'] = null;
        }

        if ($request->hasFile('cover')) {
            $attributes['cover'] = $this->postCoverService->store($request);
        }

        $post->update($attributes);

        return $post->refresh();
    }
}
