<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    public const TYPE_ORIGINAL = 'original';
    public const TYPE_GENERATED = 'generated';

    protected $fillable = ['user_id', 'job_description_id', 'type', 'path', 'filename'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }

    public function scopeOriginal(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ORIGINAL);
    }

    public function scopeGenerated(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_GENERATED);
    }
}
