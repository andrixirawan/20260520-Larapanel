<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
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
}
