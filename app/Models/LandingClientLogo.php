<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingClientLogo extends Model
{
    use HasFactory;

    protected $table = 'landing_client_logos';

    protected $fillable = ['name', 'short', 'logo', 'urutan', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['url'];

    // Accessor: kirim URL logo lengkap ke frontend (field "url" yang dipakai Index.vue/Form.vue)
    public function getUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}