<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LandingContentController extends Controller
{
    // GET /front/content (publik, dipanggil Landing)
    public function index()
    {
        return response()->json(['data' => LandingContent::first()]);
    }

    // POST /master/landing-content
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'email' => 'nullable|email',
            'whatsapp' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'hero_title' => 'nullable|string',
            'hero_desc' => 'nullable|string',
            'cta_primary_label' => 'nullable|string|max:100',
            'cta_primary_url' => 'nullable|string|max:255',
            'cta_secondary_label' => 'nullable|string|max:100',
            'cta_secondary_url' => 'nullable|string|max:255',
            'proof_text' => 'nullable|string',
            'contact_hero_title' => 'nullable|string',
            'contact_hero_subtitle' => 'nullable|string',
            'contact_maps_url' => 'nullable|string',
            'projects_page_label' => 'nullable|string|max:100',
            'projects_page_title' => 'nullable|string',
            'projects_page_subtitle' => 'nullable|string',
            'ceo_name' => 'nullable|string|max:255',
            'ceo_position' => 'nullable|string|max:255',
            'ceo_comment' => 'nullable|string',
            'ceo_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $content = LandingContent::first();

        foreach (['logo', 'ceo_photo'] as $fileField) {
            if ($request->hasFile($fileField)) {
                if ($content && $content->{$fileField}) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $content->{$fileField}));
                }
                $validated[$fileField] = '/storage/' . $request->file($fileField)->store('landing/content', 'public');
            }
        }

        if ($content) {
            $content->update($validated);
        } else {
            $content = LandingContent::create($validated);
        }

        return response()->json(['message' => 'Konten landing berhasil disimpan', 'data' => $content]);
    }
}
