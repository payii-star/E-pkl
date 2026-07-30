<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    public function index()
    {
        // Ambil semua varian beserta pilihan-pilihannya
        return Variant::with('options')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|unique:variants,name']);
        return Variant::create($validated);
    }

    public function update(Request $request, Variant $variant)
    {
        $validated = $request->validate(['name' => 'required|string|unique:variants,name,' . $variant->id]);
        $variant->update($validated);
        return $variant;
    }

    public function destroy(Variant $variant)
    {
        $variant->delete();
        return response()->noContent();
    }
}