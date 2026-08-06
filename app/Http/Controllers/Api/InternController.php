<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InternController extends Controller
{
    public function estimation(Request $request)
    {
        $user = $request->user();

        if (!$user->tanggal_mulai || !$user->tanggal_selesai) {
            return response()->json([
                'message' => 'Data peserta magang untuk akun ini belum diisi. Hubungi admin.',
            ], 404);
        }

        $start = Carbon::parse($user->tanggal_mulai)->startOfDay();
        $end = Carbon::parse($user->tanggal_selesai)->startOfDay();
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
            'start_date' => $user->tanggal_mulai->format('Y-m-d'),
            'end_date' => $user->tanggal_selesai->format('Y-m-d'),
            'total_days' => $totalDays,
            'days_passed' => $daysPassed,
            'days_remaining' => $daysRemaining,
            'percentage' => min($percentage, 100),
        ]);
    }
}