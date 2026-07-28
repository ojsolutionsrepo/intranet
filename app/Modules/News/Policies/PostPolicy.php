<?php

namespace App\Modules\News\Policies;

use App\Models\User;
use App\Modules\News\Models\Post;

class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        if ($user->can('news.manage')) {
            return true;
        }

        return $user->can('news.view')
            && $post->status === Post::STATUS_PUBLISHED
            && $post->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can('news.publish') || $user->can('news.manage');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('news.manage') || ($user->can('news.publish') && $post->author_id === $user->id);
    }
}
