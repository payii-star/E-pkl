<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingFooterSetting;
use App\Models\LandingFooterSocial;
use Illuminate\Http\Request;

class FooterController extends Controller
{
    // GET /footer/landing (publik, dipanggil Landing)
    public function index()
    {
        return response()->json([
            'setting' => LandingFooterSetting::first(),
            'socials' => LandingFooterSocial::all(),
        ]);
    }

    // POST /master/footer/setting
    public function updateSetting(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'copyright' => 'nullable|string',
        ]);

        $setting = LandingFooterSetting::first();
        if ($setting) {
            $setting->update($validated);
        } else {
            $setting = LandingFooterSetting::create($validated);
        }

        return response()->json(['message' => 'Info perusahaan berhasil disimpan', 'data' => $setting]);
    }

    // GET /master/footer/socials
    public function socials()
    {
        return response()->json(['data' => LandingFooterSocial::all()]);
    }

    // POST /master/footer/socials/store
    public function storeSocial(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|string|max:100',
            'url' => 'required|url',
        ]);

        $data = LandingFooterSocial::create($validated);
        return response()->json(['message' => 'Sosial media berhasil ditambahkan', 'data' => $data], 201);
    }

    // PUT /master/footer/socials/{id}
    public function updateSocial(Request $request, string $id)
    {
        $social = LandingFooterSocial::findOrFail($id);
        $validated = $request->validate([
            'platform' => 'required|string|max:100',
            'url' => 'required|url',
        ]);
        $social->update($validated);
        return response()->json(['message' => 'Sosial media berhasil diperbarui', 'data' => $social]);
    }

    // DELETE /master/footer/socials/{id}
    public function destroySocial(string $id)
    {
        LandingFooterSocial::findOrFail($id)->delete();
        return response()->json(['message' => 'Sosial media berhasil dihapus']);
    }
}
