<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use Uuid, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'photo',
        'nim_nis',
        'asal_instansi',
        'posisi',
        'tanggal_mulai',
        'tanggal_selesai',
        'atasan_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    protected $appends = ['permission', 'role'];

    protected static function booted()
    {
        static::deleted(function ($user) {
            if ($user->photo != null && $user->photo != '') {
                $old_photo = str_replace('/storage/', '', $user->photo);
                Storage::disk('public')->delete($old_photo);
            }
        });
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getRoleAttribute()
    {
        return $this->roles()->first();
    }

    public function getPermissionAttribute()
    {
        return $this->getAllPermissions()->pluck('name');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    public function intern()
    {
        return $this->hasOne(Intern::class);
    }

    // Atasan/supervisor dari user ini (untuk alur approval jurnal)
    public function atasan()
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    // Daftar karyawan/intern yang berada di bawah user ini (jika dia atasan)
    public function bawahan()
    {
        return $this->hasMany(User::class, 'atasan_id');
    }
}
