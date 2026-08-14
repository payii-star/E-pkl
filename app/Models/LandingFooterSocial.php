<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingFooterSocial extends Model
{
    protected $table = 'landing_footer_socials';

    protected $fillable = ['platform', 'url'];
}
