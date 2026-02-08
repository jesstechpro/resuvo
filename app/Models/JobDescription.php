<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobDescription extends Model
{
    protected $fillable = ['user_id', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedResumes(): HasMany
    {
        return $this->hasMany(Resume::class, 'job_description_id')->where('type', Resume::TYPE_GENERATED);
    }
}
