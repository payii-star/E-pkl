<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_in_photo',
        'check_out_time',
        'check_out_photo',
        'status',
        'location',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function dateColumn(): string
    {
        return Schema::hasColumn('attendances', 'date') ? 'date' : 'tanggal';
    }

    public static function checkInTimeColumn(): string
    {
        return Schema::hasColumn('attendances', 'check_in_time') ? 'check_in_time' : 'jam_masuk';
    }

    public static function checkOutTimeColumn(): string
    {
        return Schema::hasColumn('attendances', 'check_out_time') ? 'check_out_time' : 'jam_keluar';
    }

    public static function checkInPhotoColumn(): string
    {
        return Schema::hasColumn('attendances', 'check_in_photo') ? 'check_in_photo' : 'foto_masuk';
    }

    public static function checkOutPhotoColumn(): string
    {
        return Schema::hasColumn('attendances', 'check_out_photo') ? 'check_out_photo' : 'foto_keluar';
    }

    public static function locationColumn(): string
    {
        return Schema::hasColumn('attendances', 'location') ? 'location' : 'lokasi_masuk';
    }
}