<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingClientLogo extends Model
{
    protected $table = 'landing_client_logos';

    protected $fillable = ['name', 'short', 'logo', 'urutan', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
