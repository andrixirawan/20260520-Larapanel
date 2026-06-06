<?php

namespace App\Http\Middleware\Mobile;

use App\Models\MobileAuthToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return $this->unauthenticated();
        }

        $accessToken = MobileAuthToken::with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (! $accessToken || ! $accessToken->user || $accessToken->isExpired()) {
            return $this->unauthenticated();
        }

        $accessToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->attributes->set('mobile_access_token', $accessToken);
        $request->setUserResolver(fn () => $accessToken->user);
        Auth::setUser($accessToken->user);

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
