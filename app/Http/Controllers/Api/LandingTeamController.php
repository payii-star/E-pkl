<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandingTeamController extends Controller
{
    public function adminIndex()
    {
        $teams = LandingTeam::orderBy('order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $teams->map(fn (LandingTeam $team) => $this->format($team)),
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true,
            'data' => LandingTeam::where('is_active', true)
                ->orderBy('order')
                ->orderBy('id')
                ->get()
        ]);
    }

    public function show(LandingTeam $team)
    {
        return response()->json([
            'success' => true,
            'data' => $this->format($team)
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'order'    => 'nullable|integer',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except(['image']);
        $data['order'] = $data['order'] ?? ((int) (LandingTeam::max('order') ?? 0) + 1);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('landing/team', 'public');
        }

        $team = LandingTeam::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Anggota tim berhasil ditambahkan',
            'data'    => $this->format($team)
        ], 201);
    }

    public function update(Request $request, LandingTeam $team)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'order'    => 'nullable|integer',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except(['image']);

        if ($request->hasFile('image')) {
            if ($team->image) {
                Storage::disk('public')->delete($team->image);
            }
            $data['image'] = $request->file('image')->store('landing/team', 'public');
        }

        $team->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Anggota tim berhasil diperbarui',
            'data'    => $this->format($team)
        ]);
    }

    public function destroy(LandingTeam $team)
    {
        if ($team->image) {
            Storage::disk('public')->delete($team->image);
        }
        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Anggota tim berhasil dihapus'
        ]);
    }

    private function format(LandingTeam $team): array
    {
        return [
            'id' => $team->id,
            'uuid' => (string) $team->id,
            'name' => $team->name,
            'position' => $team->position,
            'image' => $team->image,
            'order' => $team->order,
            'is_active' => $team->is_active,
        ];
    }
} 