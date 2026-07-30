<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Cek jika ada data 'variants' yang dikirim
        if ($this->has('variants')) {
            $variants = $this->variants;
            
            // Loop melalui setiap varian yang dikirim
            foreach ($variants as $key => $variant) {
                // Jika 'options' ada dan merupakan string (dari JSON.stringify)
                if (isset($variant['options']) && is_string($variant['options'])) {
                    // Ubah string JSON kembali menjadi array PHP
                    $variants[$key]['options'] = json_decode($variant['options'], true);
                }
            }
            
            // Gabungkan kembali data varian yang sudah diperbaiki ke dalam request
            $this->merge([
                'variants' => $variants,
            ]);
        }
    }

    public function rules()
    {
        // Aturan validasi Anda yang sudah ada
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // 2MB

            // Aturan untuk Produk Simpel (jika 'variants' tidak ada)
            'sku' => 'required_without:variants|string|nullable',
            'price' => 'required_without:variants|numeric|nullable',
            'stock' => 'required_without:variants|integer|nullable',

            // Aturan untuk Produk dengan Varian
            'variants' => 'required_without_all:sku,price,stock|array|min:1',
            'variants.*.sku' => 'required|string',
            'variants.*.price' => 'required|numeric',
            'variants.*.stock' => 'required|integer',
            'variants.*.options' => 'required|array',
        ];
    }
}