<?php

namespace App\Actions\Post;

use App\Models\Post\Post;
use App\Services\Post\PostCoverService;

final class DeletePostAction
{
    public function __construct(
        private readonly PostCoverService $postCoverService,
    ) {}

    public function handle(Post $post): void
    {
        $this->postCoverService->deleteForPost($post);
        $post->delete();
    }
}
