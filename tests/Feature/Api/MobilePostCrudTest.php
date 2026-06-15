<?php

use App\Actions\Mobile\CreateMobileAuthToken;
use App\Models\Post\Post;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function mobileTokenFor(User $user): string
{
    [$token] = app(CreateMobileAuthToken::class)->handle(
        $user,
        request()->merge(['device_name' => 'Pest Mobile']),
    );

    return $token;
}

test('mobile posts require authentication', function () {
    $this->getJson(route('api.mobile.posts.index'))
        ->assertUnauthorized();
});

test('mobile users can list and filter posts', function () {
    $user = User::factory()->create();
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $token = mobileTokenFor($user);

    Post::factory()->create([
        'title' => 'Laravel API Guide',
        'author' => 'Rani',
    ]);
    Post::factory()->create([
        'title' => 'React Native Notes',
        'author' => 'Budi',
    ]);

    $this->withToken($token)
        ->getJson(route('api.mobile.posts.index', [
            'search' => 'Laravel',
            'per_page' => 5,
        ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Laravel API Guide')
        ->assertJsonPath('filters.search', 'Laravel')
        ->assertJsonStructure([
            'data' => [
                '*' => ['public_id', 'title', 'slug', 'cover', 'cover_url', 'body', 'author', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
            'filters',
            'sort_options',
        ]);
});

test('mobile users can create show update and delete posts', function () {
    $user = User::factory()->create(['name' => 'Mobile Writer']);
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $token = mobileTokenFor($user);

    $create = $this->withToken($token)
        ->postJson(route('api.mobile.posts.store'), [
            'title' => 'Mobile CRUD Post',
            'slug' => '',
            'author' => 'Spoofed Author',
            'body' => 'Created from the mobile API.',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'Post created.')
        ->assertJsonPath('data.slug', 'mobile-crud-post')
        ->assertJsonPath('data.author', 'Mobile Writer');

    $postPublicId = $create->json('data.public_id');

    $this->withToken($token)
        ->getJson(route('api.mobile.posts.show', $postPublicId))
        ->assertOk()
        ->assertJsonPath('data.title', 'Mobile CRUD Post');

    $this->withToken($token)
        ->patchJson(route('api.mobile.posts.update', $postPublicId), [
            'title' => 'Updated Mobile Post',
            'slug' => 'updated-mobile-post',
            'author' => 'Spoofed Update Author',
            'body' => 'Updated from the mobile API.',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Post updated.')
        ->assertJsonPath('data.author', 'Mobile Writer');

    $this->withToken($token)
        ->deleteJson(route('api.mobile.posts.destroy', $postPublicId))
        ->assertOk()
        ->assertJsonPath('message', 'Post deleted.');

    expect(Post::query()->where('public_id', $postPublicId)->exists())->toBeFalse();
});

test('mobile post mutations require write permissions', function () {
    $user = User::factory()->create();
    $user->assignRole(AccessControl::ROLE_SUBSCRIBER);
    $token = mobileTokenFor($user);
    $post = Post::factory()->create();

    $this->withToken($token)
        ->getJson(route('api.mobile.posts.index'))
        ->assertOk();

    $this->withToken($token)
        ->postJson(route('api.mobile.posts.store'), [
            'title' => 'Unauthorized Post',
            'slug' => '',
            'body' => 'This should not be created.',
        ])
        ->assertForbidden();

    $this->withToken($token)
        ->patchJson(route('api.mobile.posts.update', $post), [
            'title' => 'Unauthorized Update',
            'slug' => $post->slug,
            'body' => 'This should not be updated.',
        ])
        ->assertForbidden();

    $this->withToken($token)
        ->deleteJson(route('api.mobile.posts.destroy', $post))
        ->assertForbidden();
});

test('mobile users can create a post with cover upload', function () {
    Storage::fake('public');
    $user = User::factory()->create(['name' => 'Mobile Writer']);
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $token = mobileTokenFor($user);

    $response = $this->withToken($token)
        ->post(route('api.mobile.posts.store'), [
            'title' => 'Mobile Cover Post',
            'slug' => '',
            'body' => 'Created with a cover from mobile.',
            'cover' => UploadedFile::fake()->image('plain-cover.jpg'),
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'Post created.');

    $post = Post::query()->where('public_id', $response->json('data.public_id'))->firstOrFail();

    expect($post->cover)
        ->toStartWith('uploads/posts/covers/')
        ->and($post->author)->toBe('Mobile Writer')
        ->and($response->json('data.cover'))->toBe($post->cover)
        ->and($response->json('data.cover_url'))->toBe(route('posts.cover', $post));

    Storage::disk('public')->assertExists($post->cover);
});

test('mobile users can replace and remove a cover image', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $token = mobileTokenFor($user);
    $oldCover = 'uploads/posts/covers/old-cover.jpg';
    Storage::disk('public')->put($oldCover, 'old cover');
    $post = Post::factory()->create(['cover' => $oldCover]);

    $this->withToken($token)
        ->post(route('api.mobile.posts.update', $post), [
            '_method' => 'PATCH',
            'title' => 'Updated Cover Post',
            'slug' => 'updated-cover-post',
            'body' => 'Updated with a new cover from mobile.',
            'cover' => UploadedFile::fake()->image('new-cover.png'),
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Post updated.')
        ->assertJsonPath('data.cover_url', route('posts.cover', $post))
        ->assertJsonPath('data.author', $post->author);

    $newCover = $post->refresh()->cover;

    expect($newCover)->not->toBe($oldCover);
    Storage::disk('public')->assertMissing($oldCover);
    Storage::disk('public')->assertExists($newCover);

    $this->withToken($token)
        ->patchJson(route('api.mobile.posts.update', $post), [
            'title' => 'Updated Cover Post',
            'slug' => 'updated-cover-post',
            'body' => 'Updated without cover.',
            'remove_cover' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.cover', null)
        ->assertJsonPath('data.cover_url', null);

    Storage::disk('public')->assertMissing($newCover);
    expect($post->refresh()->cover)->toBeNull();
});
