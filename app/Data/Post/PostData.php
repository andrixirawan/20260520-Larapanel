<?php

namespace App\Data\Post;

use App\Models\Post\Post;
use App\Models\User;

final class PostData
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Post $post, ?User $user = null): array
    {
        return [
            'public_id' => $post->public_id,
            'title' => $post->title,
            'slug' => $post->slug,
            'cover' => $post->cover,
            'cover_url' => $post->cover ? route('posts.cover', $post, false) : null,
            'body' => $post->body,
            'author' => $post->author,
            'is_mine' => $user?->id !== null && $post->user_id === $user->id,
            'can_edit' => $user?->can('update', $post) ?? false,
            'can_delete' => $user?->can('delete', $post) ?? false,
            'created_at' => $post->created_at?->toISOString(),
            'updated_at' => $post->updated_at?->toISOString(),
        ];
    }
}
