<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalActivity extends Model
{
    protected $fillable = ['journal_id', 'jam_mulai', 'jam_selesai', 'kegiatan'];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}