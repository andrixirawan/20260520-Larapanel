<?php

namespace App\Models\DailyQuest;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasPublicId, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'description',
        'icon',
        'color',
        'points',
        'recurrence_type',
        'recurrence_days',
        'recurrence_ends_at',
        'recurrence_starts_at',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'recurrence_days' => 'array',
        'recurrence_ends_at' => 'date',
        'recurrence_starts_at' => 'date',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<TaskCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    /**
     * @return HasMany<TaskInstance, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(TaskInstance::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
