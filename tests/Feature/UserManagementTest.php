<?php

use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('roles seeder creates cashier role', function () {
    expect(Role::query()->where('name', AccessControl::ROLE_CASHIER)->exists())
        ->toBeTrue();
});

test('administrator can view user management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole(AccessControl::ROLE_ADMINISTRATOR);

    $managedUser = User::factory()->create([
        'name' => 'Cashier Candidate',
        'email' => 'cashier@example.com',
    ]);
    $managedUser->assignRole(AccessControl::ROLE_SUBSCRIBER);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/index')
            ->where('roles', AccessControl::roles())
            ->where('users.total', 2)
            ->has('users.data', 2)
        );
});

test('administrator can update regular user profile and role', function () {
    $admin = User::factory()->create();
    $admin->assignRole(AccessControl::ROLE_ADMINISTRATOR);

    $managedUser = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);
    $managedUser->assignRole(AccessControl::ROLE_SUBSCRIBER);

    $this->actingAs($admin)
        ->patch(route('users.update', $managedUser), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'role' => AccessControl::ROLE_CASHIER,
        ])
        ->assertRedirect();

    $managedUser->refresh();

    expect($managedUser->name)->toBe('New Name')
        ->and($managedUser->email)->toBe('new@example.com')
        ->and($managedUser->email_verified_at)->toBeNull()
        ->and($managedUser->hasRole(AccessControl::ROLE_CASHIER))->toBeTrue();
});

test('administrator cannot promote user into super admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole(AccessControl::ROLE_ADMINISTRATOR);

    $managedUser = User::factory()->create();
    $managedUser->assignRole(AccessControl::ROLE_SUBSCRIBER);

    $this->actingAs($admin)
        ->patch(route('users.update', $managedUser), [
            'name' => $managedUser->name,
            'email' => $managedUser->email,
            'role' => AccessControl::ROLE_SUPER_ADMIN,
        ])
        ->assertForbidden();
});
