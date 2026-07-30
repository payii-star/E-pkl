<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     * Perhatikan bahwa sku, price, dan stock tidak ada di sini.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category_id',
        'image_path', // Menyimpan path file, bukan URL
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array
     */
    protected $casts = [
        'category_id' => 'integer',
    ];

    /**
     * Atribut tambahan yang akan selalu ditambahkan saat model diubah menjadi array/JSON.
     * Ini memastikan frontend selalu menerima 'image_url'.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * Mendefinisikan relasi "belongsTo" ke model Category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Mendefinisikan relasi "hasMany" ke model ProductVariant.
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Accessor untuk menghasilkan atribut 'image_url' secara dinamis.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: function () {  
                // Jika ada path gambar, buat URL lengkapnya.
                if ($this->image_path) {
                    // BENAR: Panggil method url() langsung dari Storage facade
                    return asset(Storage::url($this->image_path));
                }
                
                // Jika tidak ada gambar, berikan URL placeholder default.
                // 
                return asset('media/svg/files/box.svg');
            },
        );
    }
}