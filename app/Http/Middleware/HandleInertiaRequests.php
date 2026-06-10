<?php

namespace App\Http\Middleware;

use App\Support\AccessControl;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => fn () => $request->user()?->getRoleNames()->values() ?? [],
                'permissions' => fn () => [
                    AccessControl::PERMISSION_USERS_MANAGE => $request->user()?->can(AccessControl::PERMISSION_USERS_MANAGE) ?? false,
                    AccessControl::PERMISSION_POSTS_VIEW => $request->user()?->can(AccessControl::PERMISSION_POSTS_VIEW) ?? false,
                    AccessControl::PERMISSION_POSTS_CREATE => $request->user()?->can(AccessControl::PERMISSION_POSTS_CREATE) ?? false,
                    AccessControl::PERMISSION_POSTS_UPDATE => $request->user()?->can(AccessControl::PERMISSION_POSTS_UPDATE) ?? false,
                    AccessControl::PERMISSION_POSTS_DELETE => $request->user()?->can(AccessControl::PERMISSION_POSTS_DELETE) ?? false,
                    AccessControl::PERMISSION_POS_PRODUCTS_VIEW => $request->user()?->can(AccessControl::PERMISSION_POS_PRODUCTS_VIEW) ?? false,
                    AccessControl::PERMISSION_POS_PRODUCTS_MANAGE => $request->user()?->can(AccessControl::PERMISSION_POS_PRODUCTS_MANAGE) ?? false,
                    AccessControl::PERMISSION_POS_INVENTORY_VIEW => $request->user()?->can(AccessControl::PERMISSION_POS_INVENTORY_VIEW) ?? false,
                    AccessControl::PERMISSION_POS_INVENTORY_MANAGE => $request->user()?->can(AccessControl::PERMISSION_POS_INVENTORY_MANAGE) ?? false,
                    AccessControl::PERMISSION_POS_SHIFTS_VIEW => $request->user()?->can(AccessControl::PERMISSION_POS_SHIFTS_VIEW) ?? false,
                    AccessControl::PERMISSION_POS_SHIFTS_OPEN => $request->user()?->can(AccessControl::PERMISSION_POS_SHIFTS_OPEN) ?? false,
                    AccessControl::PERMISSION_POS_SHIFTS_CLOSE => $request->user()?->can(AccessControl::PERMISSION_POS_SHIFTS_CLOSE) ?? false,
                    AccessControl::PERMISSION_POS_SHIFTS_MANAGE => $request->user()?->can(AccessControl::PERMISSION_POS_SHIFTS_MANAGE) ?? false,
                    AccessControl::PERMISSION_POS_SALES_VIEW => $request->user()?->can(AccessControl::PERMISSION_POS_SALES_VIEW) ?? false,
                    AccessControl::PERMISSION_POS_SALES_CREATE => $request->user()?->can(AccessControl::PERMISSION_POS_SALES_CREATE) ?? false,
                    AccessControl::PERMISSION_POS_SALES_VOID => $request->user()?->can(AccessControl::PERMISSION_POS_SALES_VOID) ?? false,
                    AccessControl::PERMISSION_POS_FINANCE_VIEW => $request->user()?->can(AccessControl::PERMISSION_POS_FINANCE_VIEW) ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
