<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LandingMenuController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingMenu::orderBy('order')->orderBy('id')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingMenu::where('is_active', true)->orderBy('order')->get()
        ]);
    }

    public function show(LandingMenu $menu)
    {
        return response()->json([
            'success' => true, 
            'data' => $menu
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'url'       => 'required|string|max:255',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['order'] = $data['order'] ?? ((int) (LandingMenu::max('order') ?? 0) + 1);
        $data['is_active'] = $request->boolean('is_active', true);

        $menu = LandingMenu::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Menu berhasil ditambahkan', 
            'data'    => $menu
        ], 201);
    }

    public function update(Request $request, LandingMenu $menu)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'url'       => 'required|string|max:255',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $menu->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Menu berhasil diperbarui', 
            'data'    => $menu
        ]);
    }

    public function destroy(LandingMenu $menu)
    {
        $menu->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Menu berhasil dihapus'
        ]);
    }

    // Dipanggil dari halaman depan Landing tiap kali menu diklik (analytics ringan)
    public function trackClick($menuId)
    {
        return response()->json([
            'success' => true
        ]);
    }
}