<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'day',
        'is_working_day',
        'start_time',
        'end_time',
        'min_check_in_time',
        'max_check_out_time',
    ];

    protected $casts = [
        'is_working_day' => 'boolean',
    ];
}