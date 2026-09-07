<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingClientLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Kelola data "Client / Mitra" (section "Our Clients" di landing page).
 * Datanya disimpan LANGSUNG di database E-pkl sendiri (tabel landing_client_logos),
 * BUKAN lewat proxy ke project Landing lagi.
 */
class ClientController extends Controller
{
    // GET /master/client-logos
    public function index()
    {
        $clients = LandingClientLogo::orderBy('urutan')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    // GET /front/client-logos — PUBLIK, dipanggil dari Landing buat nampilin
    // section "Our Clients". Cuma yang is_active, field seperlunya aja.
    public function publicIndex()
    {
        $clients = LandingClientLogo::where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get(['id', 'name', 'short', 'logo']);

        $clients->each(function ($client) {
            $client->setAttribute('url', $client->url);
        });

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    // GET /master/client-logos/{clientLogo}  (dipanggil ApiService.get("master/client-logos", id))
    public function show(LandingClientLogo $clientLogo)
    {
        return response()->json([
            'client' => $clientLogo,
        ]);
    }

    // POST /master/client-logos/store
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'short' => 'nullable|string|max:100',
            'logo'  => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->safe()->only(['name', 'short']);
        $data['logo'] = $request->file('logo')->store('landing/clients', 'public');
        $data['urutan'] = (int) (LandingClientLogo::max('urutan') ?? 0) + 1;
        $data['is_active'] = true;

        $client = LandingClientLogo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Client berhasil ditambahkan',
            'client' => $client,
        ], 201);
    }

    // POST /master/client-logos/{clientLogo}  dengan _method=PUT (dari Form.vue)
    public function update(Request $request, LandingClientLogo $clientLogo)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'short' => 'nullable|string|max:100',
            'logo'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->safe()->only(['name', 'short']);

        if ($request->hasFile('logo')) {
            if ($clientLogo->logo) {
                Storage::disk('public')->delete($clientLogo->logo);
            }
            $data['logo'] = $request->file('logo')->store('landing/clients', 'public');
        }

        $clientLogo->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Client berhasil diperbarui',
            'client' => $clientLogo,
        ]);
    }

    // DELETE /master/client-logos/{clientLogo}
    public function destroy(LandingClientLogo $clientLogo)
    {
        if ($clientLogo->logo) {
            Storage::disk('public')->delete($clientLogo->logo);
        }

        $clientLogo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client berhasil dihapus',
        ]);
    }

    // POST /master/client-logos/reorder
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:landing_client_logos,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        foreach ($request->input('ids') as $index => $id) {
            LandingClientLogo::where('id', $id)->update(['urutan' => $index]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan berhasil disimpan',
        ]);
    }
}