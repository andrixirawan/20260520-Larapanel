<?php

use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

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
            'user' => ['id', 'name', 'email', 'is_email_verified'],
        ])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'mobile@example.com');

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

test('mobile users can login with google id token', function () {
    config()->set('services.google.mobile_client_ids', ['mobile-client.apps.googleusercontent.com']);

    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'iss' => 'https://accounts.google.com',
            'aud' => 'mobile-client.apps.googleusercontent.com',
            'sub' => 'google-mobile-123',
            'email' => 'google-mobile@example.com',
            'email_verified' => 'true',
            'name' => 'Google Mobile',
            'picture' => 'https://example.com/google-mobile.jpg',
        ]),
    ]);

    $response = $this->postJson(route('api.mobile.auth.google'), [
        'id_token' => 'valid-google-id-token',
        'device_name' => 'Expo Go',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_at',
            'user' => ['id', 'name', 'email', 'is_email_verified'],
        ])
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'google-mobile@example.com')
        ->assertJsonPath('user.is_email_verified', true);

    $user = User::where('email', 'google-mobile@example.com')->firstOrFail();

    expect($user->google_id)->toBe('google-mobile-123')
        ->and($user->google_avatar)->toBe('https://example.com/google-mobile.jpg')
        ->and(MobileAuthToken::query()->count())->toBe(1);
});

test('mobile google login rejects unallowed audience', function () {
    config()->set('services.google.mobile_client_ids', ['allowed-client.apps.googleusercontent.com']);

    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'iss' => 'https://accounts.google.com',
            'aud' => 'other-client.apps.googleusercontent.com',
            'sub' => 'google-mobile-123',
            'email' => 'google-mobile@example.com',
            'email_verified' => true,
        ]),
    ]);

    $this->postJson(route('api.mobile.auth.google'), [
        'id_token' => 'wrong-audience-google-id-token',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('id_token');

    expect(User::where('email', 'google-mobile@example.com')->exists())->toBeFalse();
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
