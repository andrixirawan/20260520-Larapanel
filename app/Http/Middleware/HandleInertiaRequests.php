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
                    AccessControl::PERMISSION_POSTS_VIEW => $request->user()?->can(AccessControl::PERMISSION_POSTS_VIEW) ?? false,
                    AccessControl::PERMISSION_POSTS_CREATE => $request->user()?->can(AccessControl::PERMISSION_POSTS_CREATE) ?? false,
                    AccessControl::PERMISSION_POSTS_UPDATE => $request->user()?->can(AccessControl::PERMISSION_POSTS_UPDATE) ?? false,
                    AccessControl::PERMISSION_POSTS_DELETE => $request->user()?->can(AccessControl::PERMISSION_POSTS_DELETE) ?? false,
                ],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
