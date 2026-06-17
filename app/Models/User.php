<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Models\DailyQuest\Task;
use App\Models\DailyQuest\TaskCategory;
use App\Models\DailyQuest\TaskInstance;
use App\Models\DailyQuest\UserDailyStat;
use App\Models\Post\Post;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'avatar',
    'google_id',
    'google_avatar',
    'email_verified_at',
    'timezone',
    'total_points',
    'current_streak',
    'longest_streak',
    'last_active_date',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicId, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'total_points' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_active_date' => 'date',
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
                    return $value;
                }

                return $this->versionAvatarUrl(route('profile.avatar', $this, false));
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

    /**
     * @return HasMany<MobileAuthToken, $this>
     */
    public function mobileAuthTokens(): HasMany
    {
        return $this->hasMany(MobileAuthToken::class);
    }

    /**
     * @return HasMany<TaskCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(TaskCategory::class);
    }

    /**
     * Backward-compatible alias for the daily quest category relation.
     *
     * @return HasMany<TaskCategory, $this>
     */
    public function taskCategories(): HasMany
    {
        return $this->categories();
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<TaskInstance, $this>
     */
    public function taskInstances(): HasMany
    {
        return $this->hasMany(TaskInstance::class);
    }

    /**
     * @return HasMany<UserDailyStat, $this>
     */
    public function dailyStats(): HasMany
    {
        return $this->hasMany(UserDailyStat::class);
    }

    public function hasCompletedAllTasksOnDate(Carbon|string $date): bool
    {
        $targetDate = $date instanceof Carbon ? $date->toDateString() : $date;

        $stats = $this->taskInstances()
            ->whereDate('scheduled_date', $targetDate)
            ->selectRaw('COUNT(*) as total_tasks')
            ->selectRaw('COUNT(completed_at) as completed_tasks')
            ->first();

        if (! $stats) {
            return false;
        }

        $totalTasks = (int) data_get($stats, 'total_tasks', 0);
        $completedTasks = (int) data_get($stats, 'completed_tasks', 0);

        return $totalTasks > 0 && $totalTasks === $completedTasks;
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    private function versionAvatarUrl(string $url): string
    {
        $version = $this->updated_at?->getTimestamp();

        if (! $version) {
            return $url;
        }

        return $url.(Str::contains($url, '?') ? '&' : '?').'v='.$version;
    }
}
