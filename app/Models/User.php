<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'asal_instansi_address',
        'asal_instansi_latitude',
        'asal_instansi_longitude',
        'asal_instansi_place_id',
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
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'asal_instansi_latitude' => 'float',
        'asal_instansi_longitude' => 'float',
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
        // Prioritaskan role hr-admin di atas role lain (mis. atasan) jika suatu
        // akun kebetulan memiliki lebih dari satu role. Ini mencegah sidebar/menu
        // salah mendeteksi akun HR Admin sebagai "atasan" biasa.
        $rolePriority = ['hr-admin', 'atasan', 'karyawan'];

        $roles = $this->roles()->get();
        foreach ($rolePriority as $roleName) {
            $match = $roles->firstWhere('name', $roleName);
            if ($match) {
                return $match;
            }
        }

        return $roles->first();
    }

    public function getPermissionAttribute()
    {
        return $this->getAllPermissions()->pluck('name');
    }

    public function faceProfile(): HasOne
    {
        return $this->hasOne(FaceProfile::class);
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function bawahan(): HasMany
    {
        return $this->hasMany(User::class, 'atasan_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
