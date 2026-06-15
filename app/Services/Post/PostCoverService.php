<?php

namespace App\Services\Post;

use App\Models\Post\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class PostCoverService
{
    public function store(Request $request): string
    {
        $cover = $request->file('cover');

        if (! $cover) {
            throw ValidationException::withMessages([
                'cover' => __('The cover image could not be uploaded.'),
            ]);
        }

        $storedCover = $cover->storeAs(
            $this->directory(),
            $cover->hashName(),
            ['disk' => $this->disk()],
        );

        if (! is_string($storedCover)) {
            throw ValidationException::withMessages([
                'cover' => __('The cover image could not be uploaded. Please check storage permissions.'),
            ]);
        }

        return $storedCover;
    }

    public function delete(?string $coverPath): void
    {
        if ($coverPath) {
            Storage::disk($this->disk())->delete($coverPath);
        }
    }

    public function deleteForPost(Post $post): void
    {
        $this->delete($post->getRawOriginal('cover'));
    }

    public function exists(string $coverPath): bool
    {
        return Storage::disk($this->disk())->exists($coverPath);
    }

    public function response(string $coverPath)
    {
        return Storage::disk($this->disk())->response($coverPath);
    }

    public function disk(): string
    {
        return config('uploads.posts.disk', 'public');
    }

    public function directory(): string
    {
        $baseDirectory = trim(
            config('uploads.posts.directory', 'uploads/posts/covers'),
            '/',
        );

        return $baseDirectory.'/'.now()->format('Y/m');
    }
}
