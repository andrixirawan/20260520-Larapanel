<?php

use App\Models\MobileAuthToken;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('mobile users can register and receive bearer token', function () {
    Notification::fake();

    $response = $this->postJson(route('api.mobile.auth.register'), [
        'name' => 'Mobile User',
        'email' => 'mobile@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'iPhone 15',
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_at',
            'user' => ['public_id', 'name', 'email', 'is_email_verified', 'roles', 'permissions'],
        ])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'mobile@example.com');

    expect($response->json('user.roles'))->toBe([AccessControl::ROLE_SUBSCRIBER])
        ->and($response->json('user.permissions'))->toMatchArray([
            AccessControl::PERMISSION_POSTS_VIEW => true,
            AccessControl::PERMISSION_POSTS_CREATE => false,
            AccessControl::PERMISSION_POSTS_UPDATE => false,
            AccessControl::PERMISSION_POSTS_DELETE => false,
        ]);

    expect(MobileAuthToken::query()->count())->toBe(1);
});

test('mobile users can login fetch current user and logout', function () {
    $user = User::factory()->create([
        'email' => 'mobile-login@example.com',
    ]);

    $login = $this->postJson(route('api.mobile.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Pixel',
    ]);

    $token = $login->json('access_token');

    $this->withToken($token)
        ->getJson(route('api.mobile.user'))
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);

    $this->withToken($token)
        ->postJson(route('api.mobile.auth.logout'))
        ->assertOk();

    $this->withToken($token)
        ->getJson(route('api.mobile.user'))
        ->assertUnauthorized();
});

test('mobile login rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.mobile.auth.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('mobile login requires two factor code when enabled', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $this->postJson(route('api.mobile.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertConflict()
        ->assertJsonPath('code', 'two_factor_required');

    $this->postJson(route('api.mobile.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'recovery_code' => 'recovery-code-1',
    ])
        ->assertOk()
        ->assertJsonStructure(['access_token']);
});

test('mobile forgot password sends reset link without exposing unknown emails', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson(route('api.mobile.auth.forgot-password'), [
        'email' => $user->email,
    ])->assertOk();

    Notification::assertSentTo($user, ResetPassword::class);

    $this->postJson(route('api.mobile.auth.forgot-password'), [
        'email' => 'unknown@example.com',
    ])->assertOk();
});
