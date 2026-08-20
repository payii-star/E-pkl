<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LandingServiceController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingService::orderBy('order')->orderBy('id')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingService::where('is_active', true)->orderBy('order')->get()
        ]);
    }

    public function show(LandingService $service)
    {
        return response()->json([
            'success' => true, 
            'data' => $service
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:255',
            'order'       => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['order'] = $data['order'] ?? ((int) (LandingService::max('order') ?? 0) + 1);
        $data['is_active'] = $request->boolean('is_active', true);

        $service = LandingService::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Service berhasil ditambahkan', 
            'data'    => $service
        ], 201);
    }

    public function update(Request $request, LandingService $service)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:255',
            'order'       => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $service->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Service berhasil diperbarui', 
            'data'    => $service
        ]);
    }

    public function destroy(LandingService $service)
    {
        $service->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Service berhasil dihapus'
        ]);
    }
}