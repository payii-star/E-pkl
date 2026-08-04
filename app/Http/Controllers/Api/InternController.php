<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InternController extends Controller
{
    public function estimation(Request $request)
    {
        $intern = $request->user()->intern;

        if (!$intern) {
            return response()->json([
                'message' => 'Data peserta magang untuk akun ini belum diisi. Hubungi admin.',
            ], 404);
        }

        $start = Carbon::parse($intern->start_date)->startOfDay();
        $end = Carbon::parse($intern->end_date)->startOfDay();
        $today = Carbon::today();

        $totalDays = $start->diffInDays($end) + 1;

        if ($today->lt($start)) {
            $daysPassed = 0;
        } elseif ($today->gt($end)) {
            $daysPassed = $totalDays;
        } else {
            $daysPassed = $start->diffInDays($today) + 1;
        }

        $daysRemaining = max($totalDays - $daysPassed, 0);
        $percentage = $totalDays > 0 ? round(($daysPassed / $totalDays) * 100) : 0;

        return response()->json([
            'start_date' => $intern->start_date->format('Y-m-d'),
            'end_date' => $intern->end_date->format('Y-m-d'),
            'total_days' => $totalDays,
            'days_passed' => $daysPassed,
            'days_remaining' => $daysRemaining,
            'percentage' => min($percentage, 100),
        ]);
    }
}