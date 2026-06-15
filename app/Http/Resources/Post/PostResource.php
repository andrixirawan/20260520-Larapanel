<?php

namespace App\Http\Resources\Post;

use App\Data\Post\PostData;
use App\Models\Post\Post;
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
        $data = PostData::fromModel($this->resource, $request->user());
        $data['cover_url'] = $this->cover ? route('posts.cover', $this->resource) : null;

        return $data;
    }
}
