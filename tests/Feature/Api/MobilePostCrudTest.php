<?php

use App\Actions\Mobile\CreateMobileAuthToken;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
    $token = mobileTokenFor(User::factory()->create());

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
                '*' => ['id', 'title', 'slug', 'cover_url', 'body', 'author', 'created_at', 'updated_at'],
            ],
            'links',
            'meta',
            'filters',
            'sort_options',
        ]);
});

test('mobile users can create show update and delete posts', function () {
    $token = mobileTokenFor(User::factory()->create());

    $create = $this->withToken($token)
        ->postJson(route('api.mobile.posts.store'), [
            'title' => 'Mobile CRUD Post',
            'slug' => '',
            'body' => 'Created from the mobile API.',
            'author' => 'Mobile User',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'Post created.')
        ->assertJsonPath('data.slug', 'mobile-crud-post');

    $postId = $create->json('data.id');

    $this->withToken($token)
        ->getJson(route('api.mobile.posts.show', $postId))
        ->assertOk()
        ->assertJsonPath('data.title', 'Mobile CRUD Post');

    $this->withToken($token)
        ->patchJson(route('api.mobile.posts.update', $postId), [
            'title' => 'Updated Mobile Post',
            'slug' => 'updated-mobile-post',
            'body' => 'Updated from the mobile API.',
            'author' => 'Editor',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Post updated.')
        ->assertJsonPath('data.author', 'Editor');

    $this->withToken($token)
        ->deleteJson(route('api.mobile.posts.destroy', $postId))
        ->assertOk()
        ->assertJsonPath('message', 'Post deleted.');

    expect(Post::query()->whereKey($postId)->exists())->toBeFalse();
});
