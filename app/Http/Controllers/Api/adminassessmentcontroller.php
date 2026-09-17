<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternAssessment;
use App\Models\User;
use App\Support\ComputesInternAssessment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Fitur Rekap & Penilaian Siswa Magang (sisi ADMIN).
 * Logic perhitungan rekap & nilai sistem ada di trait ComputesInternAssessment
 * (dipakai bareng dengan AssessmentController di sisi siswa, biar angkanya
 * selalu konsisten).
 */
class AdminAssessmentController extends Controller
{
    use ComputesInternAssessment;

    // GET /admin/assessments?date= -> ringkasan semua siswa magang PADA 1 TANGGAL
    public function index(Request $request)
    {
        $date = $this->resolveDate($request);

        $interns = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['hr-admin', 'atasan', 'admin-landing']);
            })
            ->where('name', 'not like', '%landing%')
            ->where('email', 'not like', '%landing%')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'photo', 'nim_nis', 'asal_instansi', 'tanggal_mulai', 'tanggal_selesai']);

        $data = $interns->map(function ($intern) use ($date) {
            $summary = $this->buildDaySummary($intern, $date);
            $summary['assessment'] = $this->assessmentPayload($intern);
            return $summary;
        });

        return response()->json([
            'data' => $data,
            'date' => $date->toDateString(),
        ]);
    }

    // GET /admin/assessments/{user}?date= -> detail + riwayat harian s.d. tanggal ini
    public function show(Request $request, User $user)
    {
        $date = $this->resolveDate($request);

        $summary = $this->buildDaySummary($user, $date);
        $summary['assessment'] = $this->assessmentPayload($user);

        if ($summary['has_period']) {
            $summary['daily_history'] = $this->buildDailyHistory($user, $date);
        }

        return response()->json(['data' => $summary]);
    }

    // POST /admin/assessments/{user}/score -> admin input/ubah nilai akhir manual (1 nilai per siswa)
    public function storeScore(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'score' => 'required|integer|min:0|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $assessment = InternAssessment::updateOrCreate(
            ['user_id' => $user->id],
            [
                'score' => $request->score,
                'note' => $request->note,
                'created_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'data' => $assessment,
            'message' => 'Nilai akhir berhasil disimpan',
        ]);
    }

    private function resolveDate(Request $request): Carbon
    {
        return $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : now()->startOfDay();
    }

    private function assessmentPayload(User $intern): ?array
    {
        $assessment = InternAssessment::where('user_id', $intern->id)->first();

        return $assessment ? [
            'score' => $assessment->score,
            'note' => $assessment->note,
            'updated_at' => $assessment->updated_at,
        ] : null;
    }
}