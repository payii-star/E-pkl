<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingMenu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        return response()->json(['data' => LandingMenu::orderBy('order')->get()]);
    }

    public function show(string $id)
    {
        return response()->json(['data' => LandingMenu::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'order' => 'nullable|integer',
        ]);

        $data = LandingMenu::create($validated);
        return response()->json(['message' => 'Menu berhasil ditambahkan', 'data' => $data], 201);
    }

    public function update(Request $request, string $id)
    {
        $menu = LandingMenu::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'order' => 'nullable|integer',
        ]);
        $menu->update($validated);
        return response()->json(['message' => 'Menu berhasil diperbarui', 'data' => $menu]);
    }

    public function destroy(string $id)
    {
        LandingMenu::findOrFail($id)->delete();
        return response()->json(['message' => 'Menu berhasil dihapus']);
    }
}
