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
        $services = LandingService::orderBy('order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services->map(
                fn (LandingService $service) => $this->format($service)
            ),
        ]);
    }

    public function publicIndex()
    {
        $services = LandingService::where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn (LandingService $service) => $this->format($service));

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function show(LandingService $service)
    {
        return response()->json([
            'success' => true,
            'data' => $this->format($service),
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
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = [
            'title'       => $request->title,
            'description' => $request->description,
            'icon'        => $request->icon,
            'order'       => $request->order
                ?? ((int) (LandingService::max('order') ?? 0) + 1),
            'is_active'   => $request->boolean('is_active', true),
        ];

        $service = LandingService::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Service berhasil ditambahkan',
            'data'    => $this->format($service),
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
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = [
            'title'       => $request->title,
            'description' => $request->description,
            'icon'        => $request->icon,
            'order'       => $request->order ?? $service->order,
            'is_active'   => $request->boolean('is_active', $service->is_active),
        ];

        $service->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Service berhasil diperbarui',
            'data'    => $this->format($service),
        ]);
    }

    public function destroy(LandingService $service)
    {
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service berhasil dihapus',
        ]);
    }

    private function format(LandingService $service): array
    {
        return [
            'id'          => $service->id,
            'uuid'        => (string) $service->id,
            'title'       => $service->title,
            'description' => $service->description,
            'icon'        => $service->icon,
            'order'       => $service->order,
            'is_active'   => $service->is_active,
        ];
    }
}