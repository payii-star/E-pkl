<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandingServiceController extends Controller
{
    /**
     * Admin - list semua services
     */
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

    /**
     * Public - services yang aktif
     * Dipakai oleh Landing
     */
    public function publicIndex(Request $request)
    {
        $services = LandingService::where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(function (LandingService $service) use ($request) {
                return $this->format($service, $request);
            });

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * Admin - detail service
     */
    public function show(LandingService $service)
    {
        return response()->json([
            'success' => true,
            'data' => $this->format($service),
        ]);
    }

    /**
     * Admin - tambah service
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon'        => 'nullable|image|mimes:jpeg,jpg,png,webp,svg|max:2048',
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
            'order'       => $request->order
                ?? ((int) (LandingService::max('order') ?? 0) + 1),
            'is_active'   => $request->boolean('is_active', true),
        ];

        /*
         * Simpan icon ke:
         * storage/app/public/landing/services
         */
        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')
                ->store('landing/services', 'public');
        }

        $service = LandingService::create($data);

        $service->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Service berhasil ditambahkan',
            'data'    => $this->format($service),
        ], 201);
    }

    /**
     * Admin - update service
     */
    public function update(Request $request, LandingService $service)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon'        => 'nullable|image|mimes:jpeg,jpg,png,webp,svg|max:2048',
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
            'order'       => $request->order ?? $service->order,
            'is_active'   => $request->boolean(
                'is_active',
                $service->is_active
            ),
        ];

        /*
         * Kalau upload icon baru:
         * 1. hapus icon lama
         * 2. simpan icon baru
         */
        if ($request->hasFile('icon')) {

            if ($service->icon) {
                Storage::disk('public')->delete($service->icon);
            }

            $data['icon'] = $request->file('icon')
                ->store('landing/services', 'public');
        }

        $service->update($data);

        $service->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Service berhasil diperbarui',
            'data'    => $this->format($service),
        ]);
    }

    /**
     * Admin - hapus service
     */
    public function destroy(LandingService $service)
    {
        /*
         * Hapus file icon dari storage sebelum
         * menghapus record database.
         */
        if ($service->icon) {
            Storage::disk('public')->delete($service->icon);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service berhasil dihapus',
        ]);
    }

    /**
     * Format response API
     */
    private function format(
        LandingService $service,
        ?Request $request = null
    ): array {
        $imageUrl = null;

        if ($service->icon) {

            /*
             * Kalau database sudah menyimpan URL penuh,
             * jangan ditambahkan /storage lagi.
             */
            if (
                str_starts_with($service->icon, 'http://') ||
                str_starts_with($service->icon, 'https://')
            ) {
                $imageUrl = $service->icon;
            } else {
                /*
                 * Gunakan host request agar frontend Landing
                 * mendapatkan URL backend yang benar.
                 */
                $host = $request
                    ? $request->getSchemeAndHttpHost()
                    : request()->getSchemeAndHttpHost();

                $imageUrl = $host . '/storage/' . ltrim(
                    $service->icon,
                    '/'
                );
            }
        }

        return [
            'id'          => $service->id,
            'uuid'        => (string) $service->id,
            'title'       => $service->title,
            'description' => $service->description,
            'icon'        => $service->icon,
            'image_url'   => $imageUrl,
            'order'       => $service->order,
            'is_active'   => $service->is_active,
        ];
    }
}