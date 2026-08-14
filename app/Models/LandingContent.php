<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingContent extends Model
{
    protected $table = 'landing_content';

    protected $fillable = [
        'app_name', 'description', 'logo', 'email', 'whatsapp', 'phone', 'address',
        'hero_title', 'hero_desc', 'cta_primary_label', 'cta_primary_url',
        'cta_secondary_label', 'cta_secondary_url', 'proof_text',
        'contact_hero_title', 'contact_hero_subtitle', 'contact_maps_url',
        'projects_page_label', 'projects_page_title', 'projects_page_subtitle',
        'ceo_name', 'ceo_position', 'ceo_comment', 'ceo_photo',
    ];
}
