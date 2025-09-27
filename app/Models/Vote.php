<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function joke(): HasOne
    {
        return $this->hasOne(Joke::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
