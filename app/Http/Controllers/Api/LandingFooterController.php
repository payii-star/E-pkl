<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingFooter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LandingFooterController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingFooter::orderBy('order')->orderBy('id')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingFooter::where('is_active', true)->orderBy('order')->get()
        ]);
    }

    public function show(LandingFooter $footer)
    {
        return response()->json([
            'success' => true, 
            'data' => $footer
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'column_title' => 'required|string|max:255',
            'label'        => 'required|string|max:255',
            'url'          => 'required|string|max:255',
            'order'        => 'nullable|integer',
            'is_active'    => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['order'] = $data['order'] ?? ((int) (LandingFooter::max('order') ?? 0) + 1);
        $data['is_active'] = $request->boolean('is_active', true);

        $footer = LandingFooter::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Footer link berhasil ditambahkan', 
            'data'    => $footer
        ], 201);
    }

    public function update(Request $request, LandingFooter $footer)
    {
        $validator = Validator::make($request->all(), [
            'column_title' => 'required|string|max:255',
            'label'        => 'required|string|max:255',
            'url'          => 'required|string|max:255',
            'order'        => 'nullable|integer',
            'is_active'    => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $footer->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Footer link berhasil diperbarui', 
            'data'    => $footer
        ]);
    }

    public function destroy(LandingFooter $footer)
    {
        $footer->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Footer link berhasil dihapus'
        ]);
    }
}