<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->safe()->except(['avatar', 'remove_avatar']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->boolean('remove_avatar') || $request->hasFile('avatar')) {
            $this->deleteLocalAvatar($user);

            $user->avatar = null;
        }

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');

            $storedAvatar = $avatar->storeAs(
                $this->avatarDirectory($user),
                $avatar->hashName(),
                ['disk' => $this->avatarDisk()],
            );

            if (! is_string($storedAvatar)) {
                throw ValidationException::withMessages([
                    'avatar' => __('The avatar could not be uploaded. Please check the storage path and permissions.'),
                ]);
            }

            $user->avatar = $storedAvatar;
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $this->deleteLocalAvatar($user);

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function avatarDirectory(User $user): string
    {
        return str_replace(
            '{user}',
            (string) $user->id,
            config('uploads.user_avatars.directory', 'uploads/users/{user}/avatars'),
        );
    }

    private function deleteLocalAvatar(User $user): void
    {
        $path = $this->localAvatarPath($user, $user->getRawOriginal('avatar'));

        if ($path) {
            Storage::disk($this->avatarDisk())->delete($path);
        }
    }

    private function avatarDisk(): string
    {
        return config('uploads.user_avatars.disk', 'public');
    }

    private function localAvatarPath(User $user, ?string $avatar): ?string
    {
        if (! $avatar) {
            return null;
        }

        $storagePath = parse_url(Storage::disk($this->avatarDisk())->url(''), PHP_URL_PATH);
        $storagePrefix = '/'.trim($storagePath ?: '/storage', '/');
        $avatarPath = parse_url($avatar, PHP_URL_PATH) ?: $avatar;

        if (Str::startsWith($avatarPath, $storagePrefix.'/')) {
            $avatar = Str::after($avatarPath, $storagePrefix.'/');
        }

        $avatar = ltrim($avatar, '/');
        $avatarDirectory = trim($this->avatarDirectory($user), '/').'/';

        return Str::startsWith($avatar, $avatarDirectory) ? $avatar : null;
    }
}
