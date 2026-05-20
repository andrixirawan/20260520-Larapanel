<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guests are redirected from posts', function () {
    $this->get(route('posts.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the posts index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('posts.index'))
        ->assertOk();
});

test('post can be created with a cover image', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'title' => 'My First Post',
        'slug' => '',
        'author' => 'Admin',
        'body' => 'Post body',
        'cover' => UploadedFile::fake()->image('plain-cover.jpg'),
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.index'));

    $post = Post::query()->firstOrFail();

    expect($post->slug)
        ->toBe('my-first-post')
        ->and($post->cover)->toStartWith('uploads/posts/covers/')
        ->and($post->cover_url)->toBe("/posts/{$post->id}/cover")
        ->and(basename($post->cover))->not->toBe('plain-cover.jpg');

    Storage::disk('public')->assertExists($post->cover);
});

test('post cover is served through laravel instead of public storage symlink', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $cover = 'uploads/posts/covers/cover.jpg';
    Storage::disk('public')->put($cover, UploadedFile::fake()->image('cover.jpg')->getContent());
    $post = Post::factory()->create(['cover' => $cover]);

    $this->actingAs($user)
        ->get(route('posts.cover', $post))
        ->assertOk();
});

test('post cover upload replaces the previous cover', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $oldCover = 'uploads/posts/covers/old-cover.jpg';
    Storage::disk('public')->put($oldCover, 'old cover');
    $post = Post::factory()->create(['cover' => $oldCover]);

    $response = $this->actingAs($user)->post(route('posts.update', $post), [
        '_method' => 'put',
        'title' => $post->title,
        'slug' => $post->slug,
        'author' => $post->author,
        'body' => $post->body,
        'cover' => UploadedFile::fake()->image('new-cover.png'),
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.index'));

    $post->refresh();

    Storage::disk('public')->assertMissing($oldCover);
    Storage::disk('public')->assertExists($post->cover);
});

test('post cover can be removed', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $cover = 'uploads/posts/covers/cover.jpg';
    Storage::disk('public')->put($cover, 'cover');
    $post = Post::factory()->create(['cover' => $cover]);

    $response = $this->actingAs($user)->post(route('posts.update', $post), [
        '_method' => 'put',
        'title' => $post->title,
        'slug' => $post->slug,
        'author' => $post->author,
        'body' => $post->body,
        'remove_cover' => '1',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.index'));

    expect($post->refresh()->cover)->toBeNull();
    Storage::disk('public')->assertMissing($cover);
});

test('deleting a post removes its cover', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $cover = 'uploads/posts/covers/cover.jpg';
    Storage::disk('public')->put($cover, 'cover');
    $post = Post::factory()->create(['cover' => $cover]);

    $response = $this->actingAs($user)->delete(route('posts.destroy', $post));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.index'));

    Storage::disk('public')->assertMissing($cover);
    expect($post->fresh())->toBeNull();
});
