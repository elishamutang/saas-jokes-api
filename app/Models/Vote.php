<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vote extends Model
{
    use HasFactory;

    protected $table = 'votes';

    protected $fillable = [
        'user_id',
        'joke_id',
        'rating',
    ];

    protected function casts(): array
    {
        return [];
    }

    protected $appends = ['average_rating'];

    /**
     * Returns the average rating of a joke.
     *
     * @return Attribute
     */
    protected function averageRating(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this::calculateAverageRating((int) $attributes['joke_id'])
        );
    }

    /**
     * Calculates the average rating of a joke.
     *
     * @param int $jokeId
     * @return Float
     */
    public static function calculateAverageRating(int $jokeId): Float
    {
        // Retrieves all votes for a particular joke.
        $votes = Vote::where('joke_id', $jokeId)->get();

        $totalVotes = $votes->count();
        $netRating = $votes->sum('rating');

        if ($netRating > 0) {
            return round(($netRating / $totalVotes) * 100, 2);
        }

        return 0;
    }

    /**
     * A Vote has one Joke.
     *
     * @return HasOne
     */
    public function joke(): HasOne
    {
        return $this->hasOne(Joke::class);
    }

    /**
     * A Vote belongs to one User.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
