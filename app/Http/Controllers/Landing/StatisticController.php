<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\LandingStatistic;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    public function index()
    {
        return response()->json(['data' => LandingStatistic::orderBy('urutan')->get()]);
    }

    public function show(string $id)
    {
        return response()->json(['data' => LandingStatistic::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'icon' => 'required|string|max:100',
            'statistic' => 'required|string|max:50',
            'label' => 'required|string|max:255',
            'urutan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data = LandingStatistic::create($validated);
        return response()->json(['message' => 'Statistik berhasil disimpan', 'data' => $data], 201);
    }

    public function update(Request $request, string $id)
    {
        $stat = LandingStatistic::findOrFail($id);
        $validated = $request->validate([
            'icon' => 'required|string|max:100',
            'statistic' => 'required|string|max:50',
            'label' => 'required|string|max:255',
            'urutan' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $stat->update($validated);
        return response()->json(['message' => 'Statistik berhasil disimpan', 'data' => $stat]);
    }

    public function destroy(string $id)
    {
        LandingStatistic::findOrFail($id)->delete();
        return response()->json(['message' => 'Statistik berhasil dihapus']);
    }
}
