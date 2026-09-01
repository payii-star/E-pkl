<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    protected $fillable = ['task_id', 'path', 'original_name', 'mime_type'];

    protected $appends = ['url', 'is_image'];

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}