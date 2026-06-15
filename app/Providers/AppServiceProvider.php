<?php

namespace App\Providers;

use App\Listeners\SendLoginNotification;
use App\Listeners\SendLogoutNotification;
use App\Models\Post\Post;
use App\Policies\Post\PostPolicy;
use App\Support\AccessControl;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerFeatureMigrationPaths();
        $this->configureAuthActivityNotifications();
        $this->configureAuthorization();
        $this->configureDefaults();
    }

    protected function registerFeatureMigrationPaths(): void
    {
        $featureMigrationPaths = collect(File::directories(database_path('migrations')))
            ->filter(fn (string $path): bool => is_dir($path))
            ->values()
            ->all();

        if ($featureMigrationPaths !== []) {
            $this->loadMigrationsFrom($featureMigrationPaths);
        }
    }

    /**
     * Send account activity emails after successful login and logout.
     */
    protected function configureAuthActivityNotifications(): void
    {
        Event::listen(Login::class, SendLoginNotification::class);
        Event::listen(Logout::class, SendLogoutNotification::class);
    }

    /**
     * Let the super-admin role pass all Gate and can() authorization checks.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn ($user, string $ability): ?bool => $user->hasRole(AccessControl::ROLE_SUPER_ADMIN) ? true : null);
        Gate::policy(Post::class, PostPolicy::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
