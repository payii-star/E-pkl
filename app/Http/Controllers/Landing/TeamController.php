<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    public function index()
    {
        return response()->json(['data' => LandingTeam::orderBy('order')->get()]);
    }

    public function show(string $id)
    {
        return response()->json(['data' => LandingTeam::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = '/storage/' . $request->file('image')->store('landing/teams', 'public');
        }

        $data = LandingTeam::create($validated);
        return response()->json(['message' => 'Anggota tim berhasil ditambahkan', 'data' => $data], 201);
    }

    public function update(Request $request, string $id)
    {
        $team = LandingTeam::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            if ($team->image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $team->image));
            }
            $validated['image'] = '/storage/' . $request->file('image')->store('landing/teams', 'public');
        }

        $team->update($validated);
        return response()->json(['message' => 'Anggota tim berhasil diperbarui', 'data' => $team]);
    }

    public function destroy(string $id)
    {
        $team = LandingTeam::findOrFail($id);
        if ($team->image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $team->image));
        }
        $team->delete();
        return response()->json(['message' => 'Anggota tim berhasil dihapus']);
    }
}
