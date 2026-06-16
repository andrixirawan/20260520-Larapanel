<?php

use App\Models\Post\Post;
use App\Models\User;
use App\Support\AccessControl;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guests are redirected from posts', function () {
    $this->get(route('posts.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the posts index', function () {
    $user = User::factory()->create();
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);

    $this->actingAs($user)
        ->get(route('posts.index'))
        ->assertOk();
});

test('post can be created with a cover image', function () {
    Storage::fake('public');

    $user = User::factory()->create(['name' => 'Current Admin']);
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'title' => 'My First Post',
        'slug' => '',
        'body' => 'Post body',
        'cover' => UploadedFile::fake()->image('plain-cover.jpg'),
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.mine'));

    $post = Post::query()->firstOrFail();

    expect($post->slug)
        ->toBe('my-first-post')
        ->and($post->user_id)->toBe($user->id)
        ->and($post->author)->toBe('Current Admin')
        ->and($post->cover)->toStartWith('uploads/posts/covers/')
        ->and($post->cover_url)->toBe("/posts/{$post->public_id}/cover")
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
            ->where('post.public_id', $post->public_id)
        );
});

test('public blog index route renders the post listing', function () {
    Post::factory()->create(['title' => 'Blog route post']);

    $this->get(route('public.posts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('posts.data.0.title', 'Blog route post')
        );
});

test('authenticated all posts index supports the same filters', function () {
    $user = User::factory()->create(['name' => 'Sari']);
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    Post::factory()->create([
        'title' => 'Gamma dashboard post',
        'author' => $user->name,
        'user_id' => $user->id,
    ]);
    Post::factory()->create([
        'title' => 'Delta dashboard post',
        'author' => $user->name,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('posts.index', [
            'search' => 'Gamma',
            'author' => 'Sari',
            'sort' => 'title',
            'per_page' => 5,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('filters.search', 'Gamma')
            ->where('filters.author', 'Sari')
            ->where('scope', 'all')
            ->where('posts.data.0.title', 'Gamma dashboard post')
            ->where('posts.total', 1)
        );
});

test('authenticated users can view all posts from the dashboard all posts list', function () {
    $user = User::factory()->create(['name' => 'Owner']);
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $otherUser = User::factory()->create(['name' => 'Other']);

    Post::factory()->create([
        'title' => 'Owned post',
        'author' => $user->name,
        'user_id' => $user->id,
    ]);
    Post::factory()->create([
        'title' => 'Other post',
        'author' => $otherUser->name,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get(route('posts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('scope', 'all')
            ->where('posts.total', 2)
        );
});

test('authenticated users can view only their own posts from the dashboard my posts list', function () {
    $user = User::factory()->create(['name' => 'Owner']);
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $otherUser = User::factory()->create(['name' => 'Other']);

    Post::factory()->create([
        'title' => 'Owned post',
        'author' => $user->name,
        'user_id' => $user->id,
    ]);
    Post::factory()->create([
        'title' => 'Other post',
        'author' => $otherUser->name,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get(route('posts.mine'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('posts/index')
            ->where('scope', 'mine')
            ->where('posts.data.0.title', 'Owned post')
            ->where('posts.total', 1)
        );
});

test('authenticated users cannot open another users post from the dashboard', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $owner->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $otherAdmin = User::factory()->create(['name' => 'Other Admin']);
    $otherAdmin->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $post = Post::factory()->create([
        'author' => $owner->name,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($otherAdmin)
        ->get(route('posts.show', $post))
        ->assertForbidden();
});

test('administrators cannot edit or delete posts they did not create', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $owner->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $otherAdmin = User::factory()->create(['name' => 'Other Admin']);
    $otherAdmin->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $post = Post::factory()->create([
        'author' => $owner->name,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($otherAdmin)
        ->get(route('posts.edit', $post))
        ->assertForbidden();

    $this->actingAs($otherAdmin)
        ->delete(route('posts.destroy', $post))
        ->assertForbidden();
});

test('superadmin can edit and delete posts created by other users', function () {
    $owner = User::factory()->create(['name' => 'Owner']);
    $owner->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $superAdmin = User::factory()->create(['name' => 'Super Admin']);
    $superAdmin->assignRole(AccessControl::ROLE_SUPER_ADMIN);
    $post = Post::factory()->create([
        'author' => $owner->name,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('posts.edit', $post))
        ->assertOk();

    $this->actingAs($superAdmin)
        ->delete(route('posts.destroy', $post))
        ->assertRedirect(route('posts.mine'));

    expect($post->fresh())->toBeNull();
});

test('post cover upload replaces the previous cover', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $oldCover = 'uploads/posts/covers/old-cover.jpg';
    Storage::disk('public')->put($oldCover, 'old cover');
    $post = Post::factory()->create([
        'cover' => $oldCover,
        'author' => $user->name,
        'user_id' => $user->id,
    ]);
    $originalAuthor = $post->author;

    $response = $this->actingAs($user)->post(route('posts.update', $post), [
        '_method' => 'put',
        'title' => $post->title,
        'slug' => $post->slug,
        'body' => $post->body,
        'cover' => UploadedFile::fake()->image('new-cover.png'),
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.mine'));

    $post->refresh();

    Storage::disk('public')->assertMissing($oldCover);
    Storage::disk('public')->assertExists($post->cover);
    expect($post->author)->toBe($originalAuthor);
});

test('post cover can be removed', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $cover = 'uploads/posts/covers/cover.jpg';
    Storage::disk('public')->put($cover, 'cover');
    $post = Post::factory()->create([
        'cover' => $cover,
        'author' => $user->name,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('posts.update', $post), [
        '_method' => 'put',
        'title' => $post->title,
        'slug' => $post->slug,
        'body' => $post->body,
        'remove_cover' => '1',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.mine'));

    expect($post->refresh()->cover)->toBeNull();
    Storage::disk('public')->assertMissing($cover);
});

test('deleting a post removes its cover', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole(AccessControl::ROLE_ADMINISTRATOR);
    $cover = 'uploads/posts/covers/cover.jpg';
    Storage::disk('public')->put($cover, 'cover');
    $post = Post::factory()->create([
        'cover' => $cover,
        'author' => $user->name,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('posts.destroy', $post));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('posts.mine'));

    Storage::disk('public')->assertMissing($cover);
    expect($post->fresh())->toBeNull();
});
