<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index()
    {
        return response()->json(['data' => LandingProject::orderBy('urutan')->get()]);
    }

    public function show(string $id)
    {
        return response()->json(['data' => LandingProject::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'category' => 'nullable|string|max:50',
            'url' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'urutan' => 'nullable|integer',
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = '/storage/' . $request->file('thumbnail')->store('landing/projects', 'public');
        }

        $data = LandingProject::create($validated);
        return response()->json(['message' => 'Proyek berhasil ditambahkan', 'data' => $data], 201);
    }

    public function update(Request $request, string $id)
    {
        $project = LandingProject::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'category' => 'nullable|string|max:50',
            'url' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'urutan' => 'nullable|integer',
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($project->thumbnail) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $project->thumbnail));
            }
            $validated['thumbnail'] = '/storage/' . $request->file('thumbnail')->store('landing/projects', 'public');
        }

        $project->update($validated);
        return response()->json(['message' => 'Proyek berhasil diperbarui', 'data' => $project]);
    }

    public function destroy(string $id)
    {
        $project = LandingProject::findOrFail($id);
        if ($project->thumbnail) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $project->thumbnail));
        }
        $project->delete();
        return response()->json(['message' => 'Proyek berhasil dihapus']);
    }
}
