<?php

namespace Database\Seeders\Post;

use App\Models\Post\Post;
use App\Models\User;
use Database\Seeders\User\UserSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * @var array<string, int>
     */
    private const POSTS_PER_ROLE = [
        'super-admin' => 5,
        'administrator' => 7,
        'cashier' => 9,
        'subscriber' => 11,
    ];

    public function run(): void
    {
        foreach (UserSeeder::USERS as $seededUser) {
            $user = User::query()->where('email', $seededUser['email'])->first();

            if (! $user) {
                continue;
            }

            $postsCount = self::POSTS_PER_ROLE[$seededUser['role']] ?? 5;

            for ($index = 1; $index <= $postsCount; $index++) {
                $title = sprintf('%s Post %d', $seededUser['name'], $index);

                Post::query()->updateOrCreate(
                    ['slug' => Str::slug($title)],
                    [
                        'title' => $title,
                        'body' => sprintf(
                            "Seeded post %d for %s.\n\nThis content is generated for development data and role-based testing.",
                            $index,
                            $seededUser['name'],
                        ),
                        'author' => $user->name,
                        'cover' => null,
                    ],
                );
            }
        }
    }
}
