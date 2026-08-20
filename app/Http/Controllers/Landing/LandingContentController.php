<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandingContentController extends Controller
{
    // Key yang statusnya "read-only" lewat form ini (butuh UI khusus, bukan text field biasa)
    private const EXCLUDED_KEYS = ['client_logos'];

    private const EDITABLE_KEYS = [
        'app_name', 'logo', 'description', 'email', 'whatsapp', 'phone', 'address',
        'hero_title', 'hero_desc', 'cta_primary_label', 'cta_primary_url',
        'cta_secondary_label', 'cta_secondary_url', 'proof_text',
        'contact_hero_title', 'contact_hero_subtitle', 'contact_maps_url',
        'projects_page_label', 'projects_page_title', 'projects_page_subtitle',
        'ceo_name', 'ceo_position', 'ceo_comment', 'ceo_photo',
    ];

    // Route: GET /master/landing-content
    public function adminIndex()
    {
        $data = LandingContent::whereIn('key', self::EDITABLE_KEYS)
            ->get()
            ->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // Route: POST /master/landing-content
    public function update(Request $request)
    {
        $rules = [];
foreach (self::EDITABLE_KEYS as $key) {
    $rules[$key] = in_array($key, ['logo', 'ceo_photo'])
        ? 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        : 'nullable|string';
}

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach (self::EDITABLE_KEYS as $key) {
    if (in_array($key, ['logo', 'ceo_photo'])) {
        if ($request->hasFile($key)) {
            $old = LandingContent::where('key', $key)->first();
            if ($old && $old->value && Storage::disk('public')->exists($old->value)) {
                Storage::disk('public')->delete($old->value);
            }
            $folder = $key === 'logo' ? 'logo' : 'ceo';
            $path = $request->file($key)->store($folder, 'public');
            LandingContent::updateOrCreate(['key' => $key], ['value' => $path, 'type' => 'image']);
        }
        continue;
    }

    if ($request->has($key)) {
        LandingContent::updateOrCreate(
            ['key' => $key],
            ['value' => $request->input($key), 'type' => 'text']
        );
    }
}

        return response()->json([
            'success' => true,
            'message' => 'Landing content berhasil diperbarui',
        ]);
    }

    // Route: GET /master/landing-content/client-logos
    public function clientLogos()
    {
        $row = LandingContent::where('key', 'client_logos')->first();
        $logos = $row && $row->value ? json_decode($row->value, true) : [];

        return response()->json([
            'success' => true,
            'data' => $logos ?: [],
        ]);
    }

    // Route: POST /master/landing-content/client-logos
    public function updateClientLogos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logos'             => 'required|array',
            'logos.*.name'      => 'required|string|max:255',
            'logos.*.short'     => 'nullable|string|max:100',
            'logos.*.url'       => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Rapikan supaya cuma field yang relevan yang tersimpan
        $logos = collect($request->input('logos'))->map(fn ($l) => [
            'name'  => $l['name'] ?? '',
            'short' => $l['short'] ?? '',
            'url'   => $l['url'] ?? '',
        ])->values()->all();

        LandingContent::updateOrCreate(
            ['key' => 'client_logos'],
            ['value' => json_encode($logos), 'type' => 'json']
        );

        return response()->json([
            'success' => true,
            'message' => 'Client logos berhasil diperbarui',
            'data' => $logos,
        ]);
    }
}