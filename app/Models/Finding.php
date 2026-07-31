<?php

namespace App\Models;

use App\Enums\FindingSeverity;
use App\Enums\FindingSource;
use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finding extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'source' => FindingSource::class,
            'severity' => FindingSeverity::class,
            'status' => FindingStatus::class,
        ];
    }

    /** @return BelongsTo<Analysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}
