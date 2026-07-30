<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Sebuah Varian (misal: Ukuran) memiliki banyak pilihan (S, M, L).
     */
    public function options()
    {
        return $this->hasMany(VariantOption::class);
    }
}