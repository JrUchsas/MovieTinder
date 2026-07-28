<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    /** @use HasFactory<\Database\Factories\MovieFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tmdb_id',
        'title',
        'poster_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tmdb_id' => 'integer',
        ];
    }

    /**
     * Get the swipes for the movie.
     *
     * @return HasMany<Swipe, $this>
     */
    public function swipes(): HasMany
    {
        return $this->hasMany(Swipe::class);
    }
}
