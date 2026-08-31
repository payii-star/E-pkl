<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandingContentPageController extends Controller
{
    /**
     * Landing Content itu data singleton (satu baris info umum, hero, CTA,
     * halaman kontak, komentar CEO, halaman projects) — bukan daftar
     * halaman/artikel. Frontend (landing-content/Index.vue) cuma pernah
     * manggil GET & POST ke /master/landing-content tanpa ID.
     */
    public function adminIndex()
    {
        return response()->json([
            'success' => true,
            'data' => LandingContent::first()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true,
            'data' => LandingContent::first()
        ]);
    }

    public function show(LandingContent $contentPage)
    {
        return response()->json([
            'success' => true,
            'data' => $contentPage
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name'               => 'required|string|max:255',
            'description'            => 'nullable|string',
            'email'                  => 'nullable|email|max:255',
            'whatsapp'               => 'nullable|string|max:50',
            'phone'                  => 'nullable|string|max:50',
            'address'                => 'nullable|string',
            'hero_title'             => 'nullable|string',
            'hero_desc'              => 'nullable|string',
            'cta_primary_label'      => 'nullable|string|max:255',
            'cta_primary_url'        => 'nullable|string|max:255',
            'cta_secondary_label'    => 'nullable|string|max:255',
            'cta_secondary_url'      => 'nullable|string|max:255',
            'proof_text'             => 'nullable|string',
            'contact_hero_title'     => 'nullable|string|max:255',
            'contact_hero_subtitle'  => 'nullable|string',
            'contact_maps_url'       => 'nullable|string|max:255',
            'projects_page_label'    => 'nullable|string|max:255',
            'projects_page_title'    => 'nullable|string|max:255',
            'projects_page_subtitle' => 'nullable|string',
            'ceo_name'               => 'nullable|string|max:255',
            'ceo_position'           => 'nullable|string|max:255',
            'ceo_comment'            => 'nullable|string',
            'logo'                   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'ceo_photo'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except(['logo', 'ceo_photo']);

        $content = LandingContent::first();

        if ($request->hasFile('logo')) {
            if ($content?->logo) {
                Storage::disk('public')->delete($content->logo);
            }
            $data['logo'] = $request->file('logo')->store('landing/content', 'public');
        }

        if ($request->hasFile('ceo_photo')) {
            if ($content?->ceo_photo) {
                Storage::disk('public')->delete($content->ceo_photo);
            }
            $data['ceo_photo'] = $request->file('ceo_photo')->store('landing/content', 'public');
        }

        if ($content) {
            $content->update($data);
        } else {
            $content = LandingContent::create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Landing content berhasil disimpan',
            'data'    => $content
        ]);
    }

    /**
     * Rute {contentPage} ini nggak pernah dipanggil dari frontend saat ini
     * (yang dipakai cuma store() lewat POST tanpa ID), tapi dibiarkan aktif
     * untuk kompatibilitas kalau ada pemanggilan langsung ke /master/landing-content/{id}.
     */
    public function update(Request $request, LandingContent $contentPage)
    {
        return $this->store($request);
    }

    public function destroy(LandingContent $contentPage)
    {
        return response()->json([
            'message' => 'Landing content adalah data singleton dan tidak bisa dihapus'
        ], 403);
    }
}