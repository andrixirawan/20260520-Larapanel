<?php

use App\Models\Post\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public api can list posts', function () {
    Post::factory()->create([
        'title' => 'Public API Alpha',
        'author' => 'Rani',
    ]);
    Post::factory()->create([
        'title' => 'Public API Beta',
        'author' => 'Budi',
    ]);

    $this->getJson(route('api.posts.index', ['search' => 'Alpha']))
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Public API Alpha')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('filters.search', 'Alpha');
});

test('public api can show post detail by slug', function () {
    $post = Post::factory()->create([
        'title' => 'Readable Slug Post',
        'slug' => 'readable-slug-post',
    ]);

    $this->getJson(route('api.posts.show', $post->slug))
        ->assertOk()
        ->assertJsonPath('data.public_id', $post->public_id)
        ->assertJsonPath('data.slug', 'readable-slug-post');
});
