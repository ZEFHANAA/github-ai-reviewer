<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'github_created_at' => 'immutable_datetime',
            'github_updated_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<Analysis, $this> */
    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }
}
