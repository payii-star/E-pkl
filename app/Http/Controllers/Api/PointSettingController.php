<?php
// app/Http/Controllers/Api/PointSettingController.php

namespace App\Http\Controllers\Api; // Namespace sudah benar

use App\Http\Controllers\Controller;
use App\Models\PointSetting;
use Illuminate\Http\Request;

class PointSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'rupiah_per_point' => PointSetting::firstOrCreate(
                ['key' => 'rupiah_per_point'],
                ['value' => '10000']
            )->value,
            'point_redemption_value' => PointSetting::firstOrCreate(
                ['key' => 'point_redemption_value'],
                ['value' => '100']
            )->value,
        ];

        return response()->json($settings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rupiah_per_point' => 'required|integer|min:1',
            'point_redemption_value' => 'required|integer|min:1',
        ]);

        foreach ($validated as $key => $value) {
            PointSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Pengaturan poin berhasil disimpan.']);
    }
}