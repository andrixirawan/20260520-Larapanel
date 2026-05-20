<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

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

    $cover = 'uploads/posts/covers/cover.jpg';
    Storage::disk('public')->put($cover, UploadedFile::fake()->image('cover.jpg')->getContent());
    $post = Post::factory()->create(['cover' => $cover]);

    $this->get(route('posts.cover', $post))
        ->assertOk();
});

test('home renders a public paginated post list with filters', function () {
    Post::factory()->create([
        'title' => 'Alpha public note',
        'author' => 'Rani',
        'body' => 'Visible public body',
    ]);
    Post::factory()->create([
        'title' => 'Beta private draft',
        'author' => 'Budi',
        'body' => 'Different content',
    ]);

    $this->get(route('home', [
        'search' => 'Alpha',
        'author' => 'Rani',
        'sort' => 'title',
        'per_page' => 5,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('filters.search', 'Alpha')
            ->where('filters.author', 'Rani')
            ->where('filters.sort', 'title')
            ->where('filters.per_page', 5)
            ->where('posts.data.0.title', 'Alpha public note')
            ->where('posts.total', 1)
        );
});

test('posts can be opened from a public slug url', function () {
    $post = Post::factory()->create();

    $this->get(route('public.posts.show', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public-posts/show')
            ->where('post.id', $post->id)
        );
});

test('authenticated posts index supports the same filters', function () {
    $user = User::factory()->create();
    Post::factory()->create(['title' => 'Gamma dashboard post', 'author' => 'Sari']);
    Post::factory()->create(['title' => 'Delta dashboard post', 'author' => 'Joko']);

    $this->actingAs($user)
        ->get(route('posts.index', [
            'search' => 'Gamma',
            'author' => 'Sari',
            'sort' => 'latest',
            'per_page' => 5,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('filters.search', 'Gamma')
            ->where('filters.author', 'Sari')
            ->where('posts.data.0.title', 'Gamma dashboard post')
            ->where('posts.total', 1)
        );
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
