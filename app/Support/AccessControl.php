<?php

namespace App\Support;

final class AccessControl
{
    public const ROLE_SUPER_ADMIN = 'super-admin';

    public const ROLE_ADMINISTRATOR = 'administrator';

    public const ROLE_CASHIER = 'cashier';

    public const ROLE_SUBSCRIBER = 'subscriber';

    public const PERMISSION_USERS_MANAGE = 'users.manage';

    public const PERMISSION_POSTS_VIEW = 'posts.view';

    public const PERMISSION_POSTS_CREATE = 'posts.create';

    public const PERMISSION_POSTS_UPDATE = 'posts.update';

    public const PERMISSION_POSTS_DELETE = 'posts.delete';

    /**
     * @return array<int, string>
     */
    public static function userPermissions(): array
    {
        return [
            self::PERMISSION_USERS_MANAGE,
        ];
    }

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

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMINISTRATOR,
            self::ROLE_CASHIER,
            self::ROLE_SUBSCRIBER,
        ];
    }
}
