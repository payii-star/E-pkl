<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingTeam extends Model
{
    protected $table = 'landing_teams';

    protected $fillable = ['name', 'position', 'image', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
