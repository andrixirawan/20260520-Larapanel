<?php

namespace App\Models\Post;

use App\Models\Concerns\HasPublicId;
use App\Models\User;
use Database\Factories\Post\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasPublicId;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'user_id',
        'cover',
        'body',
        'author',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = [
        'cover_url',
    ];

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->cover) {
            return null;
        }

        return route('posts.cover', $this, false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
