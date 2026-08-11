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
            $request->validate([
                'app' => 'required',
                'description' => 'required',
                'alamat' => 'required',
                'telepon' => 'required',
                'email' => 'required|email',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'dashboard_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'bg_auth' => 'nullable|image|mimes:jpeg,png,jpg|max:8192',
            ]);

            $setting = Setting::first();
            $data = $request->except(['logo', 'dashboard_logo', 'bg_auth']);

            if ($request->hasFile('logo')) {
                if ($setting->logo) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $setting->logo));
                }
                $data['logo'] = '/storage/' . $request->file('logo')->store('setting', 'public');
            }

            if ($request->hasFile('dashboard_logo')) {
                if ($setting->dashboard_logo) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $setting->dashboard_logo));
                }
                $data['dashboard_logo'] = '/storage/' . $request->file('dashboard_logo')->store('setting', 'public');
            }

            if ($request->hasFile('bg_auth')) {
                if ($setting->bg_auth) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $setting->bg_auth));
                }
                $data['bg_auth'] = '/storage/' . $request->file('bg_auth')->store('setting', 'public');
            }

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