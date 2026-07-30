<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use App\Models\VariantOption;
use Illuminate\Http\Request;

class VariantOptionController extends Controller
{
    public function store(Request $request, Variant $variant)
    {
        $validated = $request->validate(['name' => 'required|string']);

        $option = $variant->options()->create($validated);

        return $option;
    }

    public function destroy(VariantOption $option)
    {
        $option->delete();
        return response()->noContent();
    }
}