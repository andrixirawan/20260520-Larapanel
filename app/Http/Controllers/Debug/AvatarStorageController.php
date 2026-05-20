<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AvatarStorageController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(config('app.debug'), 404);

        $user = $request->user()->fresh();
        $disk = config('uploads.user_avatars.disk', 'public');
        $storage = Storage::disk($disk);
        $rawAvatar = $user?->getRawOriginal('avatar');
        $resolvedAvatar = $user?->avatar;
        $avatarPath = $this->avatarPath($rawAvatar);
        $diskConfig = config("filesystems.disks.{$disk}", []);
        $diskRoot = $diskConfig['root'] ?? null;
        $avatarDirectory = trim(str_replace(
            '{user}',
            (string) $user?->id,
            config('uploads.user_avatars.directory', 'uploads/users/{user}/avatars'),
        ), '/');

        $checks = [
            'app' => [
                'debug' => config('app.debug'),
                'url' => config('app.url'),
                'filesystem_default' => config('filesystems.default'),
                'public_path' => public_path(),
                'storage_path' => storage_path(),
            ],
            'user' => [
                'id' => $user?->id,
                'email' => $user?->email,
                'raw_avatar_from_database' => $rawAvatar,
                'resolved_avatar_sent_to_frontend' => $resolvedAvatar,
                'google_avatar' => $user?->google_avatar,
                'has_custom_avatar' => $user?->has_custom_avatar,
                'updated_at' => $user?->updated_at?->toISOString(),
            ],
            'disk' => [
                'name' => $disk,
                'driver' => $diskConfig['driver'] ?? null,
                'root' => $diskRoot,
                'url' => $diskConfig['url'] ?? null,
                'visibility' => $diskConfig['visibility'] ?? null,
                'root_exists' => $diskRoot ? is_dir($diskRoot) : null,
                'root_writable' => $diskRoot ? is_writable($diskRoot) : null,
            ],
            'avatar_file' => [
                'path_on_disk' => $avatarPath,
                'storage_exists' => $avatarPath ? $this->safe(fn () => $storage->exists($avatarPath)) : null,
                'storage_size' => $avatarPath ? $this->safe(fn () => $storage->size($avatarPath)) : null,
                'physical_path' => $diskRoot && $avatarPath ? $diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $avatarPath) : null,
                'physical_exists' => $diskRoot && $avatarPath ? file_exists($diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $avatarPath)) : null,
                'directory' => $avatarDirectory,
                'directory_exists' => $this->safe(fn () => $storage->exists($avatarDirectory)),
                'directory_writable' => $diskRoot ? is_writable($diskRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $avatarDirectory)) : null,
            ],
            'public_storage_path' => [
                'path' => public_path('storage'),
                'exists' => file_exists(public_path('storage')),
                'is_link' => is_link(public_path('storage')),
                'realpath' => realpath(public_path('storage')) ?: null,
            ],
            'latest_avatar_files' => $this->latestAvatarFiles($storage, $avatarDirectory),
            'url_check' => $this->checkUrl($resolvedAvatar),
        ];

        return response()->json([
            ...$checks,
            'diagnosis' => $this->diagnose($checks),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function avatarPath(?string $avatar): ?string
    {
        if (! $avatar) {
            return null;
        }

        $path = parse_url($avatar, PHP_URL_PATH) ?: $avatar;

        return ltrim(Str::startsWith($path, '/storage/')
            ? Str::after($path, '/storage/')
            : $path, '/');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function latestAvatarFiles($storage, string $directory): array
    {
        return $this->safe(function () use ($storage, $directory) {
            return collect($storage->files($directory))
                ->map(fn (string $path) => [
                    'path' => $path,
                    'url' => $storage->url($path),
                    'size' => $storage->size($path),
                    'last_modified' => $storage->lastModified($path),
                ])
                ->sortByDesc('last_modified')
                ->take(10)
                ->values()
                ->all();
        }, []);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function checkUrl(?string $url): ?array
    {
        if (! $url || ! Str::startsWith($url, ['http://', 'https://'])) {
            return [
                'url' => $url,
                'status' => null,
                'ok' => false,
                'error' => 'Avatar URL is empty or not absolute.',
            ];
        }

        try {
            $response = Http::timeout(8)->withoutVerifying()->head($url);

            return [
                'url' => $url,
                'status' => $response->status(),
                'ok' => $response->successful(),
                'content_type' => $response->header('content-type'),
                'content_length' => $response->header('content-length'),
            ];
        } catch (Throwable $exception) {
            return [
                'url' => $url,
                'status' => null,
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $checks
     * @return array<int, string>
     */
    private function diagnose(array $checks): array
    {
        $messages = [];
        $rawAvatar = $checks['user']['raw_avatar_from_database'] ?? null;
        $latestFiles = $checks['latest_avatar_files'] ?? [];
        $resolvedAvatar = $checks['user']['resolved_avatar_sent_to_frontend'] ?? null;

        if (! $rawAvatar && count($latestFiles) > 0) {
            $messages[] = 'Upload files exist, but users.avatar is empty. The upload reaches storage, but the database value is not being saved for this user.';
        }

        if ($rawAvatar && ! ($checks['avatar_file']['storage_exists'] ?? false)) {
            $messages[] = 'users.avatar has a value, but Storage::disk(...)->exists() is false. PUBLIC_DISK_ROOT or the stored avatar path is wrong.';
        }

        if (is_string($resolvedAvatar) && Str::startsWith($resolvedAvatar, '/storage/')) {
            $messages[] = 'Frontend is still receiving a relative /storage URL. Clear production config/opcache and confirm PUBLIC_DISK_URL is configured.';
        }

        if (($checks['avatar_file']['storage_exists'] ?? false) && ! ($checks['url_check']['ok'] ?? false)) {
            $messages[] = 'The avatar file exists on disk, but the public URL is not accessible. Check cPanel document root, public storage folder, .htaccess, or file permissions.';
        }

        if (! ($checks['disk']['root_writable'] ?? false)) {
            $messages[] = 'The configured public disk root is not writable by PHP.';
        }

        return $messages ?: ['No obvious issue detected by server-side checks. Inspect the browser Network tab for the avatar image request.'];
    }

    private function safe(callable $callback, mixed $fallback = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            return [
                'error' => $exception->getMessage(),
            ];
        }
    }
}
