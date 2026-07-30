<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'reserved_stock', 
        'options',
        'barcode', 
    ];

    /**
     * Tipe data 'options' harus di-cast sebagai array/json.
     */
    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Accessor untuk menghitung stok yang tersedia (bisa dijual).
     *
     * @return int
     */
    public function getAvailableStockAttribute()
    {
        return $this->stock - $this->reserved_stock;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}