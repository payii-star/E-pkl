<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// UBAH INI: Gunakan Authenticatable agar model bisa login
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens; // TAMBAHKAN INI: Untuk kemampuan API token

class Member extends Authenticatable // UBAH INI: extends Authenticatable
{
    // TAMBAHKAN INI: Pasang kemampuan Notifiable dan HasApiTokens
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'address',
        'password', // TAMBAHKAN INI
        'member_id',
        'points',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password', // TAMBAHKAN INI: Untuk keamanan
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // TAMBAHKAN INI: Untuk hashing otomatis jika perlu
    ];

    protected static function boot()
    {
        parent::boot();

        // Event ini berjalan SETIAP KALI member baru akan dibuat (creating).
        static::creating(function ($model) {
            // Jika member_id belum diisi, buatkan UUID baru.
            if (empty($model->member_id)) {
                $model->member_id = (string) Str::uuid();
            }
        });
    }
}