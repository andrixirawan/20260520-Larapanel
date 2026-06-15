<?php

namespace App\Services\Post;

use App\Models\Post\Post;
use Illuminate\Support\Str;

final class PostSlugService
{
    public function ensureUnique(string $value, ?Post $post = null): string
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
}
