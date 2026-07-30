<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 
        'transaction_id', 
        'points_change', 
        'description',
        // 'type' // Tambahkan ini jika di database Anda ada kolom 'type' (earn/redeem/refund)
    ];

    // Relasi opsional jika dibutuhkan
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}