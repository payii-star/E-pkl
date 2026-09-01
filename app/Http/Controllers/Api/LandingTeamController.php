<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandingTeamController extends Controller
{
    /**
     * GET /front/teams
     * Data team untuk landing page/public.
     */
    public function publicIndex(Request $request)
    {
        $teams = LandingTeam::where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(function (LandingTeam $team) use ($request) {
                $imageUrl = null;

                if ($team->image) {
                    $imageUrl = $request->getSchemeAndHttpHost()
                        . '/storage/'
                        . ltrim($team->image, '/');
                } else {
                    $imageUrl = 'https://ui-avatars.com/api/?name='
                        . urlencode($team->name)
                        . '&background=3b82f6&color=fff&size=256';
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'position' => $team->position,
                    'image' => $team->image,
                    'image_url' => $imageUrl,
                    'order' => $team->order,
                    'is_active' => $team->is_active,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $teams,
        ]);
    }

    /**
     * GET /master/teams
     * Data team untuk dashboard/admin.
     */
    public function adminIndex(Request $request)
    {
        $teams = LandingTeam::orderBy('order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $teams->map(
                fn (LandingTeam $team) => $this->format($team)
            ),
        ]);
    }

    /**
     * GET /master/teams/{team}
     */
    public function show(LandingTeam $team)
    {
        return response()->json([
            'success' => true,
            'data' => $this->format($team),
        ]);
    }

    /**
     * POST /master/teams
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'position' => $request->position,
            'order' => $request->order
                ?? ((int) (LandingTeam::max('order') ?? 0) + 1),
            'is_active' => true,
        ];

        /*
         * Semua foto team disimpan di:
         *
         * storage/app/public/landing/team/
         *
         * dan nantinya diakses melalui:
         *
         * /storage/landing/team/namafile.jpg
         */
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('landing/team', 'public');
        }

        $team = LandingTeam::create($data);

        $team->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Anggota tim berhasil ditambahkan',
            'data' => $this->format($team),
        ], 201);
    }

    /**
     * PUT /master/teams/{team}
     */
    public function update(Request $request, LandingTeam $team)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'position' => $request->position,
            'order' => $request->order ?? $team->order,
        ];

        /*
         * Kalau upload foto baru:
         * 1. Hapus foto lama
         * 2. Simpan foto baru ke landing/team
         */
        if ($request->hasFile('image')) {
            if ($team->image) {
                Storage::disk('public')->delete($team->image);
            }

            $data['image'] = $request->file('image')
                ->store('landing/team', 'public');
        }

        $team->update($data);

        $team->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Anggota tim berhasil diperbarui',
            'data' => $this->format($team),
        ]);
    }

    /**
     * DELETE /master/teams/{team}
     */
    public function destroy(LandingTeam $team)
    {
        if ($team->image) {
            Storage::disk('public')->delete($team->image);
        }

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Anggota tim berhasil dihapus',
        ]);
    }

    /**
     * Format response team.
     */
    private function format(LandingTeam $team): array
    {
        $imageUrl = null;

        if ($team->image) {
            $imageUrl = asset(
                'storage/' . ltrim($team->image, '/')
            );
        } else {
            $imageUrl = 'https://ui-avatars.com/api/?name='
                . urlencode($team->name)
                . '&background=3b82f6&color=fff&size=256';
        }

        return [
            'id' => $team->id,
            'uuid' => (string) $team->id,
            'name' => $team->name,
            'position' => $team->position,
            'image' => $team->image,
            'image_url' => $imageUrl,
            'order' => $team->order,
            'is_active' => $team->is_active,
        ];
    }
}   