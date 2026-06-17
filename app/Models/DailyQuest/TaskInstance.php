<?php

namespace App\Models\DailyQuest;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskInstance extends Model
{
    use HasFactory, HasPublicId;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'scheduled_date',
        'completed_at',
        'points_awarded',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
        'points_awarded' => 'integer',
    ];

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
