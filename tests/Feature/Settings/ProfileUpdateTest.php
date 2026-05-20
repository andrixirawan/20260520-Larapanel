<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('avatar picture can be uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->getRawOriginal('avatar'))
        ->toStartWith("uploads/users/{$user->id}/avatars/")
        ->and($user->avatar)->toStartWith('/storage/uploads/users/');

    Storage::disk('public')->assertExists($user->getRawOriginal('avatar'));
});

test('uploaded avatar filename is hashed and does not use the original filename', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('plain-user-avatar.jpg'),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $path = $user->refresh()->getRawOriginal('avatar');
    $filename = pathinfo($path, PATHINFO_FILENAME);

    expect(basename($path))
        ->not->toBe('plain-user-avatar.jpg')
        ->not->toContain('plain-user-avatar')
        ->and(strlen($filename))->toBeGreaterThanOrEqual(32);

    Storage::disk('public')->assertExists($path);
});

test('uploaded avatar url uses the configured public disk url', function () {
    config(['filesystems.disks.public.url' => 'https://demo.example.test/storage']);

    $user = User::factory()->create([
        'avatar' => 'uploads/users/1/avatars/avatar.jpg',
    ]);

    expect($user->avatar)
        ->toStartWith('https://demo.example.test/storage/uploads/users/1/avatars/avatar.jpg')
        ->toContain('?v=');
});

test('avatar upload replaces the previous local avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $oldAvatar = "uploads/users/{$user->id}/avatars/old-avatar.jpg";

    Storage::disk('public')->put($oldAvatar, 'old avatar');
    $user->forceFill(['avatar' => $oldAvatar])->save();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('new-avatar.png'),
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    Storage::disk('public')->assertMissing($oldAvatar);
    Storage::disk('public')->assertExists($user->getRawOriginal('avatar'));
});

test('avatar picture can be removed', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $avatar = "uploads/users/{$user->id}/avatars/avatar.jpg";

    Storage::disk('public')->put($avatar, 'avatar');
    $user->forceFill(['avatar' => $avatar])->save();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => '1',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->getRawOriginal('avatar'))->toBeNull();

    Storage::disk('public')->assertMissing($avatar);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('deleting an account removes its local avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $avatar = "uploads/users/{$user->id}/avatars/avatar.jpg";

    Storage::disk('public')->put($avatar, 'avatar');
    $user->forceFill(['avatar' => $avatar])->save();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    Storage::disk('public')->assertMissing($avatar);
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
