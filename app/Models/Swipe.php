<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Swipe extends Model
{
    /** @use HasFactory<\Database\Factories\SwipeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'movie_id',
        'is_liked',
        'watch_scheduled_at',
        'is_watched',
        'user_rating',
        'user_review',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'movie_id' => 'integer',
            'is_liked' => 'boolean',
            'is_watched' => 'boolean',
            'user_rating' => 'integer',
            'user_review' => 'string',
            'watch_scheduled_at' => 'datetime',
        ];
    }

    /**
     * Get the user that made the swipe.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the movie that was swiped.
     *
     * @return BelongsTo<Movie, $this>
     */
    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
