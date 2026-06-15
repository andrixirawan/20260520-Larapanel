<?php

namespace App\Data\Post;

use App\Models\Post\Post;

final class PostData
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Post $post): array
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
}
