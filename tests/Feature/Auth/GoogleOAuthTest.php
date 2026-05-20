<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

function googleUser(array $attributes = []): SocialiteUser
{
    return (new SocialiteUser)->setRaw($attributes)->map(array_merge([
        'id' => 'google-123',
        'nickname' => null,
        'name' => 'Taylor Otwell',
        'email' => 'taylor@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ], $attributes));
}

test('users can be redirected to google', function () {
    $provider = Mockery::mock(Provider::class);

    $provider->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://accounts.google.com/oauth'));

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);

    $this->get(route('auth.google.redirect'))
        ->assertRedirect('https://accounts.google.com/oauth');
});

test('google callback creates and authenticates a user', function () {
    Mail::fake();

    $provider = Mockery::mock(Provider::class);

    $provider->shouldReceive('user')
        ->once()
        ->andReturn(googleUser());

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'taylor@example.com')->firstOrFail();

    expect($user->google_id)->toBe('google-123')
        ->and($user->google_avatar)->toBe('https://example.com/avatar.jpg')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('google callback links an existing account without changing its password', function () {
    Mail::fake();

    $password = Hash::make('secret-password');
    $existingUser = User::factory()->unverified()->create([
        'email' => 'taylor@example.com',
        'name' => 'Existing User',
        'password' => $password,
    ]);

    $provider = Mockery::mock(Provider::class);

    $provider->shouldReceive('user')
        ->once()
        ->andReturn(googleUser([
            'id' => 'google-456',
            'name' => 'Google Name',
        ]));

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    $existingUser->refresh();

    expect($existingUser->name)->toBe('Existing User')
        ->and($existingUser->password)->toBe($password)
        ->and($existingUser->google_id)->toBe('google-456')
        ->and($existingUser->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($existingUser);
});
