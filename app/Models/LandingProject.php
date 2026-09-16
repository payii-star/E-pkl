<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingProject extends Model
{
    protected $table = 'landing_projects';

    protected $fillable = [
        'title', 'slug', 'client_name', 'description', 'thumbnail', 'gallery', 'category', 'url', 'is_featured', 'urutan',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'gallery'     => 'array',
    ];
}