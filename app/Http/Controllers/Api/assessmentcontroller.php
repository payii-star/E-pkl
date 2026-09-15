<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternAssessment;
use App\Support\ComputesInternAssessment;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Fitur Nilai (sisi SISWA) — siswa melihat rekap & nilai dirinya sendiri.
 * Pakai trait yang sama dengan AdminAssessmentController supaya angkanya
 * selalu identik dengan yang dilihat admin.
 */
class AssessmentController extends Controller
{
    use ComputesInternAssessment;

    // GET /assessments/me -> nilai & rekap milik siswa yang sedang login
    public function me(Request $request)
    {
        $user = $request->user();

        // "Hari ini", tapi tidak lebih jauh dari tanggal selesai magang
        // (kalau magangnya sudah lewat, rekap berhenti di hari terakhir periode)
        $date = now()->startOfDay();
        if ($user->tanggal_selesai) {
            $periodEnd = Carbon::parse($user->tanggal_selesai)->startOfDay();
            if ($date->gt($periodEnd)) {
                $date = $periodEnd;
            }
        }

        $summary = $this->buildDaySummary($user, $date);

        $assessment = InternAssessment::where('user_id', $user->id)->first();
        $summary['assessment'] = $assessment ? [
            'score' => $assessment->score,
            'note' => $assessment->note,
            'updated_at' => $assessment->updated_at,
        ] : null;

        if ($summary['has_period']) {
            $summary['daily_history'] = $this->buildDailyHistory($user, $date);
        }

        return response()->json(['data' => $summary]);
    }
}