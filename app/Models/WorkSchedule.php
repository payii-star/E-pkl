<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'day',
        'is_working_day',
        'start_time',
        'end_time',
        'min_check_in_time',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_working_day' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}