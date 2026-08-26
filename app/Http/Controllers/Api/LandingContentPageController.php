<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingContentPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LandingContentPageController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingContentPage::orderBy('id', 'desc')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingContentPage::where('is_published', true)->get()
        ]);
    }

    public function showBySlug($slug)
    {
        $page = LandingContentPage::where('slug', $slug)->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Halaman tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true, 
            'data' => $page
        ]);
    }

    public function show(LandingContentPage $contentPage)
    {
        return response()->json([
            'success' => true, 
            'data' => $contentPage
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'is_published' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['title']);
        $data['is_published'] = $request->boolean('is_published', true);

        $page = LandingContentPage::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Halaman konten berhasil ditambahkan', 
            'data'    => $page
        ], 201);
    }

    public function update(Request $request, LandingContentPage $contentPage)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'is_published' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($data['title'] !== $contentPage->title) {
            $data['slug'] = Str::slug($data['title']);
        }

        $data['is_published'] = $request->boolean('is_published', true);

        $contentPage->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Halaman konten berhasil diperbarui', 
            'data'    => $contentPage
        ]);
    }

    public function destroy(LandingContentPage $contentPage)
    {
        $contentPage->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Halaman konten berhasil dihapus'
        ]);
    }
}