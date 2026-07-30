<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::first());
    }

    public function update(Request $request)
    {
        if (request()->wantsJson()) {
            // 1. Validasi disesuaikan dengan field yang dibutuhkan
            $request->validate([
                'app' => 'required',
                'description' => 'required',
                'alamat' => 'required',
                'telepon' => 'required',
                'email' => 'required|email',
                // File dibuat tidak wajib (nullable)
                'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'bg_auth' => 'nullable|image|mimes:jpeg,png,jpg|max:8192',
            ]);

            $setting = Setting::first();
            $data = $request->except(['logo', 'bg_auth']); // Ambil semua data kecuali file

            // Proses upload 'logo' jika ada file baru
            if ($request->hasFile('logo')) {
                // Hapus logo lama jika ada
                if ($setting->logo) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $setting->logo));
                }
                // Simpan logo baru
                $data['logo'] = '/storage/' . $request->file('logo')->store('setting', 'public');
            }

            // Proses upload 'bg_auth' jika ada file baru
            if ($request->hasFile('bg_auth')) {
                // Hapus bg_auth lama jika ada
                if ($setting->bg_auth) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $setting->bg_auth));
                }
                // Simpan bg_auth baru
                $data['bg_auth'] = '/storage/' . $request->file('bg_auth')->store('setting', 'public');
            }

            // --- Logika untuk 'pemerintah', 'dinas', dan 'banner' telah dihapus ---

            $setting->update($data);

            return response()->json([
                'message' => 'Berhasil memperbarui data Konfigurasi Website',
                'data' => $setting
            ]);
        } else {
            abort(404);
        }
    }
}