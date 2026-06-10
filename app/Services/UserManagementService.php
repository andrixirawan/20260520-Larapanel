<?php

namespace App\Services;

use App\Models\User;
use App\Support\AccessControl;
use Illuminate\Auth\Access\AuthorizationException;

class UserManagementService
{
    /**
     * @param  array{name: string, email: string, role: string}  $attributes
     *
     * @throws AuthorizationException
     */
    public function update(User $actor, User $managedUser, array $attributes): void
    {
        $this->assertCanManage($actor, $managedUser, $attributes['role']);

        $managedUser->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
        ]);

        if ($managedUser->isDirty('email')) {
            $managedUser->email_verified_at = null;
        }

        $managedUser->save();
        $managedUser->syncRoles([$attributes['role']]);
    }

    /**
     * Prevent administrators from escalating privileges into super-admin access.
     *
     * @throws AuthorizationException
     */
    private function assertCanManage(User $actor, User $managedUser, string $role): void
    {
        if ($actor->hasRole(AccessControl::ROLE_SUPER_ADMIN)) {
            return;
        }

        if ($managedUser->hasRole(AccessControl::ROLE_SUPER_ADMIN) || $role === AccessControl::ROLE_SUPER_ADMIN) {
            throw new AuthorizationException('Only super admins can manage super admin accounts.');
        }
    }
}
