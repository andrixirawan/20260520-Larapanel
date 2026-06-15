<?php

namespace App\Policies\Post;

use App\Models\Post\Post;
use App\Models\User;
use App\Support\AccessControl;

class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        return $user->can(AccessControl::PERMISSION_POSTS_VIEW)
            && $post->user_id === $user->id;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can(AccessControl::PERMISSION_POSTS_UPDATE)
            && $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can(AccessControl::PERMISSION_POSTS_DELETE)
            && $post->user_id === $user->id;
    }
}
