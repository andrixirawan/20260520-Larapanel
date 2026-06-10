<?php

namespace App\Support;

final class AccessControl
{
    public const ROLE_SUPER_ADMIN = 'super-admin';
    public const ROLE_ADMINISTRATOR = 'administrator';
    public const ROLE_SUBSCRIBER = 'subscriber';

    public const PERMISSION_POSTS_VIEW = 'posts.view';
    public const PERMISSION_POSTS_CREATE = 'posts.create';
    public const PERMISSION_POSTS_UPDATE = 'posts.update';
    public const PERMISSION_POSTS_DELETE = 'posts.delete';

    /**
     * @return array<int, string>
     */
    public static function postPermissions(): array
    {
        return [
            self::PERMISSION_POSTS_VIEW,
            self::PERMISSION_POSTS_CREATE,
            self::PERMISSION_POSTS_UPDATE,
            self::PERMISSION_POSTS_DELETE,
        ];
    }
}
