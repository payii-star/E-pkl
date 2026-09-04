<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingTestimonial extends Model
{
    protected $table = 'landing_testimonials';

    protected $fillable = ['name', 'position', 'photo', 'message', 'placement', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

