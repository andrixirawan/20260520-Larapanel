<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateGoogleUser;
use App\Actions\Mobile\CreateMobileAuthToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class GoogleOAuthController extends Controller
{
    private const MOBILE_REDIRECT_URI_KEY = 'google_oauth_mobile_redirect_uri';

    private const MOBILE_DEVICE_NAME_KEY = 'google_oauth_mobile_device_name';

    private const MOBILE_STATE_PREFIX = 'mobile_';

    private const MOBILE_STATE_CACHE_PREFIX = 'google_oauth_mobile_state:';

    public function redirect(Request $request): RedirectResponse
    {
        if ($this->isMobileLogin($request)) {
            $mobileRedirectUri = $this->mobileRedirectUri($request);

            if (! $mobileRedirectUri) {
                $fallbackRedirectUri = $this->defaultMobileRedirectUri();

                abort_if(! $fallbackRedirectUri, Response::HTTP_BAD_REQUEST, 'Mobile Google redirect URI is not allowed.');

                return $this->redirectToMobile($fallbackRedirectUri, [
                    'error' => 'mobile_redirect_uri_not_allowed',
                    'message' => 'Mobile Google redirect URI is not allowed.',
                ]);
            }

            $state = self::MOBILE_STATE_PREFIX.Str::random(40);
            $deviceName = $request->string('device_name')->limit(255)->toString() ?: 'React Native';

            Cache::put($this->mobileStateCacheKey($state), [
                'redirect_uri' => $mobileRedirectUri,
                'device_name' => $deviceName,
            ], now()->addMinutes(10));

            $request->session()->put(self::MOBILE_REDIRECT_URI_KEY, $mobileRedirectUri);
            $request->session()->put(self::MOBILE_DEVICE_NAME_KEY, $deviceName);

            return Socialite::driver('google')
                ->stateless()
                ->with(['state' => $state])
                ->redirect();
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request, FindOrCreateGoogleUser $users, CreateMobileAuthToken $tokens): RedirectResponse
    {
        $mobileLogin = $this->mobileLoginFromState($request);
        $mobileRedirectUri = $mobileLogin['redirect_uri'] ?? $request->session()->pull(self::MOBILE_REDIRECT_URI_KEY);
        $mobileDeviceName = $mobileLogin['device_name'] ?? $request->session()->pull(self::MOBILE_DEVICE_NAME_KEY);

        if ($this->hasMobileState($request) && ! $mobileRedirectUri) {
            $fallbackRedirectUri = $this->defaultMobileRedirectUri();

            abort_if(! $fallbackRedirectUri, Response::HTTP_BAD_REQUEST, 'Mobile Google sign-in state is invalid or expired.');

            return $this->redirectToMobile($fallbackRedirectUri, [
                'error' => 'mobile_oauth_state_expired',
                'message' => 'Mobile Google sign-in state is invalid or expired.',
            ]);
        }

        try {
            $googleUser = $mobileRedirectUri
                ? Socialite::driver('google')->stateless()->user()
                : Socialite::driver('google')->user();
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

    private function defaultMobileRedirectUri(): ?string
    {
        return collect(config('auth.mobile_tokens.redirect_uris', []))
            ->filter()
            ->map(fn (string $uri): string => trim($uri))
            ->first();
    }

    /**
     * @return array{redirect_uri: string, device_name: string}|null
     */
    private function mobileLoginFromState(Request $request): ?array
    {
        $state = $request->string('state')->toString();

        if (! $this->hasMobileState($request)) {
            return null;
        }

        $payload = Cache::pull($this->mobileStateCacheKey($state));

        if (! is_array($payload)) {
            return null;
        }

        $redirectUri = isset($payload['redirect_uri']) && is_string($payload['redirect_uri'])
            ? $payload['redirect_uri']
            : null;
        $deviceName = isset($payload['device_name']) && is_string($payload['device_name'])
            ? $payload['device_name']
            : 'React Native';

        if (! $redirectUri || ! $this->isAllowedMobileRedirectUri($redirectUri)) {
            return null;
        }

        return [
            'redirect_uri' => $redirectUri,
            'device_name' => $deviceName,
        ];
    }

    private function hasMobileState(Request $request): bool
    {
        return Str::startsWith($request->string('state')->toString(), self::MOBILE_STATE_PREFIX);
    }

    private function isAllowedMobileRedirectUri(string $uri): bool
    {
        return collect(config('auth.mobile_tokens.redirect_uris', []))
            ->filter()
            ->map(fn (string $allowedUri): string => trim($allowedUri))
            ->contains($uri);
    }

    private function mobileStateCacheKey(string $state): string
    {
        return self::MOBILE_STATE_CACHE_PREFIX.$state;
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
