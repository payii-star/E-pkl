<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LandingProjectController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingProject::orderBy('urutan')->orderBy('id')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingProject::orderBy('urutan')->orderBy('id')->get()
        ]);
    }

    public function publicFeatured()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingProject::where('is_featured', true)->orderBy('urutan')->get()
        ]);
    }

    public function show(LandingProject $project)
    {
        return response()->json([
            'success' => true, 
            'data' => $project
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:255',
            'url'         => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'urutan'      => 'nullable|integer',
            'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except('thumbnail');
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(5);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['urutan'] = $data['urutan'] ?? ((int) (LandingProject::max('urutan') ?? 0) + 1);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('landing/projects', 'public');
        }

        $project = LandingProject::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Project berhasil ditambahkan', 
            'data'    => $project
        ], 201);
    }

    public function update(Request $request, LandingProject $project)
    {
        $validator = Validator::make($request->all(), [
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'category'         => 'nullable|string|max:255',
            'url'              => 'nullable|string|max:255',
            'is_featured'      => 'nullable|boolean',
            'urutan'           => 'nullable|integer',
            'thumbnail'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_thumbnail' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except(['thumbnail', 'remove_thumbnail']);

        if ($data['title'] !== $project->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(5);
        }

        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('thumbnail')) {
            // Upload thumbnail baru -> hapus yang lama, pasang yang baru
            if ($project->thumbnail) {
                Storage::disk('public')->delete($project->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('landing/projects', 'public');
        } elseif ($request->boolean('remove_thumbnail') && $project->thumbnail) {
            // Tidak ada file baru, tapi user minta hapus thumbnail lama
            Storage::disk('public')->delete($project->thumbnail);
            $data['thumbnail'] = null;
        }

        $project->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Project berhasil diperbarui', 
            'data'    => $project
        ]);
    }

    public function destroy(LandingProject $project)
    {
        if ($project->thumbnail) {
            Storage::disk('public')->delete($project->thumbnail);
        }

        $project->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Project berhasil dihapus'
        ]);
    }
}