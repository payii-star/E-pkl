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
        return response()->json([
            'success' => true, 
            'data' => LandingTeam::orderBy('urutan')->orderBy('id')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingTeam::orderBy('urutan')->orderBy('id')->get()
        ]);
    }

    public function show(LandingTeam $team)
    {
        return response()->json([
            'success' => true, 
            'data' => $team
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'position'   => 'required|string|max:255',
            'bio'        => 'nullable|string',
            'urutan'     => 'nullable|integer',
            'photo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'socials'    => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except(['photo', 'socials']);
        $data['urutan'] = $data['urutan'] ?? ((int) (LandingTeam::max('urutan') ?? 0) + 1);

        if ($request->has('socials')) {
            $data['socials'] = is_string($request->socials) 
                ? json_decode($request->socials, true) 
                : $request->socials;
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('landing/team', 'public');
        }

        $team = LandingTeam::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Anggota tim berhasil ditambahkan', 
            'data'    => $team
        ], 201);
    }

    public function update(Request $request, LandingTeam $team)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'position'   => 'required|string|max:255',
            'bio'        => 'nullable|string',
            'urutan'     => 'nullable|integer',
            'photo'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'socials'    => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->safe()->except(['photo', 'socials']);

        if ($request->has('socials')) {
            $data['socials'] = is_string($request->socials) 
                ? json_decode($request->socials, true) 
                : $request->socials;
        }

        if ($request->hasFile('photo')) {
            if ($team->photo) {
                Storage::disk('public')->delete($team->photo);
            }
            $data['photo'] = $request->file('photo')->store('landing/team', 'public');
        }

        $team->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Anggota tim berhasil diperbarui', 
            'data'    => $team
        ]);
    }

    public function destroy(LandingTeam $team)
    {
        if ($team->photo) {
            Storage::disk('public')->delete($team->photo);
        }

        $team->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Anggota tim berhasil dihapus'
        ]);
    }
}