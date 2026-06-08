<?php

namespace App\Actions\Mobile;

use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreateMobileAuthToken
{
    /**
     * @return array{0: string, 1: MobileAuthToken}
     */
    public function handle(User $user, Request $request, ?string $name = null): array
    {
        $plainTextToken = Str::random(80);
        $expiresInMinutes = config('auth.mobile_tokens.expire_minutes');

        $accessToken = $user->mobileAuthTokens()->create([
            'name' => $name
                ?: $request->string('device_name')->trim()->toString()
                ?: Str::limit((string) $request->userAgent(), 255, '')
                ?: 'React Native',
            'token_hash' => hash('sha256', $plainTextToken),
            'abilities' => ['*'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => is_numeric($expiresInMinutes) && (int) $expiresInMinutes > 0
                ? now()->addMinutes((int) $expiresInMinutes)
                : null,
        ]);

        return [$plainTextToken, $accessToken];
    }
}
