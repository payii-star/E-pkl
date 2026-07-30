<?php

namespace App\Models;

use App\Models\User;
use App\Models\Promo; 
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_number',
        'user_id',
        'member_id',
        'original_amount', 
        'discount_amount',
        'points_redeemed',       
        'point_discount_amount', 
        'points_earned',
        'final_amount',    
        'paid_amount',
        'change_amount',
        'payment_method',
        'promo_code',      
        'promo_id',        
        'status',   
        'snap_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function getRouteKeyName()
    {
        return 'invoice_number';
    }
}