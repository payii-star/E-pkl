<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingFooterSetting extends Model
{
    protected $table = 'landing_footer_settings';

    protected $fillable = ['company_name', 'description', 'address', 'email', 'phone', 'copyright'];
}
