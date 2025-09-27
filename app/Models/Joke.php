<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Joke extends Model
{
    /** @use HasFactory<\Database\Factories\JokeFactory> */
    use HasFactory;

    protected $table = 'jokes';

    protected $fillable = [
        'title',
        'content',
        'user_id',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
