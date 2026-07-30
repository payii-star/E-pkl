<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // Import Str untuk membuat slug

class CategoryController extends Controller
{
    /**
     * Menampilkan semua kategori.
     */
    public function index()
    {
        $categories = Category::latest()->get();
        return response()->json($categories);
    }

    /**
     * Menyimpan kategori baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name), // Buat slug otomatis dari nama
            'description' => $request->description,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Menampilkan satu kategori spesifik.
     */
    public function show(Category $category)
    {
        return response()->json($category);
    }

    /**
     * Memperbarui kategori yang ada.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            // Pastikan validasi 'unique' mengabaikan kategori saat ini
            'name' => 'required|string|unique:categories,name,' . $category->id . '|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
        ]);

        return response()->json($category);
    }

    /**
     * Menghapus kategori.
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(null, 204); // 204 No Content, artinya berhasil dihapus
    }
}