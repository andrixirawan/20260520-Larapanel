<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateGoogleUser;
use App\Actions\Mobile\CreateMobileAuthToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleOAuthController extends Controller
{
    private const MOBILE_REDIRECT_URI_KEY = 'google_oauth_mobile_redirect_uri';

    private const MOBILE_DEVICE_NAME_KEY = 'google_oauth_mobile_device_name';

    public function redirect(Request $request): RedirectResponse
    {
        if ($this->isMobileLogin($request)) {
            $mobileRedirectUri = $this->mobileRedirectUri($request);

            if (! $mobileRedirectUri) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Mobile Google redirect URI is not allowed.',
                ]);
            }

            $request->session()->put(self::MOBILE_REDIRECT_URI_KEY, $mobileRedirectUri);
            $request->session()->put(
                self::MOBILE_DEVICE_NAME_KEY,
                $request->string('device_name')->limit(255)->toString() ?: 'React Native',
            );
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, FindOrCreateGoogleUser $users, CreateMobileAuthToken $tokens): RedirectResponse
    {
        $mobileRedirectUri = $request->session()->pull(self::MOBILE_REDIRECT_URI_KEY);
        $mobileDeviceName = $request->session()->pull(self::MOBILE_DEVICE_NAME_KEY);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            if ($mobileRedirectUri) {
                return $this->redirectToMobile($mobileRedirectUri, [
                    'error' => 'google_auth_failed',
                ]);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication was cancelled or could not be completed.',
            ]);
        }

        if (! $googleUser->getEmail()) {
            if ($mobileRedirectUri) {
                return $this->redirectToMobile($mobileRedirectUri, [
                    'error' => 'google_email_missing',
                ]);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Google did not provide an email address for this account.',
            ]);
        }

        $user = $users->handle(
            googleId: $googleUser->getId(),
            email: $googleUser->getEmail(),
            name: $googleUser->getName(),
            nickname: $googleUser->getNickname(),
            avatar: $googleUser->getAvatar(),
        );

        if ($mobileRedirectUri) {
            [$plainTextToken, $accessToken] = $tokens->handle($user, $request, $mobileDeviceName ?: 'React Native');

            return $this->redirectToMobile($mobileRedirectUri, [
                'access_token' => $plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $accessToken->expires_at?->toISOString(),
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function isMobileLogin(Request $request): bool
    {
        return $request->boolean('mobile')
            || $request->filled('mobile_redirect_uri')
            || $request->filled('redirect_uri');
    }

    private function mobileRedirectUri(Request $request): ?string
    {
        $allowedUris = collect(config('auth.mobile_tokens.redirect_uris', []))
            ->filter()
            ->map(fn (string $uri): string => trim($uri))
            ->values();

        $redirectUri = $request->string('mobile_redirect_uri')->toString()
            ?: $request->string('redirect_uri')->toString()
            ?: $allowedUris->first();

        if (! $redirectUri || ! $allowedUris->contains($redirectUri)) {
            return null;
        }

        return $redirectUri;
    }

    /**
     * @param  array<string, string|null>  $parameters
     */
    private function redirectToMobile(string $uri, array $parameters): RedirectResponse
    {
        $query = http_build_query(
            array_filter($parameters, fn (?string $value): bool => $value !== null),
            '',
            '&',
            PHP_QUERY_RFC3986,
        );

        return redirect()->away($uri.(str_contains($uri, '?') ? '&' : '?').$query);
    }
}
