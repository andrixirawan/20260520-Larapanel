<?php

use App\Actions\Mobile\CreateMobileAuthToken;
use App\Models\MobileAuthToken;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function mobileProfileTokenFor(User $user): string
{
    [$token] = app(CreateMobileAuthToken::class)->handle($user, request(), 'Profile test');

    return $token;
}

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

test('mobile users can update their name and avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $token = mobileProfileTokenFor($user);

    $response = $this->withToken($token)
        ->post(route('api.mobile.profile.update'), [
            'name' => 'Updated Mobile User',
            'avatar' => UploadedFile::fake()->image('mobile-avatar.png'),
        ], [
            'Accept' => 'application/json',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Profile updated.')
        ->assertJsonPath('data.name', 'Updated Mobile User')
        ->assertJsonPath('data.has_custom_avatar', true);

    $user->refresh();

    expect($user->name)->toBe('Updated Mobile User')
        ->and($response->json('data.avatar'))->toStartWith(
            url("/users/{$user->public_id}/avatar?v="),
        );

    Storage::disk('public')->assertExists($user->getRawOriginal('avatar'));
});

test('mobile avatar upload replaces the previous local avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $oldAvatar = "uploads/users/{$user->id}/avatars/old-avatar.jpg";
    Storage::disk('public')->put($oldAvatar, 'old avatar');
    $user->forceFill(['avatar' => $oldAvatar])->save();

    $this->withToken(mobileProfileTokenFor($user))
        ->post(route('api.mobile.profile.update'), [
            'name' => $user->name,
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
        ], [
            'Accept' => 'application/json',
        ])
        ->assertOk();

    $user->refresh();

    Storage::disk('public')->assertMissing($oldAvatar);
    Storage::disk('public')->assertExists($user->getRawOriginal('avatar'));
});

test('mobile users can remove their custom avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $avatar = "uploads/users/{$user->id}/avatars/avatar.jpg";
    Storage::disk('public')->put($avatar, 'avatar');
    $user->forceFill(['avatar' => $avatar])->save();

    $this->withToken(mobileProfileTokenFor($user))
        ->postJson(route('api.mobile.profile.update'), [
            'name' => $user->name,
            'remove_avatar' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.avatar', null)
        ->assertJsonPath('data.has_custom_avatar', false);

    expect($user->refresh()->getRawOriginal('avatar'))->toBeNull();
    Storage::disk('public')->assertMissing($avatar);
});

test('mobile profile update validates input', function () {
    $user = User::factory()->create();

    $this->withToken(mobileProfileTokenFor($user))
        ->postJson(route('api.mobile.profile.update'), [
            'name' => '',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

test('mobile profile update requires authentication', function () {
    $this->postJson(route('api.mobile.profile.update'), [
        'name' => 'Unauthorized Update',
    ])->assertUnauthorized();
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
