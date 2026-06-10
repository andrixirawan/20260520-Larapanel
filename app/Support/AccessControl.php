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

    public const PERMISSION_POS_PRODUCTS_VIEW = 'pos.products.view';

    public const PERMISSION_POS_PRODUCTS_MANAGE = 'pos.products.manage';

    public const PERMISSION_POS_INVENTORY_VIEW = 'pos.inventory.view';

    public const PERMISSION_POS_INVENTORY_MANAGE = 'pos.inventory.manage';

    public const PERMISSION_POS_SHIFTS_VIEW = 'pos.shifts.view';

    public const PERMISSION_POS_SHIFTS_OPEN = 'pos.shifts.open';

    public const PERMISSION_POS_SHIFTS_CLOSE = 'pos.shifts.close';

    public const PERMISSION_POS_SHIFTS_MANAGE = 'pos.shifts.manage';

    public const PERMISSION_POS_SALES_VIEW = 'pos.sales.view';

    public const PERMISSION_POS_SALES_CREATE = 'pos.sales.create';

    public const PERMISSION_POS_SALES_VOID = 'pos.sales.void';

    public const PERMISSION_POS_FINANCE_VIEW = 'pos.finance.view';

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
    public static function posPermissions(): array
    {
        return [
            self::PERMISSION_POS_PRODUCTS_VIEW,
            self::PERMISSION_POS_PRODUCTS_MANAGE,
            self::PERMISSION_POS_INVENTORY_VIEW,
            self::PERMISSION_POS_INVENTORY_MANAGE,
            self::PERMISSION_POS_SHIFTS_VIEW,
            self::PERMISSION_POS_SHIFTS_OPEN,
            self::PERMISSION_POS_SHIFTS_CLOSE,
            self::PERMISSION_POS_SHIFTS_MANAGE,
            self::PERMISSION_POS_SALES_VIEW,
            self::PERMISSION_POS_SALES_CREATE,
            self::PERMISSION_POS_SALES_VOID,
            self::PERMISSION_POS_FINANCE_VIEW,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function cashierPosPermissions(): array
    {
        return [
            self::PERMISSION_POS_PRODUCTS_VIEW,
            self::PERMISSION_POS_INVENTORY_VIEW,
            self::PERMISSION_POS_SHIFTS_VIEW,
            self::PERMISSION_POS_SHIFTS_OPEN,
            self::PERMISSION_POS_SHIFTS_CLOSE,
            self::PERMISSION_POS_SALES_VIEW,
            self::PERMISSION_POS_SALES_CREATE,
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
