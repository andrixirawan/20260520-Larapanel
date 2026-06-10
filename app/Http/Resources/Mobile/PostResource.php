<?php

namespace App\Http\Resources\Mobile;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'cover' => $this->cover,
            'cover_url' => $this->cover ? route('posts.cover', $this->resource) : null,
            'body' => $this->body,
            'author' => $this->author,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
