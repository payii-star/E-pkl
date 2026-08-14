<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingClientLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientLogoController extends Controller
{
    public function index()
    {
        $data = LandingClientLogo::orderBy('urutan')->get();
        return response()->json(['data' => $data]);
    }

    public function show(string $id)
    {
        $data = LandingClientLogo::findOrFail($id);
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'urutan' => 'nullable|integer',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = '/storage/' . $request->file('logo')->store('landing/clients', 'public');
        }

        $data = LandingClientLogo::create($validated);

        return response()->json(['message' => 'Klien berhasil ditambahkan', 'data' => $data], 201);
    }

    public function update(Request $request, string $id)
    {
        $client = LandingClientLogo::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'urutan' => 'nullable|integer',
        ]);

        if ($request->hasFile('logo')) {
            if ($client->logo) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $client->logo));
            }
            $validated['logo'] = '/storage/' . $request->file('logo')->store('landing/clients', 'public');
        }

        $client->update($validated);

        return response()->json(['message' => 'Klien berhasil diperbarui', 'data' => $client]);
    }

    public function destroy(string $id)
    {
        $client = LandingClientLogo::findOrFail($id);
        if ($client->logo) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $client->logo));
        }
        $client->delete();

        return response()->json(['message' => 'Klien berhasil dihapus']);
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array']);
        foreach ($request->order as $index => $id) {
            LandingClientLogo::where('id', $id)->update(['urutan' => $index]);
        }
        return response()->json(['message' => 'Urutan berhasil disimpan']);
    }
}
