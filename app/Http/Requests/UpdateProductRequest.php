<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Sama seperti Store, kita perlu decode JSON options jika ada
        if ($this->has('variants')) {
            $variants = $this->variants;
            foreach ($variants as $key => $variant) {
                if (isset($variant['options']) && is_string($variant['options'])) {
                    $variants[$key]['options'] = json_decode($variant['options'], true);
                }
            }
            $this->merge(['variants' => $variants]);
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // --- ATURAN FLEKSIBEL (SAMA SEPERTI STORE) ---
            
            // Jika variants TIDAK ADA, maka sku, price, stock WAJIB (Produk Simpel)
            'sku' => 'required_without:variants|nullable|string',
            'price' => 'required_without:variants|nullable|numeric',
            'stock' => 'required_without:variants|nullable|integer',

            // Jika sku, price, stock TIDAK ADA, maka variants WAJIB (Produk Varian)
            'variants' => 'required_without_all:sku,price,stock|array|min:1',
            
            'variants.*.sku' => 'required|string',
            'variants.*.price' => 'required|numeric',
            'variants.*.stock' => 'required|integer',
            'variants.*.options' => 'required|array',
        ];
    }
}