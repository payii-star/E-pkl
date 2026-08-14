<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingMenu extends Model
{
    protected $table = 'landing_menu';

    protected $fillable = ['name', 'url', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
