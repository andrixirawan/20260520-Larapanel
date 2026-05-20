<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'avatar', 'google_id', 'google_avatar', 'email_verified_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var array<int, string>
     */
    protected $appends = ['has_custom_avatar'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Return a browser-ready avatar URL while storing local uploads as relative paths.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (! $value) {
                    return $this->google_avatar;
                }

                if (Str::startsWith($value, ['http://', 'https://'])) {
                    return $this->versionAvatarUrl($value);
                }

                $disk = config('uploads.user_avatars.disk', 'public');
                $path = Str::startsWith($value, '/storage/')
                    ? Str::after($value, '/storage/')
                    : $value;

                return $this->versionAvatarUrl(Storage::disk($disk)->url($path));
            },
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function hasCustomAvatar(): Attribute
    {
        return Attribute::get(fn (): bool => filled($this->getRawOriginal('avatar')));
    }

    private function versionAvatarUrl(string $url): string
    {
        $version = $this->updated_at?->getTimestamp();

        if (! $version || ! Str::contains($url, '/storage/')) {
            return $url;
        }

        return $url.(Str::contains($url, '?') ? '&' : '?').'v='.$version;
    }
}
