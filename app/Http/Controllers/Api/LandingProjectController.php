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
    const MAX_GALLERY = 6;

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
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:255',
            'url'         => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'urutan'      => 'nullable|integer',
            'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gallery'     => 'nullable|array|max:' . self::MAX_GALLERY,
            'gallery.*'   => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except(['thumbnail', 'gallery']);
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(5);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['urutan'] = $data['urutan'] ?? ((int) (LandingProject::max('urutan') ?? 0) + 1);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('landing/projects', 'public');
        }

        if ($request->hasFile('gallery')) {
            $data['gallery'] = $this->storeGalleryFiles($request->file('gallery'));
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
            'title'              => 'required|string|max:255',
            'client_name'        => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'category'           => 'nullable|string|max:255',
            'url'                => 'nullable|string|max:255',
            'is_featured'        => 'nullable|boolean',
            'urutan'             => 'nullable|integer',
            'thumbnail'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_thumbnail'   => 'nullable|boolean',
            // gallery_existing = path foto lama yang MASIH mau dipertahankan
            'gallery_existing'   => 'nullable|array',
            'gallery_existing.*' => 'string',
            // gallery_new = file foto baru yang mau ditambahkan
            'gallery_new'        => 'nullable|array',
            'gallery_new.*'      => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $existingKept = array_values($request->input('gallery_existing', []));
        $newFilesCount = $request->hasFile('gallery_new') ? count($request->file('gallery_new')) : 0;

        if ((count($existingKept) + $newFilesCount) > self::MAX_GALLERY) {
            return response()->json([
                'message' => 'Maksimal ' . self::MAX_GALLERY . ' foto galeri per project',
                'errors'  => ['gallery_new' => ['Maksimal ' . self::MAX_GALLERY . ' foto galeri per project']],
            ], 422);
        }

        $data = $validator->safe()->except([
            'thumbnail', 'remove_thumbnail', 'gallery_existing', 'gallery_new',
        ]);

        if ($data['title'] !== $project->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(5);
        }

        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('thumbnail')) {
            if ($project->thumbnail) {
                Storage::disk('public')->delete($project->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('landing/projects', 'public');
        } elseif ($request->boolean('remove_thumbnail') && $project->thumbnail) {
            Storage::disk('public')->delete($project->thumbnail);
            $data['thumbnail'] = null;
        }

        // ── GALLERY: hapus foto lama yang tidak dipertahankan, simpan foto baru ──
        $oldGallery = $project->gallery ?? [];

        foreach ($oldGallery as $oldPath) {
            if (!in_array($oldPath, $existingKept, true)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $newPaths = $request->hasFile('gallery_new')
            ? $this->storeGalleryFiles($request->file('gallery_new'))
            : [];

        $data['gallery'] = array_slice(array_merge($existingKept, $newPaths), 0, self::MAX_GALLERY);

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

        foreach (($project->gallery ?? []) as $path) {
            Storage::disk('public')->delete($path);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dihapus'
        ]);
    }

    /**
     * Simpan banyak file galeri sekaligus, kembalikan array path-nya.
     *
     * @param  \Illuminate\Http\UploadedFile[]  $files
     * @return string[]
     */
    private function storeGalleryFiles(array $files): array
    {
        $paths = [];

        foreach ($files as $file) {
            $paths[] = $file->store('landing/projects/gallery', 'public');
        }

        return $paths;
    }
}