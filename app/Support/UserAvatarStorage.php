<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAvatarStorage
{
    public function store(User $user, UploadedFile $avatar): string
    {
        $storedAvatar = $avatar->storeAs(
            trim($this->directory($user), '/'),
            $avatar->hashName(),
            ['disk' => $this->disk()],
        );

        if (! is_string($storedAvatar)) {
            throw ValidationException::withMessages([
                'avatar' => __('The avatar could not be uploaded. Please check the storage path and permissions.'),
            ]);
        }

        return $storedAvatar;
    }

    public function delete(User $user): void
    {
        $path = $this->localPath($user, $user->getRawOriginal('avatar'));

        if ($path) {
            Storage::disk($this->disk())->delete($path);
        }
    }

    public function disk(): string
    {
        return config('uploads.user_avatars.disk', 'public');
    }

    private function directory(User $user): string
    {
        return str_replace(
            '{user}',
            (string) $user->id,
            config('uploads.user_avatars.directory', 'uploads/users/{user}/avatars'),
        );
    }

    private function localPath(User $user, ?string $avatar): ?string
    {
        if (! $avatar) {
            return null;
        }

        $storagePath = parse_url(Storage::disk($this->disk())->url(''), PHP_URL_PATH);
        $storagePrefix = '/'.trim($storagePath ?: '/storage', '/');
        $avatarPath = parse_url($avatar, PHP_URL_PATH) ?: $avatar;

        if (Str::startsWith($avatarPath, $storagePrefix.'/')) {
            $avatar = Str::after($avatarPath, $storagePrefix.'/');
        }

        $avatar = ltrim($avatar, '/');
        $avatarDirectory = trim($this->directory($user), '/').'/';

        return Str::startsWith($avatar, $avatarDirectory) ? $avatar : null;
    }
}
