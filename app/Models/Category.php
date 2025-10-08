<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * Categories have many jokes
     *
     * @return BelongsToMany
     */
    public function jokes(): BelongsToMany
    {
        return $this->belongsToMany(Joke::class);
    }
}
