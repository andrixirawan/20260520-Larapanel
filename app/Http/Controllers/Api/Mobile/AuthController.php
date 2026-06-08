<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Mobile\CreateMobileAuthToken;
use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\UserResource;
use App\Models\MobileAuthToken;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use PasswordValidationRules;

    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        $request->validate([
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $creator->create($request->only([
            'name',
            'email',
            'password',
            'password_confirmation',
        ]));

        event(new Registered($user));

        return $this->tokenResponse($user, $request, Response::HTTP_CREATED);
    }

    /**
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::query()
            ->where('email', $request->string('email')->lower()->toString())
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            if (! $request->filled('code') && ! $request->filled('recovery_code')) {
                return response()->json([
                    'message' => 'Two-factor authentication is required.',
                    'code' => 'two_factor_required',
                    'two_factor_required' => true,
                ], Response::HTTP_CONFLICT);
            }

            if (! $this->hasValidTwoFactorCredential($user, $request)) {
                RateLimiter::hit($this->throttleKey($request));

                throw ValidationException::withMessages([
                    'code' => [__('The provided two factor authentication code was invalid.')],
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey($request));

        return $this->tokenResponse($user, $request);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'data' => UserResource::make($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_access_token')?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->mobileAuthTokens()->delete();

        return response()->json([
            'message' => 'All mobile sessions have been logged out.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => __($status),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return response()->json([
            'message' => __('If an account exists for this email, a password reset link has been sent.'),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => $this->passwordRules(),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->mobileAuthTokens()->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function sendVerificationNotification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.',
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent.',
        ], Response::HTTP_ACCEPTED);
    }

    private function tokenResponse(User $user, Request $request, int $status = Response::HTTP_OK): JsonResponse
    {
        [$plainTextToken, $accessToken] = $this->createToken($user, $request);

        return response()->json([
            'message' => 'Authenticated.',
            'token_type' => 'Bearer',
            'access_token' => $plainTextToken,
            'expires_at' => $accessToken->expires_at?->toISOString(),
            'user' => UserResource::make($user),
        ], $status);
    }

    /**
     * @return array{0: string, 1: MobileAuthToken}
     */
    private function createToken(User $user, Request $request): array
    {
        return app(CreateMobileAuthToken::class)->handle($user, $request);
    }

    private function hasValidTwoFactorCredential(User $user, Request $request): bool
    {
        if ($request->filled('code')) {
            return app(TwoFactorAuthenticationProvider::class)->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $request->string('code')->toString(),
            );
        }

        if (! $request->filled('recovery_code')) {
            return false;
        }

        $recoveryCode = collect($user->recoveryCodes())->first(function (string $code) use ($request): bool {
            return hash_equals($code, $request->string('recovery_code')->toString());
        });

        if (! $recoveryCode) {
            return false;
        }

        $user->replaceRecoveryCode($recoveryCode);

        return true;
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
