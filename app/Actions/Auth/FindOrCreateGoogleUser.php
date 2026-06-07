<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FindOrCreateGoogleUser
{
    public function handle(
        string $googleId,
        string $email,
        ?string $name = null,
        ?string $nickname = null,
        ?string $avatar = null,
    ): User {
        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = new User([
                'email' => $email,
            ]);
        }

        $user->fill([
            'name' => $user->exists ? $user->name : ($name ?: $nickname ?: 'Google User'),
            'google_id' => $googleId,
            'google_avatar' => $avatar,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ]);

        if (! $user->exists) {
            $user->password = Hash::make(Str::random(32));
        }

        $user->save();

        return $user;
    }
}
