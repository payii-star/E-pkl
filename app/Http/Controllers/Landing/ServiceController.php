<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return response()->json(['data' => LandingService::orderBy('order')->get()]);
    }

    public function show(string $id)
    {
        return response()->json(['data' => LandingService::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer',
        ]);

        $data = LandingService::create($validated);
        return response()->json(['message' => 'Layanan berhasil ditambahkan', 'data' => $data], 201);
    }

    public function update(Request $request, string $id)
    {
        $service = LandingService::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer',
        ]);
        $service->update($validated);
        return response()->json(['message' => 'Layanan berhasil diperbarui', 'data' => $service]);
    }

    public function destroy(string $id)
    {
        LandingService::findOrFail($id)->delete();
        return response()->json(['message' => 'Layanan berhasil dihapus']);
    }
}
