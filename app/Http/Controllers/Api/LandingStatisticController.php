<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingStatistic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LandingStatisticController extends Controller
{
    public function adminIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingStatistic::orderBy('urutan')->orderBy('id')->get()
        ]);
    }

    public function publicIndex()
    {
        return response()->json([
            'success' => true, 
            'data' => LandingStatistic::where('is_active', true)->orderBy('urutan')->get()
        ]);
    }

    public function show(LandingStatistic $statistic)
    {
        return response()->json([
            'success' => true, 
            'data' => $statistic
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon'      => 'required|string|max:255',
            'statistic' => 'required|string|max:255',
            'label'     => 'required|string|max:255',
            'urutan'    => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['urutan'] = $data['urutan'] ?? ((int) (LandingStatistic::max('urutan') ?? 0) + 1);
        $data['is_active'] = $request->boolean('is_active', true);

        $statistic = LandingStatistic::create($data);

        return response()->json([
            'success' => true, 
            'message' => 'Statistic berhasil ditambahkan', 
            'data'    => $statistic
        ], 201);
    }

    public function update(Request $request, LandingStatistic $statistic)
    {
        $validator = Validator::make($request->all(), [
            'icon'      => 'required|string|max:255',
            'statistic' => 'required|string|max:255',
            'label'     => 'required|string|max:255',
            'urutan'    => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $statistic->update($data);

        return response()->json([
            'success' => true, 
            'message' => 'Statistic berhasil diperbarui', 
            'data'    => $statistic
        ]);
    }

    public function destroy(LandingStatistic $statistic)
    {
        $statistic->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Statistic berhasil dihapus'
        ]);
    }
}