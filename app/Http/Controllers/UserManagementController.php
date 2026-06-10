<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use App\Support\AccessControl;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function index(Request $request): Response
    {
        $search = TableQuery::search($request);
        $sortOptions = [
            'name' => 'name',
            'email' => 'email',
            'created_at' => 'created_at',
        ];
        $sort = TableQuery::sort($request, $sortOptions, 'name');
        $direction = TableQuery::direction($request, 'asc');
        $perPage = TableQuery::perPage($request);

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortOptions[$sort], $direction)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'public_id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'roles' => $user->getRoleNames()->values()->all(),
            ]);

        return Inertia::render('users/index', [
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'roles' => AccessControl::roles(),
            'users' => $users,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userManagementService->update(
            $request->user(),
            $user,
            $request->validated(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return back();
    }
}
