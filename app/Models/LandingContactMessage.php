<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingContactMessage extends Model
{
    protected $table = 'landing_contact_messages';

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'is_read'];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
